"""MikroTik RouterOS plugin.

Biedt functionaliteit voor het beheren van MikroTik routers via SSH.
LET OP: ether1 is altijd de uplink - nooit isoleren!
"""

import logging
from typing import Any

from telegram import Update
from telegram.ext import ContextTypes

from .base import BasePlugin

logger = logging.getLogger(__name__)

# Gevaarlijke commands die niet via de bot uitgevoerd mogen worden
BLOCKED_COMMANDS = [
    "system reset",
    "system routerboard",
    "/interface disable ether1",
    "interface disable ether1",
    "/ip address remove",
    "/system identity set",
]


class MikroTikPlugin(BasePlugin):
    """Plugin voor MikroTik RouterOS beheer via SSH."""

    name = "mikrotik"
    description = "MikroTik router beheer"
    commands = [
        ("mikrotik", "MikroTik beheer: /mikrotik <actie> [args]"),
    ]

    def __init__(self, bot: "Any", config: dict[str, Any]) -> None:
        """Initialiseer MikroTik plugin.

        Args:
            bot: Bot instance
            config: Plugin configuratie
        """
        super().__init__(bot, config)
        self.router_host = config.get("host", "")
        self.router_user = config.get("user", "admin")
        self.ssh_port = config.get("port", 22)

    async def initialize(self) -> None:
        """Initialiseer de MikroTik plugin."""
        if not self.router_host:
            logger.warning("MikroTik plugin: geen router host geconfigureerd.")
        else:
            logger.info(f"MikroTik plugin geinitialiseerd voor {self.router_host}.")

    async def handle_mikrotik(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk het /mikrotik command.

        Subcommands:
          /mikrotik status          - Router status
          /mikrotik interfaces      - Interface overzicht
          /mikrotik dhcp            - DHCP leases
          /mikrotik firewall        - Firewall regels
          /mikrotik exec <command>  - Voer RouterOS command uit

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        if not self.router_host:
            await update.effective_message.reply_text(
                "MikroTik router host is niet geconfigureerd."
            )
            return

        args = context.args or []

        if not args:
            help_text = (
                "MikroTik router beheer:\n\n"
                "/mikrotik status - Router status\n"
                "/mikrotik interfaces - Interface overzicht\n"
                "/mikrotik dhcp - DHCP leases\n"
                "/mikrotik firewall - Firewall regels samenvatting\n"
                "/mikrotik exec <cmd> - RouterOS command uitvoeren"
            )
            await update.effective_message.reply_text(help_text)
            return

        action = args[0].lower()

        if action == "status":
            await self._show_status(update)
        elif action == "interfaces":
            await self._show_interfaces(update)
        elif action == "dhcp":
            await self._show_dhcp(update)
        elif action == "firewall":
            await self._show_firewall(update)
        elif action == "exec" and len(args) > 1:
            cmd = " ".join(args[1:])
            await self._exec_command(update, cmd)
        else:
            await update.effective_message.reply_text(
                "Onbekende actie. Gebruik /mikrotik zonder argumenten voor hulp."
            )

    async def _run_routeros_command(self, command: str) -> tuple[str, str, int]:
        """Voer een RouterOS command uit via SSH.

        Args:
            command: RouterOS command

        Returns:
            Tuple van (stdout, stderr, return_code)
        """
        import asyncio

        cmd_parts = [
            "ssh",
            "-o", "ConnectTimeout=5",
            "-o", "StrictHostKeyChecking=no",
            "-p", str(self.ssh_port),
            f"{self.router_user}@{self.router_host}",
            command,
        ]

        try:
            proc = await asyncio.create_subprocess_exec(
                *cmd_parts,
                stdout=asyncio.subprocess.PIPE,
                stderr=asyncio.subprocess.PIPE,
            )
            stdout, stderr = await asyncio.wait_for(
                proc.communicate(), timeout=15
            )
            return (
                stdout.decode("utf-8", errors="replace"),
                stderr.decode("utf-8", errors="replace"),
                proc.returncode or 0,
            )
        except asyncio.TimeoutError:
            return ("", "SSH timeout", 1)
        except OSError as e:
            return ("", str(e), 1)

    async def _show_status(self, update: Update) -> None:
        """Toon router status informatie.

        Args:
            update: Telegram Update object
        """
        commands = {
            "Identity": "/system identity print",
            "Resources": "/system resource print",
            "Uptime": '/system resource print where name="uptime"',
        }

        lines: list[str] = []
        emoji_router = "\U0001f4e1"  # satelliet
        lines.append(f"{emoji_router} MikroTik Router Status\n")

        # Haal resource info op
        stdout, stderr, rc = await self._run_routeros_command(
            "/system resource print"
        )
        if rc == 0 and stdout:
            lines.append(stdout.strip())
        else:
            lines.append(f"Fout: {stderr.strip() or 'geen verbinding'}")

        await self.bot.formatter.send_message(update, "\n".join(lines))

    async def _show_interfaces(self, update: Update) -> None:
        """Toon interface overzicht.

        Args:
            update: Telegram Update object
        """
        stdout, stderr, rc = await self._run_routeros_command(
            "/interface print"
        )

        if rc == 0 and stdout:
            await self.bot.formatter.send_message(
                update, f"Interfaces:\n\n{stdout.strip()}"
            )
        else:
            await update.effective_message.reply_text(
                f"Fout bij ophalen interfaces: {stderr.strip()}"
            )

    async def _show_dhcp(self, update: Update) -> None:
        """Toon DHCP leases.

        Args:
            update: Telegram Update object
        """
        stdout, stderr, rc = await self._run_routeros_command(
            "/ip dhcp-server lease print"
        )

        if rc == 0 and stdout:
            result = f"DHCP Leases:\n\n{stdout.strip()}"
            if len(result) > 3500:
                result = result[:3500] + "\n...(afgekapt)"
            await self.bot.formatter.send_message(update, result)
        else:
            await update.effective_message.reply_text(
                f"Fout bij ophalen DHCP leases: {stderr.strip()}"
            )

    async def _show_firewall(self, update: Update) -> None:
        """Toon firewall regels samenvatting.

        Args:
            update: Telegram Update object
        """
        stdout, stderr, rc = await self._run_routeros_command(
            "/ip firewall filter print count-only"
        )

        lines: list[str] = []
        emoji_shield = "\U0001f6e1\ufe0f"
        lines.append(f"{emoji_shield} Firewall Samenvatting\n")

        if rc == 0:
            lines.append(f"Filter regels: {stdout.strip()}")

        # NAT regels
        stdout_nat, _, rc_nat = await self._run_routeros_command(
            "/ip firewall nat print count-only"
        )
        if rc_nat == 0:
            lines.append(f"NAT regels: {stdout_nat.strip()}")

        # Mangle regels
        stdout_mangle, _, rc_mangle = await self._run_routeros_command(
            "/ip firewall mangle print count-only"
        )
        if rc_mangle == 0:
            lines.append(f"Mangle regels: {stdout_mangle.strip()}")

        await self.bot.formatter.send_message(update, "\n".join(lines))

    async def _exec_command(self, update: Update, command: str) -> None:
        """Voer een willekeurig RouterOS command uit.

        Bevat veiligheidscontroles om gevaarlijke commands te blokkeren.

        Args:
            update: Telegram Update object
            command: RouterOS command
        """
        # Veiligheidscontrole
        cmd_lower = command.lower().strip()
        for blocked in BLOCKED_COMMANDS:
            if blocked.lower() in cmd_lower:
                emoji_warn = "\u26a0\ufe0f"
                await update.effective_message.reply_text(
                    f"{emoji_warn} Dit command is geblokkeerd vanwege veiligheidsredenen: {blocked}"
                )
                return

        # Extra check: bescherm ether1 (uplink)
        if "ether1" in cmd_lower and ("disable" in cmd_lower or "remove" in cmd_lower):
            emoji_warn = "\u26a0\ufe0f"
            await update.effective_message.reply_text(
                f"{emoji_warn} GEBLOKKEERD: ether1 is de uplink interface en mag niet "
                "uitgeschakeld of verwijderd worden!"
            )
            return

        stdout, stderr, rc = await self._run_routeros_command(command)

        if rc == 0:
            result = stdout.strip() or "(geen output)"
            if len(result) > 3500:
                result = result[:3500] + "\n...(afgekapt)"
            await self.bot.formatter.send_message(update, result)
        else:
            await update.effective_message.reply_text(
                f"Fout bij uitvoeren command: {stderr.strip() or 'onbekende fout'}"
            )
