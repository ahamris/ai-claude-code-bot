"""Basis plugin class voor het bot framework.

Plugins breiden de bot uit met specifieke functionaliteit
en registreren eigen commands.
"""

import asyncio
import logging
from abc import ABC, abstractmethod
from typing import TYPE_CHECKING, Any, Optional

from telegram import Update
from telegram.ext import Application, CommandHandler, ContextTypes

if TYPE_CHECKING:
    from ..bot import AIBot

logger = logging.getLogger(__name__)


class BasePlugin(ABC):
    """Abstracte basisclass voor bot plugins."""

    # Plugin naam (gebruikt in YAML config)
    name: str = ""
    # Beschrijving
    description: str = ""
    # Commands die deze plugin registreert: [("naam", "beschrijving")]
    commands: list[tuple[str, str]] = []

    def __init__(self, bot: "AIBot", config: dict[str, Any]) -> None:
        """Initialiseer de plugin.

        Args:
            bot: Referentie naar de hoofd bot instance
            config: Plugin-specifieke configuratie
        """
        self.bot = bot
        self.config = config

    def register(self, app: Application) -> None:
        """Registreer plugin commands bij de Telegram applicatie.

        Args:
            app: Telegram Application instance
        """
        for cmd_name, cmd_desc in self.commands:
            handler_method = getattr(self, f"handle_{cmd_name}", None)
            if handler_method:
                app.add_handler(
                    CommandHandler(cmd_name, self._wrap_handler(handler_method))
                )
                logger.debug(f"Plugin {self.name}: command /{cmd_name} geregistreerd.")
            else:
                logger.warning(
                    f"Plugin {self.name}: handler voor /{cmd_name} niet gevonden."
                )

    def _wrap_handler(self, handler):
        """Wrap een handler met autorisatie en error handling.

        Args:
            handler: De originele handler functie

        Returns:
            Gewrapte handler functie
        """
        async def wrapper(
            update: Update, context: ContextTypes.DEFAULT_TYPE
        ) -> None:
            if not self.bot.is_authorized(update):
                await update.effective_message.reply_text(
                    "Je bent niet geautoriseerd om deze bot te gebruiken."
                )
                return

            try:
                await handler(update, context)
            except Exception as e:
                logger.error(
                    f"Fout in plugin {self.name}: {e}", exc_info=True
                )
                try:
                    await update.effective_message.reply_text(
                        f"Plugin fout ({self.name}): {e}"
                    )
                except Exception:
                    pass

        return wrapper

    @abstractmethod
    async def initialize(self) -> None:
        """Initialiseer de plugin. Wordt aangeroepen bij het starten van de bot."""
        ...

    async def cleanup(self) -> None:
        """Ruim plugin resources op. Wordt aangeroepen bij het stoppen van de bot."""
        pass

    async def run_ssh_command(
        self,
        host: str,
        command: str,
        jump_host: Optional[str] = None,
        timeout: int = 30,
    ) -> tuple[str, str, int]:
        """Voer een SSH command uit op een remote host.

        Args:
            host: Doelhost (IP of hostname)
            command: Uit te voeren command
            jump_host: Optionele jump host
            timeout: Maximale wachttijd in seconden

        Returns:
            Tuple van (stdout, stderr, return_code)
        """
        ssh_config = self.bot.config.get("ssh", {})
        user = ssh_config.get("user", "root")

        cmd_parts = [
            "ssh",
            "-o", "ConnectTimeout=5",
            "-o", "StrictHostKeyChecking=no",
        ]

        if jump_host:
            cmd_parts.extend(["-J", f"{user}@{jump_host}"])

        cmd_parts.append(f"{user}@{host}")
        cmd_parts.append(command)

        try:
            proc = await asyncio.create_subprocess_exec(
                *cmd_parts,
                stdout=asyncio.subprocess.PIPE,
                stderr=asyncio.subprocess.PIPE,
            )
            stdout, stderr = await asyncio.wait_for(
                proc.communicate(), timeout=timeout
            )
            return (
                stdout.decode("utf-8", errors="replace"),
                stderr.decode("utf-8", errors="replace"),
                proc.returncode or 0,
            )
        except asyncio.TimeoutError:
            return ("", f"SSH timeout na {timeout}s", 1)
        except OSError as e:
            return ("", str(e), 1)

    def get_host_info(self, host_name: str) -> tuple[str, Optional[str]]:
        """Haal host adres en optionele jump host op uit SSH configuratie.

        Args:
            host_name: Naam van de host in de configuratie

        Returns:
            Tuple van (host_adres, jump_host of None)
        """
        ssh_config = self.bot.config.get("ssh", {})
        hosts = ssh_config.get("hosts", {})
        host_info = hosts.get(host_name)

        if isinstance(host_info, str):
            return (host_info, None)
        elif isinstance(host_info, dict):
            return (host_info.get("host", ""), host_info.get("jump"))
        else:
            return ("", None)
