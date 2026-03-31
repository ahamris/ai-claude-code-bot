"""Home Assistant plugin.

Biedt integratie met Home Assistant via de REST API.
"""

import json
import logging
import urllib.request
import urllib.error
from typing import Any, Optional

from telegram import Update
from telegram.ext import ContextTypes

from .base import BasePlugin

logger = logging.getLogger(__name__)


class HomeAssistantPlugin(BasePlugin):
    """Plugin voor Home Assistant integratie via REST API."""

    name = "homeassistant"
    description = "Home Assistant apparaat- en automatiebeheer"
    commands = [
        ("ha", "Home Assistant: /ha <actie> [args]"),
    ]

    def __init__(self, bot: "Any", config: dict[str, Any]) -> None:
        """Initialiseer Home Assistant plugin.

        Args:
            bot: Bot instance
            config: Plugin configuratie met url en token
        """
        super().__init__(bot, config)
        self.base_url = config.get("url", "http://localhost:8123")
        self.token = config.get("token", "")
        self.verify_ssl = config.get("verify_ssl", True)

    async def initialize(self) -> None:
        """Initialiseer de Home Assistant plugin en controleer verbinding."""
        if not self.token:
            logger.warning(
                "Home Assistant plugin: geen API token geconfigureerd."
            )
            return

        # Test verbinding
        try:
            result = self._api_get("/api/")
            if result:
                logger.info(
                    f"Home Assistant plugin verbonden: {result.get('message', 'ok')}"
                )
            else:
                logger.warning("Home Assistant plugin: verbinding mislukt.")
        except Exception as e:
            logger.warning(f"Home Assistant plugin init fout: {e}")

    async def handle_ha(
        self, update: Update, context: ContextTypes.DEFAULT_TYPE
    ) -> None:
        """Verwerk het /ha command.

        Subcommands:
          /ha status              - Toon HA status
          /ha entities [filter]   - Lijst entiteiten
          /ha state <entity_id>   - Status van entiteit
          /ha toggle <entity_id>  - Toggle een entiteit
          /ha call <domain> <service> <entity_id> - Roep service aan

        Args:
            update: Telegram Update object
            context: Telegram context
        """
        if not self.token:
            await update.effective_message.reply_text(
                "Home Assistant API token is niet geconfigureerd."
            )
            return

        args = context.args or []

        if not args:
            help_text = (
                "Home Assistant beheer:\n\n"
                "/ha status - Toon HA status\n"
                "/ha entities [filter] - Lijst entiteiten\n"
                "/ha state <entity_id> - Status entiteit\n"
                "/ha toggle <entity_id> - Toggle entiteit\n"
                "/ha call <domain> <service> <entity_id> - Service aanroepen"
            )
            await update.effective_message.reply_text(help_text)
            return

        action = args[0].lower()

        if action == "status":
            await self._show_status(update)
        elif action == "entities":
            filter_str = args[1] if len(args) > 1 else None
            await self._list_entities(update, filter_str)
        elif action == "state" and len(args) > 1:
            await self._show_state(update, args[1])
        elif action == "toggle" and len(args) > 1:
            await self._toggle_entity(update, args[1])
        elif action == "call" and len(args) > 3:
            await self._call_service(update, args[1], args[2], args[3])
        else:
            await update.effective_message.reply_text(
                "Onbekende actie. Gebruik /ha zonder argumenten voor hulp."
            )

    async def _show_status(self, update: Update) -> None:
        """Toon Home Assistant status.

        Args:
            update: Telegram Update object
        """
        try:
            result = self._api_get("/api/")
            config = self._api_get("/api/config")

            lines: list[str] = []
            emoji_house = "\U0001f3e0"  # huis
            lines.append(f"{emoji_house} Home Assistant Status\n")

            if config:
                lines.append(f"Locatie: {config.get('location_name', 'onbekend')}")
                lines.append(f"Versie: {config.get('version', 'onbekend')}")
                lines.append(f"Tijdzone: {config.get('time_zone', 'onbekend')}")

            if result:
                lines.append(f"Status: {result.get('message', 'onbekend')}")

            await self.bot.formatter.send_message(update, "\n".join(lines))

        except Exception as e:
            await update.effective_message.reply_text(
                f"Fout bij ophalen HA status: {e}"
            )

    async def _list_entities(
        self, update: Update, filter_str: Optional[str] = None
    ) -> None:
        """Lijst Home Assistant entiteiten.

        Args:
            update: Telegram Update object
            filter_str: Optioneel filter op entiteit-type (light, switch, etc.)
        """
        try:
            states = self._api_get("/api/states")
            if not states or not isinstance(states, list):
                await update.effective_message.reply_text(
                    "Kon entiteiten niet ophalen."
                )
                return

            # Filter
            if filter_str:
                states = [
                    s for s in states
                    if filter_str.lower() in s.get("entity_id", "").lower()
                ]

            # Groepeer per domein
            domains: dict[str, list[dict]] = {}
            for state in states:
                entity_id = state.get("entity_id", "")
                domain = entity_id.split(".")[0] if "." in entity_id else "overig"
                if domain not in domains:
                    domains[domain] = []
                domains[domain].append(state)

            lines: list[str] = []
            lines.append(f"Entiteiten ({len(states)} totaal):\n")

            for domain, entities in sorted(domains.items()):
                lines.append(f"[{domain}] ({len(entities)})")
                # Toon maximaal 10 per domein
                for entity in entities[:10]:
                    eid = entity.get("entity_id", "?")
                    st = entity.get("state", "?")
                    name = entity.get("attributes", {}).get("friendly_name", "")
                    display = name if name else eid
                    lines.append(f"  {display}: {st}")
                if len(entities) > 10:
                    lines.append(f"  ...en {len(entities) - 10} meer")
                lines.append("")

            # Beperk totale lengte
            result = "\n".join(lines)
            if len(result) > 3500:
                result = result[:3500] + "\n...(afgekapt)"

            await self.bot.formatter.send_message(update, result)

        except Exception as e:
            await update.effective_message.reply_text(
                f"Fout bij ophalen entiteiten: {e}"
            )

    async def _show_state(self, update: Update, entity_id: str) -> None:
        """Toon de status van een specifieke entiteit.

        Args:
            update: Telegram Update object
            entity_id: Entity ID (bijv. light.woonkamer)
        """
        try:
            state = self._api_get(f"/api/states/{entity_id}")
            if not state:
                await update.effective_message.reply_text(
                    f"Entiteit '{entity_id}' niet gevonden."
                )
                return

            lines: list[str] = []
            name = state.get("attributes", {}).get("friendly_name", entity_id)
            lines.append(f"Entiteit: {name}")
            lines.append(f"ID: {entity_id}")
            lines.append(f"Status: {state.get('state', 'onbekend')}")
            lines.append(f"Laatst gewijzigd: {state.get('last_changed', '?')}")

            # Toon relevante attributen
            attrs = state.get("attributes", {})
            skip_keys = {"friendly_name", "icon", "supported_features"}
            relevant_attrs = {
                k: v for k, v in attrs.items() if k not in skip_keys
            }
            if relevant_attrs:
                lines.append("\nAttributen:")
                for key, value in list(relevant_attrs.items())[:15]:
                    lines.append(f"  {key}: {value}")

            await self.bot.formatter.send_message(update, "\n".join(lines))

        except Exception as e:
            await update.effective_message.reply_text(
                f"Fout bij ophalen status: {e}"
            )

    async def _toggle_entity(self, update: Update, entity_id: str) -> None:
        """Toggle een entiteit (aan/uit).

        Args:
            update: Telegram Update object
            entity_id: Entity ID
        """
        try:
            domain = entity_id.split(".")[0] if "." in entity_id else ""
            if not domain:
                await update.effective_message.reply_text(
                    "Ongeldig entity ID formaat."
                )
                return

            data = {"entity_id": entity_id}
            result = self._api_post(
                f"/api/services/{domain}/toggle", data
            )

            emoji_ok = "\u2705"
            await update.effective_message.reply_text(
                f"{emoji_ok} {entity_id} is getoggled."
            )

        except Exception as e:
            await update.effective_message.reply_text(
                f"Fout bij toggle: {e}"
            )

    async def _call_service(
        self,
        update: Update,
        domain: str,
        service: str,
        entity_id: str,
    ) -> None:
        """Roep een Home Assistant service aan.

        Args:
            update: Telegram Update object
            domain: Service domein (bijv. light)
            service: Service naam (bijv. turn_on)
            entity_id: Entity ID
        """
        try:
            data = {"entity_id": entity_id}
            result = self._api_post(
                f"/api/services/{domain}/{service}", data
            )

            emoji_ok = "\u2705"
            await update.effective_message.reply_text(
                f"{emoji_ok} Service {domain}.{service} aangeroepen voor {entity_id}."
            )

        except Exception as e:
            await update.effective_message.reply_text(
                f"Fout bij aanroepen service: {e}"
            )

    def _api_get(self, endpoint: str) -> Optional[Any]:
        """Doe een GET request naar de Home Assistant API.

        Args:
            endpoint: API endpoint (bijv. /api/states)

        Returns:
            JSON response als dict/list, of None bij fout
        """
        url = f"{self.base_url.rstrip('/')}{endpoint}"
        req = urllib.request.Request(url)
        req.add_header("Authorization", f"Bearer {self.token}")
        req.add_header("Content-Type", "application/json")

        try:
            with urllib.request.urlopen(req, timeout=10) as response:
                return json.loads(response.read().decode("utf-8"))
        except (urllib.error.URLError, json.JSONDecodeError, OSError) as e:
            logger.error(f"HA API GET {endpoint} mislukt: {e}")
            return None

    def _api_post(self, endpoint: str, data: dict) -> Optional[Any]:
        """Doe een POST request naar de Home Assistant API.

        Args:
            endpoint: API endpoint
            data: Request body als dict

        Returns:
            JSON response, of None bij fout
        """
        url = f"{self.base_url.rstrip('/')}{endpoint}"
        body = json.dumps(data).encode("utf-8")
        req = urllib.request.Request(url, data=body, method="POST")
        req.add_header("Authorization", f"Bearer {self.token}")
        req.add_header("Content-Type", "application/json")

        try:
            with urllib.request.urlopen(req, timeout=10) as response:
                return json.loads(response.read().decode("utf-8"))
        except (urllib.error.URLError, json.JSONDecodeError, OSError) as e:
            logger.error(f"HA API POST {endpoint} mislukt: {e}")
            return None
