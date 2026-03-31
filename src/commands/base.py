"""Basis command class voor het bot framework.

Alle commands erven van BaseCommand en registreren zichzelf
bij de Telegram applicatie.
"""

import logging
from abc import ABC, abstractmethod
from typing import TYPE_CHECKING, Any

from telegram import Update
from telegram.ext import Application, CommandHandler, ContextTypes

if TYPE_CHECKING:
    from ..bot import AIBot

logger = logging.getLogger(__name__)


class BaseCommand(ABC):
    """Abstracte basisclass voor bot commands."""

    # Naam van het command (zonder /)
    name: str = ""
    # Beschrijving voor /help
    description: str = ""

    def __init__(self, bot: "AIBot") -> None:
        """Initialiseer het command.

        Args:
            bot: Referentie naar de hoofd bot instance
        """
        self.bot = bot

    def register(self, app: Application) -> None:
        """Registreer dit command bij de Telegram applicatie.

        Args:
            app: Telegram Application instance
        """
        if not self.name:
            raise ValueError(
                f"Command {self.__class__.__name__} heeft geen naam gedefinieerd."
            )

        app.add_handler(CommandHandler(self.name, self._handle_wrapper))
        logger.debug(f"Command /{self.name} geregistreerd.")

    async def _handle_wrapper(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Wrapper rond de handler voor autorisatie en error handling.

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        # Autorisatie check
        if not self.bot.is_authorized(update):
            logger.warning(
                f"Ongeautoriseerde toegang tot /{self.name} door "
                f"user {update.effective_user.id if update.effective_user else 'onbekend'}"
            )
            await update.effective_message.reply_text(
                "Je bent niet geautoriseerd om deze bot te gebruiken."
            )
            return

        try:
            await self.handle(update, context)
        except Exception as e:
            logger.error(f"Fout bij uitvoeren /{self.name}: {e}", exc_info=True)
            error_msg = f"Er ging iets mis bij het uitvoeren van /{self.name}: {e}"
            try:
                await update.effective_message.reply_text(error_msg)
            except Exception:
                pass

    @abstractmethod
    async def handle(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk het command. Moet geimplementeerd worden door subclasses.

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        ...
