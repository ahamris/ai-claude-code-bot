"""Proxmox VE plugin.

Biedt functionaliteit voor het beheren van Proxmox containers (LXC)
en virtuele machines via SSH naar PVE hosts.
"""

import logging
from typing import Any

from telegram import Update
from telegram.ext import ContextTypes

from .base import BasePlugin

logger = logging.getLogger(__name__)


class ProxmoxPlugin(BasePlugin):
    """Plugin voor Proxmox VE beheer."""

    name = "proxmox"
    description = "Proxmox VE container en VM beheer"
    commands = [
        ("pve", "Proxmox beheer: /pve <actie> [args]"),
    ]

    def __init__(self, bot: "Any", config: dict[str, Any]) -> None:
        """Initialiseer Proxmox plugin.

        Args:
            bot: Bot instance
            config: Plugin configuratie
        """
        super().__init__(bot, config)
        self.default_host = config.get("default_host", "")
        self.default_storage = config.get("default_storage", "local-lvm")
        self.default_template = config.get("default_template", "")

    async def initialize(self) -> None:
        """Initialiseer de Proxmox plugin."""
        logger.info("Proxmox plugin geinitialiseerd.")

    async def handle_pve(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk het /pve command.

        Subcommands:
          /pve list [host]           - Lijst containers en VMs
          /pve status <vmid> [host]  - Status van een specifieke CT/VM
          /pve start <vmid> [host]   - Start een CT/VM
          /pve stop <vmid> [host]    - Stop een CT/VM
          /pve exec <vmid> <cmd>     - Voer command uit in container

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        args = context.args or []

        if not args:
            help_text = (
                "Proxmox VE beheer:\n\n"
                "/pve list [host] - Lijst containers/VMs\n"
                "/pve status <vmid> [host] - Status CT/VM\n"
                "/pve start <vmid> [host] - Start CT/VM\n"
                "/pve stop <vmid> [host] - Stop CT/VM\n"
                "/pve exec <vmid> <cmd> - Voer command uit in CT"
            )
            await update.effective_message.reply_text(help_text)
            return

        action = args[0].lower()

        if action == "list":
            await self._list_vms(update, args[1] if len(args) > 1 else None)
        elif action == "status" and len(args) > 1:
            host = args[2] if len(args) > 2 else None
            await self._vm_status(update, args[1], host)
        elif action == "start" and len(args) > 1:
            host = args[2] if len(args) > 2 else None
            await self._vm_action(update, "start", args[1], host)
        elif action == "stop" and len(args) > 1:
            host = args[2] if len(args) > 2 else None
            await self._vm_action(update, "stop", args[1], host)
        elif action == "exec" and len(args) > 2:
            vmid = args[1]
            cmd = " ".join(args[2:])
            await self._exec_in_container(update, vmid, cmd)
        else:
            await update.effective_message.reply_text(
                f"Onbekende actie of ontbrekende argumenten: {action}\n"
                "Gebruik /pve zonder argumenten voor hulp."
            )

    async def _list_vms(
        self, update: Update, host_name: str | None = None
    ) -> None:
        """Lijst alle containers en VMs op een PVE host.

        Args:
            update: Telegram Update object
            host_name: Specifieke host, of alle hosts als None
        """
        hosts_to_check: list[tuple[str, str, str | None]] = []

        if host_name:
            address, jump = self.get_host_info(host_name)
            if address:
                hosts_to_check.append((host_name, address, jump))
            else:
                await update.effective_message.reply_text(
                    f"Host '{host_name}' niet gevonden in SSH configuratie."
                )
                return
        else:
            # Alle SSH hosts
            ssh_hosts = self.bot.config.get("ssh", {}).get("hosts", {})
            for name, info in ssh_hosts.items():
                address, jump = self.get_host_info(name)
                if address:
                    hosts_to_check.append((name, address, jump))

        if not hosts_to_check:
            await update.effective_message.reply_text(
                "Geen PVE hosts geconfigureerd."
            )
            return

        lines: list[str] = []

        for name, address, jump in hosts_to_check:
            # Haal container lijst op
            stdout_ct, _, rc_ct = await self.run_ssh_command(
                address, "pct list 2>/dev/null || echo 'geen LXC support'", jump
            )
            # Haal VM lijst op
            stdout_vm, _, rc_vm = await self.run_ssh_command(
                address, "qm list 2>/dev/null || echo 'geen QEMU support'", jump
            )

            lines.append(f"=== {name} ({address}) ===")

            if stdout_ct.strip() and "geen" not in stdout_ct:
                lines.append("\nContainers (LXC):")
                lines.append(stdout_ct.strip())

            if stdout_vm.strip() and "geen" not in stdout_vm:
                lines.append("\nVirtuele Machines:")
                lines.append(stdout_vm.strip())

            if not stdout_ct.strip() and not stdout_vm.strip():
                lines.append("  Geen containers of VMs gevonden.")

            lines.append("")

        await self.bot.formatter.send_message(update, "\n".join(lines))

    async def _vm_status(
        self,
        update: Update,
        vmid: str,
        host_name: str | None = None,
    ) -> None:
        """Haal de status op van een specifieke CT/VM.

        Args:
            update: Telegram Update object
            vmid: VM/container ID
            host_name: PVE hostnaam
        """
        host_name = host_name or self._get_default_host()
        if not host_name:
            await update.effective_message.reply_text(
                "Geef een hostnaam op of configureer een default host."
            )
            return

        address, jump = self.get_host_info(host_name)

        # Probeer eerst als container, dan als VM
        stdout, stderr, rc = await self.run_ssh_command(
            address,
            f"pct status {vmid} 2>/dev/null || qm status {vmid} 2>/dev/null || echo 'Niet gevonden'",
            jump,
        )

        # Haal ook config op
        stdout_cfg, _, _ = await self.run_ssh_command(
            address,
            f"pct config {vmid} 2>/dev/null || qm config {vmid} 2>/dev/null",
            jump,
        )

        lines: list[str] = []
        lines.append(f"Status VM/CT {vmid} op {host_name}:")
        lines.append(stdout.strip())

        if stdout_cfg.strip():
            # Toon alleen relevante config regels
            relevant_keys = ["hostname", "memory", "cores", "net", "rootfs", "name"]
            for line in stdout_cfg.strip().split("\n"):
                key = line.split(":")[0].strip() if ":" in line else ""
                if any(k in key.lower() for k in relevant_keys):
                    lines.append(f"  {line.strip()}")

        await self.bot.formatter.send_message(update, "\n".join(lines))

    async def _vm_action(
        self,
        update: Update,
        action: str,
        vmid: str,
        host_name: str | None = None,
    ) -> None:
        """Start of stop een CT/VM.

        Args:
            update: Telegram Update object
            action: 'start' of 'stop'
            vmid: VM/container ID
            host_name: PVE hostnaam
        """
        host_name = host_name or self._get_default_host()
        if not host_name:
            await update.effective_message.reply_text(
                "Geef een hostnaam op of configureer een default host."
            )
            return

        address, jump = self.get_host_info(host_name)

        # Probeer eerst als container, dan als VM
        cmd = f"pct {action} {vmid} 2>/dev/null || qm {action} {vmid} 2>/dev/null"
        stdout, stderr, rc = await self.run_ssh_command(address, cmd, jump)

        if rc == 0:
            emoji_ok = "\u2705"
            await update.effective_message.reply_text(
                f"{emoji_ok} VM/CT {vmid} is ge{action}."
            )
        else:
            emoji_fail = "\u274c"
            error = stderr.strip() or stdout.strip() or "onbekende fout"
            await update.effective_message.reply_text(
                f"{emoji_fail} Kon VM/CT {vmid} niet {action}en: {error}"
            )

    async def _exec_in_container(
        self,
        update: Update,
        vmid: str,
        command: str,
    ) -> None:
        """Voer een command uit in een LXC container.

        Args:
            update: Telegram Update object
            vmid: Container ID
            command: Uit te voeren command
        """
        host_name = self._get_default_host()
        if not host_name:
            await update.effective_message.reply_text(
                "Configureer een default PVE host."
            )
            return

        address, jump = self.get_host_info(host_name)

        stdout, stderr, rc = await self.run_ssh_command(
            address, f"pct exec {vmid} -- {command}", jump, timeout=60
        )

        result = stdout.strip() or stderr.strip() or "(geen output)"

        # Beperk output lengte
        if len(result) > 3000:
            result = result[:3000] + "\n...(afgekapt)"

        await self.bot.formatter.send_message(update, result)

    def _get_default_host(self) -> str | None:
        """Geef de standaard PVE host terug.

        Returns:
            Hostnaam of None
        """
        if self.default_host:
            return self.default_host

        # Pak de eerste SSH host
        ssh_hosts = self.bot.config.get("ssh", {}).get("hosts", {})
        if ssh_hosts:
            return next(iter(ssh_hosts))

        return None
