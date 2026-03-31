"""Monitoring module voor health checks, security scans en alerts.

Voert periodieke controles uit op hosts en services en stuurt
waarschuwingen via Telegram.
"""

import asyncio
import logging
import subprocess
from dataclasses import dataclass, field
from datetime import datetime
from typing import Any, Optional

from telegram import Bot

logger = logging.getLogger(__name__)


@dataclass
class HostStatus:
    """Status van een host na een check."""

    name: str
    address: str
    reachable: bool
    latency_ms: Optional[float] = None
    last_check: str = ""
    details: str = ""


@dataclass
class SecurityResult:
    """Resultaat van een security scan op een host."""

    name: str
    address: str
    findings: list[str] = field(default_factory=list)
    last_scan: str = ""


@dataclass
class WebServiceStatus:
    """Status van een webservice."""

    name: str
    host: str
    port: int
    reachable: bool
    last_check: str = ""


class Monitoring:
    """Monitoring systeem voor hosts, services en beveiliging."""

    def __init__(
        self,
        config: dict[str, Any],
        ssh_config: Optional[dict[str, Any]] = None,
    ) -> None:
        """Initialiseer monitoring.

        Args:
            config: Monitoring configuratie uit YAML
            ssh_config: SSH configuratie voor remote checks
        """
        self.enabled = config.get("enabled", False)
        self.health_check_interval = config.get("health_check_interval", 300)
        self.security_scan_interval = config.get("security_scan_interval", 900)
        self.hosts = config.get("hosts", {})
        self.web_services = config.get("web_services", {})
        self.ssh_user = "root"
        self.ssh_hosts: dict[str, Any] = {}

        if ssh_config:
            self.ssh_user = ssh_config.get("user", "root")
            self.ssh_hosts = ssh_config.get("hosts", {})

        self._health_task: Optional[asyncio.Task] = None
        self._security_task: Optional[asyncio.Task] = None
        self._previous_statuses: dict[str, bool] = {}

    async def start(self, bot: Bot, chat_ids: list[int]) -> None:
        """Start de monitoring taken.

        Args:
            bot: Telegram Bot instance
            chat_ids: Lijst van chat IDs voor alerts
        """
        if not self.enabled:
            logger.info("Monitoring is uitgeschakeld.")
            return

        logger.info(
            f"Monitoring gestart: health check elke {self.health_check_interval}s, "
            f"security scan elke {self.security_scan_interval}s"
        )

        self._health_task = asyncio.create_task(
            self._health_check_loop(bot, chat_ids)
        )
        self._security_task = asyncio.create_task(
            self._security_scan_loop(bot, chat_ids)
        )

    async def stop(self) -> None:
        """Stop alle monitoring taken."""
        for task in (self._health_task, self._security_task):
            if task and not task.done():
                task.cancel()
                try:
                    await task
                except asyncio.CancelledError:
                    pass
        logger.info("Monitoring gestopt.")

    async def _health_check_loop(self, bot: Bot, chat_ids: list[int]) -> None:
        """Periodieke health check loop.

        Args:
            bot: Telegram Bot instance
            chat_ids: Lijst van chat IDs voor alerts
        """
        try:
            while True:
                results = await self.health_check()
                await self._process_health_results(bot, chat_ids, results)
                await asyncio.sleep(self.health_check_interval)
        except asyncio.CancelledError:
            pass
        except Exception as e:
            logger.error(f"Fout in health check loop: {e}")

    async def _security_scan_loop(self, bot: Bot, chat_ids: list[int]) -> None:
        """Periodieke security scan loop.

        Args:
            bot: Telegram Bot instance
            chat_ids: Lijst van chat IDs voor alerts
        """
        try:
            # Wacht even voor de eerste scan
            await asyncio.sleep(60)
            while True:
                results = await self.security_scan()
                await self._process_security_results(bot, chat_ids, results)
                await asyncio.sleep(self.security_scan_interval)
        except asyncio.CancelledError:
            pass
        except Exception as e:
            logger.error(f"Fout in security scan loop: {e}")

    async def health_check(self) -> list[HostStatus]:
        """Voer een health check uit op alle geconfigureerde hosts.

        Returns:
            Lijst van HostStatus resultaten
        """
        results: list[HostStatus] = []

        # Verwerk alle host-groepen (mgmt, dmz, remote, etc.)
        for group_name, group_hosts in self.hosts.items():
            if not isinstance(group_hosts, dict):
                continue

            for host_name, address in group_hosts.items():
                status = await self._ping_host(host_name, address)
                results.append(status)

        # Check webservices
        for service_name, service_info in self.web_services.items():
            if isinstance(service_info, list) and len(service_info) >= 2:
                host, port = service_info[0], service_info[1]
                ws_status = await self._check_port(service_name, host, port)
                results.append(
                    HostStatus(
                        name=f"{service_name} (:{port})",
                        address=host,
                        reachable=ws_status.reachable,
                        last_check=ws_status.last_check,
                    )
                )

        return results

    async def security_scan(self) -> list[SecurityResult]:
        """Voer een security scan uit op bereikbare hosts via SSH.

        Controleert auth.log en fail2ban status.

        Returns:
            Lijst van SecurityResult objecten
        """
        results: list[SecurityResult] = []

        for host_name, host_info in self.ssh_hosts.items():
            # Bepaal host adres en eventuele jump host
            if isinstance(host_info, str):
                address = host_info
                jump_host = None
            elif isinstance(host_info, dict):
                address = host_info.get("host", "")
                jump_host = host_info.get("jump")
            else:
                continue

            result = await self._scan_host_security(
                host_name, address, jump_host
            )
            results.append(result)

        return results

    async def _ping_host(self, name: str, address: str) -> HostStatus:
        """Ping een host en geef de status terug.

        Args:
            name: Hostnaam
            address: IP-adres of hostname

        Returns:
            HostStatus object
        """
        now = datetime.now().isoformat()

        try:
            proc = await asyncio.create_subprocess_exec(
                "ping", "-c", "1", "-W", "3", address,
                stdout=asyncio.subprocess.PIPE,
                stderr=asyncio.subprocess.PIPE,
            )
            stdout, _ = await asyncio.wait_for(proc.communicate(), timeout=5)

            if proc.returncode == 0:
                output = stdout.decode("utf-8", errors="replace")
                # Probeer latency te extraheren
                latency = None
                for line in output.split("\n"):
                    if "time=" in line:
                        try:
                            time_part = line.split("time=")[1]
                            latency = float(time_part.split()[0])
                        except (IndexError, ValueError):
                            pass
                        break

                return HostStatus(
                    name=name,
                    address=address,
                    reachable=True,
                    latency_ms=latency,
                    last_check=now,
                )
            else:
                return HostStatus(
                    name=name,
                    address=address,
                    reachable=False,
                    last_check=now,
                    details="Ping mislukt",
                )

        except (asyncio.TimeoutError, OSError) as e:
            return HostStatus(
                name=name,
                address=address,
                reachable=False,
                last_check=now,
                details=str(e),
            )

    async def _check_port(
        self, name: str, host: str, port: int
    ) -> WebServiceStatus:
        """Controleer of een TCP poort bereikbaar is.

        Args:
            name: Service naam
            host: IP-adres of hostname
            port: Poortnummer

        Returns:
            WebServiceStatus object
        """
        now = datetime.now().isoformat()

        try:
            _, writer = await asyncio.wait_for(
                asyncio.open_connection(host, port),
                timeout=5,
            )
            writer.close()
            await writer.wait_closed()
            return WebServiceStatus(
                name=name, host=host, port=port, reachable=True, last_check=now
            )
        except (asyncio.TimeoutError, OSError):
            return WebServiceStatus(
                name=name, host=host, port=port, reachable=False, last_check=now
            )

    async def _scan_host_security(
        self,
        name: str,
        address: str,
        jump_host: Optional[str] = None,
    ) -> SecurityResult:
        """Scan een host op security issues via SSH.

        Args:
            name: Hostnaam
            address: IP-adres
            jump_host: Optionele jump host voor SSH

        Returns:
            SecurityResult object
        """
        now = datetime.now().isoformat()
        findings: list[str] = []

        # Bouw SSH commando
        ssh_base = ["ssh", "-o", "ConnectTimeout=5", "-o", "StrictHostKeyChecking=no"]
        if jump_host:
            ssh_base.extend(["-J", f"{self.ssh_user}@{jump_host}"])
        ssh_base.append(f"{self.ssh_user}@{address}")

        # Check 1: Recente mislukte logins
        try:
            cmd_failed = ssh_base + [
                "grep -c 'Failed password' /var/log/auth.log 2>/dev/null || echo 0"
            ]
            proc = await asyncio.create_subprocess_exec(
                *cmd_failed,
                stdout=asyncio.subprocess.PIPE,
                stderr=asyncio.subprocess.PIPE,
            )
            stdout, _ = await asyncio.wait_for(proc.communicate(), timeout=10)
            count = stdout.decode().strip()
            if count.isdigit() and int(count) > 0:
                findings.append(f"Mislukte login pogingen: {count}")
        except (asyncio.TimeoutError, OSError) as e:
            findings.append(f"SSH check mislukt: {e}")

        # Check 2: fail2ban status
        try:
            cmd_f2b = ssh_base + [
                "fail2ban-client status 2>/dev/null || echo 'fail2ban niet actief'"
            ]
            proc = await asyncio.create_subprocess_exec(
                *cmd_f2b,
                stdout=asyncio.subprocess.PIPE,
                stderr=asyncio.subprocess.PIPE,
            )
            stdout, _ = await asyncio.wait_for(proc.communicate(), timeout=10)
            output = stdout.decode().strip()
            if "niet actief" in output or "not found" in output.lower():
                findings.append("fail2ban is niet actief")
        except (asyncio.TimeoutError, OSError):
            pass

        # Check 3: Updates beschikbaar
        try:
            cmd_updates = ssh_base + [
                "apt list --upgradable 2>/dev/null | grep -c upgradable || echo 0"
            ]
            proc = await asyncio.create_subprocess_exec(
                *cmd_updates,
                stdout=asyncio.subprocess.PIPE,
                stderr=asyncio.subprocess.PIPE,
            )
            stdout, _ = await asyncio.wait_for(proc.communicate(), timeout=15)
            count = stdout.decode().strip()
            if count.isdigit() and int(count) > 5:
                findings.append(f"Updates beschikbaar: {count} pakketten")
        except (asyncio.TimeoutError, OSError):
            pass

        return SecurityResult(
            name=name,
            address=address,
            findings=findings,
            last_scan=now,
        )

    async def _process_health_results(
        self,
        bot: Bot,
        chat_ids: list[int],
        results: list[HostStatus],
    ) -> None:
        """Verwerk health check resultaten en stuur alerts bij wijzigingen.

        Args:
            bot: Telegram Bot instance
            chat_ids: Lijst van chat IDs
            results: Health check resultaten
        """
        for status in results:
            key = f"{status.name}_{status.address}"
            was_reachable = self._previous_statuses.get(key)

            # Alleen alert bij statuswijziging
            if was_reachable is not None and was_reachable != status.reachable:
                emoji_up = "\u2705"  # groen vinkje
                emoji_down = "\u274c"  # rood kruis
                if status.reachable:
                    msg = f"{emoji_up} {status.name} ({status.address}) is weer bereikbaar"
                else:
                    msg = f"{emoji_down} {status.name} ({status.address}) is NIET bereikbaar"

                await self.alert(bot, chat_ids, msg)

            self._previous_statuses[key] = status.reachable

    async def _process_security_results(
        self,
        bot: Bot,
        chat_ids: list[int],
        results: list[SecurityResult],
    ) -> None:
        """Verwerk security scan resultaten en stuur alerts bij findings.

        Args:
            bot: Telegram Bot instance
            chat_ids: Lijst van chat IDs
            results: Security scan resultaten
        """
        for result in results:
            if result.findings:
                emoji_warn = "\u26a0\ufe0f"  # waarschuwing
                msg_parts = [f"{emoji_warn} Security bevindingen voor {result.name}:"]
                for finding in result.findings:
                    msg_parts.append(f"  - {finding}")
                await self.alert(bot, chat_ids, "\n".join(msg_parts))

    @staticmethod
    async def alert(bot: Bot, chat_ids: list[int], message: str) -> None:
        """Stuur een alert bericht naar alle geconfigureerde chat IDs.

        Args:
            bot: Telegram Bot instance
            chat_ids: Lijst van chat IDs
            message: Alert bericht
        """
        for chat_id in chat_ids:
            try:
                await bot.send_message(chat_id=chat_id, text=message)
            except Exception as e:
                logger.error(f"Alert sturen naar {chat_id} mislukt: {e}")

    def format_status_report(self, results: list[HostStatus]) -> str:
        """Formatteer health check resultaten als leesbaar rapport.

        Args:
            results: Lijst van HostStatus objecten

        Returns:
            Geformateerd statusrapport
        """
        if not results:
            return "Geen hosts geconfigureerd voor monitoring."

        lines: list[str] = []
        emoji_up = "\u2705"
        emoji_down = "\u274c"
        lines.append("=== Systeem Status ===\n")

        for status in results:
            icon = emoji_up if status.reachable else emoji_down
            line = f"{icon} {status.name} ({status.address})"
            if status.latency_ms is not None:
                line += f" - {status.latency_ms:.1f}ms"
            if status.details:
                line += f" [{status.details}]"
            lines.append(line)

        return "\n".join(lines)
