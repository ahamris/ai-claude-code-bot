"""Security command - voert security scans uit."""

import logging

from telegram import Update
from telegram.ext import ContextTypes

from .base import BaseCommand

logger = logging.getLogger(__name__)


class SecurityCommand(BaseCommand):
    """Voer een security scan uit op gemonitorde hosts."""

    name = "security"
    description = "Voer security scan uit op alle hosts"

    async def handle(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk het /security command.

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        if not self.bot.monitoring or not self.bot.monitoring.enabled:
            await update.effective_message.reply_text(
                "Monitoring is niet geconfigureerd of uitgeschakeld."
            )
            return

        await update.effective_message.reply_text(
            "Security scan gestart, even geduld..."
        )

        try:
            results = await self.bot.monitoring.security_scan()

            if not results:
                await update.effective_message.reply_text(
                    "Geen hosts geconfigureerd voor security scans."
                )
                return

            lines: list[str] = []
            emoji_shield = "\U0001f6e1\ufe0f"  # schild
            emoji_ok = "\u2705"
            emoji_warn = "\u26a0\ufe0f"

            lines.append(f"{emoji_shield} Security Scan Rapport\n")

            for result in results:
                if result.findings:
                    lines.append(f"{emoji_warn} {result.name} ({result.address}):")
                    for finding in result.findings:
                        lines.append(f"  - {finding}")
                else:
                    lines.append(
                        f"{emoji_ok} {result.name} ({result.address}): geen bevindingen"
                    )
                lines.append("")

            await self.bot.formatter.send_message(update, "\n".join(lines))

        except Exception as e:
            await update.effective_message.reply_text(
                f"Fout bij security scan: {e}"
            )
