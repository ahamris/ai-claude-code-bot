"""Memory commands - /memory, /learn, /forget.

Beheer de kennisbank en het geheugen van de bot.
"""

import logging

from telegram import Update
from telegram.ext import ContextTypes

from .base import BaseCommand

logger = logging.getLogger(__name__)


class MemoryCommand(BaseCommand):
    """Toon de inhoud van het geheugen."""

    name = "memory"
    description = "Toon geheugen en kennisbank"

    async def handle(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk het /memory command.

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        if not self.bot.memory or not self.bot.memory.enabled:
            await update.effective_message.reply_text(
                "Geheugen is niet geconfigureerd of uitgeschakeld."
            )
            return

        lines: list[str] = []
        emoji_brain = "\U0001f9e0"  # brein

        # Kennisonderwerpen
        topics = self.bot.memory.get_knowledge_topics()
        lines.append(f"{emoji_brain} Kennisbank ({len(topics)} onderwerpen):\n")

        if topics:
            for topic in topics:
                lines.append(f"  - {topic}")
        else:
            lines.append("  (leeg)")

        # Recente gesprekken
        convs = self.bot.memory.get_recent_conversations(n=5)
        lines.append(f"\nLaatste {len(convs)} gesprekken:")

        if convs:
            for conv in convs:
                ts = conv.get("timestamp", "?")
                msg = conv.get("user_message", "")
                # Verkort lange berichten
                if len(msg) > 60:
                    msg = msg[:60] + "..."
                lines.append(f"  [{ts[:16]}] {msg}")
        else:
            lines.append("  (geen gesprekken)")

        await self.bot.formatter.send_message(update, "\n".join(lines))


class LearnCommand(BaseCommand):
    """Sla nieuwe kennis op in de kennisbank."""

    name = "learn"
    description = "Leer nieuwe kennis: /learn <onderwerp> <inhoud>"

    async def handle(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk het /learn command.

        Verwacht: /learn <onderwerp> | <inhoud>
        Of: /learn <onderwerp> <inhoud op volgende regel>

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        if not self.bot.memory or not self.bot.memory.enabled:
            await update.effective_message.reply_text(
                "Geheugen is niet geconfigureerd of uitgeschakeld."
            )
            return

        text = update.effective_message.text or ""
        # Verwijder /learn prefix
        content = text.split(None, 1)[1] if len(text.split(None, 1)) > 1 else ""

        if not content:
            await update.effective_message.reply_text(
                "Gebruik: /learn <onderwerp> | <inhoud>\n"
                "Voorbeeld: /learn pve-00 | Proxmox host, 64GB RAM, 2x NVMe"
            )
            return

        # Split op | als die er is, anders eerste woord = onderwerp
        if "|" in content:
            parts = content.split("|", 1)
            topic = parts[0].strip()
            knowledge = parts[1].strip()
        else:
            words = content.split(None, 1)
            topic = words[0]
            knowledge = words[1] if len(words) > 1 else ""

        if not knowledge:
            await update.effective_message.reply_text(
                "Geef ook inhoud op na het onderwerp.\n"
                "Voorbeeld: /learn pve-00 | Proxmox host, 64GB RAM"
            )
            return

        self.bot.memory.save_knowledge(topic, knowledge)
        emoji_ok = "\u2705"
        await update.effective_message.reply_text(
            f"{emoji_ok} Kennis opgeslagen over: {topic}"
        )


class ForgetCommand(BaseCommand):
    """Verwijder kennis uit de kennisbank."""

    name = "forget"
    description = "Vergeet kennis: /forget <onderwerp>"

    async def handle(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk het /forget command.

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        if not self.bot.memory or not self.bot.memory.enabled:
            await update.effective_message.reply_text(
                "Geheugen is niet geconfigureerd of uitgeschakeld."
            )
            return

        args = context.args or []

        if not args:
            await update.effective_message.reply_text(
                "Gebruik: /forget <onderwerp>\n"
                "Gebruik /memory om alle onderwerpen te zien."
            )
            return

        topic = " ".join(args)

        if self.bot.memory.forget(topic):
            emoji_ok = "\u2705"
            await update.effective_message.reply_text(
                f"{emoji_ok} Kennis over '{topic}' verwijderd."
            )
        else:
            await update.effective_message.reply_text(
                f"Onderwerp '{topic}' niet gevonden in de kennisbank."
            )
