"""Status command - toont systeem- en botstatus."""

import logging
from datetime import datetime

from telegram import Update
from telegram.ext import ContextTypes

from .base import BaseCommand

logger = logging.getLogger(__name__)


class StatusCommand(BaseCommand):
    """Toont de huidige status van de bot en gemonitorde systemen."""

    name = "status"
    description = "Toon systeem- en botstatus"

    async def handle(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk het /status command.

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        lines: list[str] = []
        emoji_bot = "\U0001f916"  # robot
        emoji_clock = "\U0001f552"  # klok

        lines.append(f"{emoji_bot} Bot: {self.bot.name}")
        lines.append(f"{emoji_clock} Tijd: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")

        # Geheugen status
        if self.bot.memory and self.bot.memory.enabled:
            topics = self.bot.memory.get_knowledge_topics()
            convs = self.bot.memory.get_recent_conversations(n=1)
            lines.append(f"\nGeheugen:")
            lines.append(f"  Kennisonderwerpen: {len(topics)}")
            if convs:
                lines.append(f"  Laatste gesprek: {convs[0].get('timestamp', 'onbekend')}")
        else:
            lines.append("\nGeheugen: uitgeschakeld")

        # Monitoring status
        if self.bot.monitoring and self.bot.monitoring.enabled:
            lines.append("\nMonitoring: actief")
            results = await self.bot.monitoring.health_check()
            report = self.bot.monitoring.format_status_report(results)
            lines.append(report)
        else:
            lines.append("\nMonitoring: uitgeschakeld")

        # Plugins
        if self.bot.plugins:
            plugin_names = [p.__class__.__name__ for p in self.bot.plugins]
            lines.append(f"\nPlugins: {', '.join(plugin_names)}")
        else:
            lines.append("\nPlugins: geen")

        await self.bot.formatter.send_message(update, "\n".join(lines))
