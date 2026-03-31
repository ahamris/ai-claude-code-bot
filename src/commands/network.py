"""Network commands - /ping, /scan, /devices."""

import asyncio
import logging

from telegram import Update
from telegram.ext import ContextTypes

from .base import BaseCommand

logger = logging.getLogger(__name__)


class PingCommand(BaseCommand):
    """Ping een host of IP-adres."""

    name = "ping"
    description = "Ping een host: /ping <host/ip>"

    async def handle(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk het /ping command.

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        args = context.args or []

        if not args:
            await update.effective_message.reply_text(
                "Gebruik: /ping <host of ip-adres>"
            )
            return

        target = args[0]

        try:
            proc = await asyncio.create_subprocess_exec(
                "ping", "-c", "3", "-W", "3", target,
                stdout=asyncio.subprocess.PIPE,
                stderr=asyncio.subprocess.PIPE,
            )
            stdout, stderr = await asyncio.wait_for(
                proc.communicate(), timeout=15
            )

            output = stdout.decode("utf-8", errors="replace")

            if proc.returncode == 0:
                # Extraheer de samenvatting (laatste regels)
                lines = output.strip().split("\n")
                # Neem de statistiek regels
                summary_lines = [l for l in lines if "packet" in l or "rtt" in l or "round-trip" in l]
                emoji_ok = "\u2705"
                result = f"{emoji_ok} Ping naar {target}:\n"
                result += "\n".join(summary_lines) if summary_lines else output[-200:]
            else:
                emoji_fail = "\u274c"
                result = f"{emoji_fail} {target} is niet bereikbaar"

            await update.effective_message.reply_text(result)

        except asyncio.TimeoutError:
            await update.effective_message.reply_text(
                f"Ping naar {target} is getimed-out."
            )
        except OSError as e:
            await update.effective_message.reply_text(f"Fout: {e}")


class ScanCommand(BaseCommand):
    """Scan een netwerk of host op open poorten."""

    name = "scan"
    description = "Scan poorten: /scan <host> [poorten]"

    async def handle(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk het /scan command.

        Voert een eenvoudige TCP port scan uit.

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        args = context.args or []

        if not args:
            await update.effective_message.reply_text(
                "Gebruik: /scan <host> [poort1,poort2,...]\n"
                "Standaard poorten: 22,80,443,8006,8080,8443"
            )
            return

        target = args[0]
        default_ports = [22, 80, 443, 8006, 8080, 8443]

        if len(args) > 1:
            try:
                ports = [int(p.strip()) for p in args[1].split(",")]
            except ValueError:
                await update.effective_message.reply_text(
                    "Ongeldige poorten. Gebruik komma-gescheiden nummers."
                )
                return
        else:
            ports = default_ports

        await update.effective_message.reply_text(
            f"Scan gestart op {target}..."
        )

        results: list[str] = []
        emoji_open = "\U0001f7e2"  # groene cirkel
        emoji_closed = "\U0001f534"  # rode cirkel

        for port in ports:
            try:
                _, writer = await asyncio.wait_for(
                    asyncio.open_connection(target, port),
                    timeout=3,
                )
                writer.close()
                await writer.wait_closed()
                results.append(f"{emoji_open} {port}/tcp open")
            except (asyncio.TimeoutError, OSError):
                results.append(f"{emoji_closed} {port}/tcp gesloten")

        header = f"Scan resultaten voor {target}:\n"
        await self.bot.formatter.send_message(
            update, header + "\n".join(results)
        )


class DevicesCommand(BaseCommand):
    """Toon bekende apparaten uit de monitoring configuratie."""

    name = "devices"
    description = "Toon alle bekende apparaten"

    async def handle(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk het /devices command.

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        if not self.bot.monitoring:
            await update.effective_message.reply_text(
                "Monitoring is niet geconfigureerd."
            )
            return

        lines: list[str] = []
        emoji_server = "\U0001f5a5\ufe0f"  # desktop computer

        lines.append(f"{emoji_server} Bekende apparaten:\n")

        hosts = self.bot.monitoring.hosts
        if not hosts:
            lines.append("Geen hosts geconfigureerd.")
        else:
            for group_name, group_hosts in hosts.items():
                lines.append(f"[{group_name}]")
                if isinstance(group_hosts, dict):
                    for name, address in group_hosts.items():
                        lines.append(f"  {name}: {address}")
                lines.append("")

        # SSH hosts
        ssh_hosts = self.bot.monitoring.ssh_hosts
        if ssh_hosts:
            lines.append("[SSH toegang]")
            for name, info in ssh_hosts.items():
                if isinstance(info, str):
                    lines.append(f"  {name}: {info}")
                elif isinstance(info, dict):
                    addr = info.get("host", "?")
                    jump = info.get("jump")
                    extra = f" (via {jump})" if jump else ""
                    lines.append(f"  {name}: {addr}{extra}")

        await self.bot.formatter.send_message(update, "\n".join(lines))
