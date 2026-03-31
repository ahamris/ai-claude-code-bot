"""Hoofd bot class voor het AI Bot Framework.

Laadt configuratie uit YAML, registreert commands en plugins,
en draait de Telegram polling loop.
"""

import argparse
import logging
import sys
from pathlib import Path
from typing import Any, Optional

import yaml
from telegram import BotCommand, Update
from telegram.ext import (
    Application,
    ContextTypes,
    MessageHandler,
    filters,
)

from .ai_handler import AIHandler
from .commands.admin import CreateCommand, InstallCommand, ListCommand, LogsCommand
from .commands.base import BaseCommand
from .commands.memory_cmd import ForgetCommand, LearnCommand, MemoryCommand
from .commands.network import DevicesCommand, PingCommand, ScanCommand
from .commands.security import SecurityCommand
from .commands.status import StatusCommand
from .formatter import TelegramFormatter
from .memory import Memory
from .monitoring import Monitoring
from .plugins.base import BasePlugin
from .plugins.homeassistant import HomeAssistantPlugin
from .plugins.mikrotik import MikroTikPlugin
from .plugins.proxmox import ProxmoxPlugin

logger = logging.getLogger(__name__)

# Register van beschikbare plugins
PLUGIN_REGISTRY: dict[str, type[BasePlugin]] = {
    "proxmox": ProxmoxPlugin,
    "homeassistant": HomeAssistantPlugin,
    "mikrotik": MikroTikPlugin,
}

# Alle standaard commands
DEFAULT_COMMANDS: list[type[BaseCommand]] = [
    StatusCommand,
    CreateCommand,
    InstallCommand,
    ListCommand,
    LogsCommand,
    SecurityCommand,
    MemoryCommand,
    LearnCommand,
    ForgetCommand,
    PingCommand,
    ScanCommand,
    DevicesCommand,
]


class AIBot:
    """Hoofd bot class die alles samenbrengt.

    Laadt configuratie, initialiseert alle modules, en start de bot.
    """

    def __init__(self, config_path: str) -> None:
        """Initialiseer de bot vanuit een YAML configuratie.

        Args:
            config_path: Pad naar het YAML configuratiebestand
        """
        self.config_path = Path(config_path)
        self.config: dict[str, Any] = {}
        self.name: str = "AI Bot"
        self.chat_ids: list[int] = []
        self.memory: Optional[Memory] = None
        self.monitoring: Optional[Monitoring] = None
        self.formatter: TelegramFormatter = TelegramFormatter()
        self.ai_handler: Optional[AIHandler] = None
        self.plugins: list[BasePlugin] = []
        self._commands: list[BaseCommand] = []
        self._app: Optional[Application] = None

        # Laad configuratie
        self._load_config()
        self._setup_logging()
        self._setup_modules()

    def _load_config(self) -> None:
        """Laad en valideer de YAML configuratie."""
        if not self.config_path.exists():
            raise FileNotFoundError(
                f"Configuratiebestand niet gevonden: {self.config_path}"
            )

        with open(self.config_path, "r", encoding="utf-8") as f:
            self.config = yaml.safe_load(f) or {}

        # Valideer verplichte velden
        bot_config = self.config.get("bot", {})
        if not bot_config.get("token"):
            raise ValueError("Bot token ontbreekt in configuratie (bot.token)")

        self.name = bot_config.get("name", "AI Bot")
        self.chat_ids = bot_config.get("chat_ids", [])

        if not self.chat_ids:
            logger.warning(
                "Geen chat_ids geconfigureerd - iedereen kan de bot gebruiken!"
            )

    def _setup_logging(self) -> None:
        """Configureer logging op basis van config."""
        log_level = self.config.get("logging", {}).get("level", "INFO")
        log_format = "%(asctime)s - %(name)s - %(levelname)s - %(message)s"

        logging.basicConfig(
            format=log_format,
            level=getattr(logging, log_level.upper(), logging.INFO),
        )

        # Verminder telegram library logging
        logging.getLogger("httpx").setLevel(logging.WARNING)
        logging.getLogger("telegram").setLevel(logging.WARNING)

    def _setup_modules(self) -> None:
        """Initialiseer alle modules op basis van configuratie."""
        # Formatter
        fmt_config = self.config.get("formatter", {})
        self.formatter = TelegramFormatter(
            mode=fmt_config.get("mode", "plain"),
            strip_markdown=fmt_config.get("strip_markdown", True),
        )

        # Memory
        mem_config = self.config.get("memory", {})
        if mem_config.get("enabled", False):
            self.memory = Memory(
                data_dir=mem_config.get("data_dir", "/opt/ai-bot/data"),
                enabled=True,
                auto_learn=mem_config.get("auto_learn", True),
            )

        # AI Handler
        ai_config = self.config.get("ai", {})
        self.ai_handler = AIHandler(
            system_prompt=ai_config.get("system_prompt", ""),
            max_turns=ai_config.get("max_turns", 25),
            timeout=ai_config.get("timeout", 300),
            memory=self.memory,
            claude_binary=ai_config.get("claude_binary", "claude"),
            working_dir=ai_config.get("working_dir"),
        )

        # Monitoring
        mon_config = self.config.get("monitoring", {})
        ssh_config = self.config.get("ssh", {})
        self.monitoring = Monitoring(config=mon_config, ssh_config=ssh_config)

        # Plugins
        plugin_names = self.config.get("plugins", [])
        plugins_config = self.config.get("plugins_config", {})

        for plugin_name in plugin_names:
            if isinstance(plugin_name, str) and plugin_name in PLUGIN_REGISTRY:
                plugin_cls = PLUGIN_REGISTRY[plugin_name]
                plugin_cfg = plugins_config.get(plugin_name, {})
                plugin = plugin_cls(bot=self, config=plugin_cfg)
                self.plugins.append(plugin)
                logger.info(f"Plugin geladen: {plugin_name}")
            else:
                logger.warning(f"Onbekende plugin: {plugin_name}")

    def is_authorized(self, update: Update) -> bool:
        """Controleer of de gebruiker geautoriseerd is.

        Als er geen chat_ids geconfigureerd zijn, is iedereen toegestaan.

        Args:
            update: Telegram Update object

        Returns:
            True als geautoriseerd
        """
        if not self.chat_ids:
            return True

        user_id = update.effective_user.id if update.effective_user else None
        chat_id = update.effective_chat.id if update.effective_chat else None

        return user_id in self.chat_ids or chat_id in self.chat_ids

    async def _handle_message(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk een regulier tekstbericht (geen command).

        Stuurt het bericht naar de AI handler.

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        if not self.is_authorized(update):
            return

        if not update.effective_message or not update.effective_message.text:
            return

        user_message = update.effective_message.text

        try:
            response = await self.ai_handler.process_message(
                update, user_message
            )
            await self.formatter.send_message(update, response)
        except TimeoutError as e:
            await update.effective_message.reply_text(str(e))
        except RuntimeError as e:
            await update.effective_message.reply_text(f"AI fout: {e}")
        except Exception as e:
            logger.error(f"Onverwachte fout bij verwerken bericht: {e}", exc_info=True)
            await update.effective_message.reply_text(
                "Er ging iets mis bij het verwerken van je bericht."
            )

    async def _error_handler(
        self, update: object, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Globale error handler voor de bot.

        Args:
            update: Telegram Update object (kan None zijn)
            context: Telegram context met error info
        """
        logger.error(f"Bot fout: {context.error}", exc_info=context.error)

        if isinstance(update, Update) and update.effective_message:
            try:
                await update.effective_message.reply_text(
                    "Er is een interne fout opgetreden. Probeer het later opnieuw."
                )
            except Exception:
                pass

    async def _post_init(self, app: Application) -> None:
        """Wordt aangeroepen na het initialiseren van de applicatie.

        Initialiseert plugins en start monitoring.

        Args:
            app: Telegram Application instance
        """
        # Initialiseer plugins
        for plugin in self.plugins:
            try:
                await plugin.initialize()
            except Exception as e:
                logger.error(
                    f"Plugin {plugin.name} initialisatie mislukt: {e}",
                    exc_info=True,
                )

        # Start monitoring
        if self.monitoring and self.monitoring.enabled:
            await self.monitoring.start(app.bot, self.chat_ids)

        # Stel bot commands in voor het Telegram menu
        bot_commands: list[BotCommand] = []
        for cmd in self._commands:
            if cmd.name and cmd.description:
                bot_commands.append(BotCommand(cmd.name, cmd.description))
        for plugin in self.plugins:
            for cmd_name, cmd_desc in plugin.commands:
                bot_commands.append(BotCommand(cmd_name, cmd_desc))

        if bot_commands:
            try:
                await app.bot.set_my_commands(bot_commands)
            except Exception as e:
                logger.warning(f"Kon bot commands niet instellen: {e}")

        logger.info(f"Bot '{self.name}' is gestart en klaar voor gebruik.")

    async def _post_shutdown(self, app: Application) -> None:
        """Wordt aangeroepen bij het afsluiten van de applicatie.

        Args:
            app: Telegram Application instance
        """
        # Stop monitoring
        if self.monitoring:
            await self.monitoring.stop()

        # Cleanup plugins
        for plugin in self.plugins:
            try:
                await plugin.cleanup()
            except Exception as e:
                logger.error(f"Plugin {plugin.name} cleanup mislukt: {e}")

        logger.info(f"Bot '{self.name}' is afgesloten.")

    def run(self) -> None:
        """Start de bot en draai de polling loop."""
        bot_config = self.config.get("bot", {})
        token = bot_config["token"]

        # Bouw de applicatie
        builder = Application.builder().token(token)
        self._app = builder.post_init(self._post_init).post_shutdown(
            self._post_shutdown
        ).build()

        # Registreer standaard commands
        for cmd_cls in DEFAULT_COMMANDS:
            cmd = cmd_cls(bot=self)
            cmd.register(self._app)
            self._commands.append(cmd)

        # Registreer plugin commands
        for plugin in self.plugins:
            plugin.register(self._app)

        # Registreer message handler voor vrije tekst (na commands)
        self._app.add_handler(
            MessageHandler(
                filters.TEXT & ~filters.COMMAND,
                self._handle_message,
            )
        )

        # Registreer error handler
        self._app.add_error_handler(self._error_handler)

        logger.info(f"Bot '{self.name}' wordt gestart...")
        self._app.run_polling(
            allowed_updates=Update.ALL_TYPES,
            drop_pending_updates=True,
        )


def main() -> None:
    """Entry point voor de bot applicatie."""
    parser = argparse.ArgumentParser(
        description="AI Bot Framework powered by Claude Code"
    )
    parser.add_argument(
        "config",
        help="Pad naar het YAML configuratiebestand",
    )
    parser.add_argument(
        "--version",
        action="version",
        version="ai-claude-code-bot 0.1.0",
    )

    args = parser.parse_args()

    try:
        bot = AIBot(config_path=args.config)
        bot.run()
    except FileNotFoundError as e:
        print(f"FOUT: {e}", file=sys.stderr)
        sys.exit(1)
    except ValueError as e:
        print(f"CONFIGURATIEFOUT: {e}", file=sys.stderr)
        sys.exit(1)
    except KeyboardInterrupt:
        print("\nBot gestopt.")
    except Exception as e:
        print(f"FATALE FOUT: {e}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
