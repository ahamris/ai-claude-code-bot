"""Admin commands - /create, /install, /list, /logs.

Delegeert naar de actieve plugins voor host-beheer.
"""

import logging

from telegram import Update
from telegram.ext import ContextTypes

from .base import BaseCommand

logger = logging.getLogger(__name__)


class CreateCommand(BaseCommand):
    """Maak een nieuwe container of VM aan via de Proxmox plugin."""

    name = "create"
    description = "Maak een nieuwe container/VM aan"

    async def handle(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk het /create command.

        Verwacht argumenten: /create <type> <hostname> [opties]
        Delegeert naar de AI handler als er geen duidelijke parameters zijn.

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        args = context.args or []

        if not args:
            await update.effective_message.reply_text(
                "Gebruik: /create <beschrijving van wat je wilt aanmaken>\n"
                "Voorbeeld: /create een Ubuntu 24.04 container met 2GB RAM"
            )
            return

        # Delegeer naar AI handler met context
        user_message = f"Maak aan: {' '.join(args)}"
        try:
            response = await self.bot.ai_handler.process_message(
                update, user_message
            )
            await self.bot.formatter.send_message(update, response)
        except Exception as e:
            await update.effective_message.reply_text(f"Fout: {e}")


class InstallCommand(BaseCommand):
    """Installeer software op een host."""

    name = "install"
    description = "Installeer software op een host"

    async def handle(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk het /install command.

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        args = context.args or []

        if not args:
            await update.effective_message.reply_text(
                "Gebruik: /install <wat> op <host>\n"
                "Voorbeeld: /install nginx op cl-websrv"
            )
            return

        user_message = f"Installeer: {' '.join(args)}"
        try:
            response = await self.bot.ai_handler.process_message(
                update, user_message
            )
            await self.bot.formatter.send_message(update, response)
        except Exception as e:
            await update.effective_message.reply_text(f"Fout: {e}")


class ListCommand(BaseCommand):
    """Lijst containers, VMs of services."""

    name = "list"
    description = "Toon lijst van containers/VMs/services"

    async def handle(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk het /list command.

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        args = context.args or []
        user_message = f"Toon lijst van: {' '.join(args) if args else 'alle containers en VMs'}"

        try:
            response = await self.bot.ai_handler.process_message(
                update, user_message
            )
            await self.bot.formatter.send_message(update, response)
        except Exception as e:
            await update.effective_message.reply_text(f"Fout: {e}")


class LogsCommand(BaseCommand):
    """Bekijk logs van een host of service."""

    name = "logs"
    description = "Bekijk logs van een host of service"

    async def handle(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk het /logs command.

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        args = context.args or []

        if not args:
            await update.effective_message.reply_text(
                "Gebruik: /logs <host> [service] [aantal regels]\n"
                "Voorbeeld: /logs pve-00 syslog 50"
            )
            return

        user_message = f"Toon logs: {' '.join(args)}"
        try:
            response = await self.bot.ai_handler.process_message(
                update, user_message
            )
            await self.bot.formatter.send_message(update, response)
        except Exception as e:
            await update.effective_message.reply_text(f"Fout: {e}")
