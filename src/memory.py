"""Conversation memory en knowledge base module.

Slaat gesprekken op in JSON en kennis in markdown bestanden.
Ondersteunt zelflerend gedrag door gesprekken te analyseren.
"""

import json
import logging
import os
import time
from datetime import datetime
from pathlib import Path
from typing import Any, Optional

logger = logging.getLogger(__name__)


class Memory:
    """Beheert gespreksgeheugen en kennisbank."""

    def __init__(
        self,
        data_dir: str,
        enabled: bool = True,
        auto_learn: bool = True,
    ) -> None:
        """Initialiseer het geheugen.

        Args:
            data_dir: Basismap voor opslag
            enabled: Of geheugen actief is
            auto_learn: Of automatisch leren ingeschakeld is
        """
        self.data_dir = Path(data_dir)
        self.enabled = enabled
        self.auto_learn = auto_learn

        # Maak directories aan
        self.conversations_dir = self.data_dir / "memory" / "conversations"
        self.knowledge_dir = self.data_dir / "memory" / "knowledge"

        if self.enabled:
            self.conversations_dir.mkdir(parents=True, exist_ok=True)
            self.knowledge_dir.mkdir(parents=True, exist_ok=True)

    def save_conversation(
        self,
        user_msg: str,
        assistant_msg: str,
        user_id: Optional[int] = None,
        metadata: Optional[dict[str, Any]] = None,
    ) -> None:
        """Sla een gesprek op als JSON bestand.

        Args:
            user_msg: Bericht van de gebruiker
            assistant_msg: Antwoord van de assistent
            user_id: Telegram user ID
            metadata: Extra metadata om op te slaan
        """
        if not self.enabled:
            return

        timestamp = datetime.now().isoformat()
        filename = f"{int(time.time())}_{user_id or 'unknown'}.json"

        conversation = {
            "timestamp": timestamp,
            "user_id": user_id,
            "user_message": user_msg,
            "assistant_message": assistant_msg,
            "metadata": metadata or {},
        }

        filepath = self.conversations_dir / filename
        try:
            with open(filepath, "w", encoding="utf-8") as f:
                json.dump(conversation, f, ensure_ascii=False, indent=2)
            logger.debug(f"Gesprek opgeslagen: {filepath}")
        except OSError as e:
            logger.error(f"Fout bij opslaan gesprek: {e}")

    def get_recent_conversations(
        self, n: int = 5, user_id: Optional[int] = None
    ) -> list[dict[str, Any]]:
        """Haal de meest recente gesprekken op.

        Args:
            n: Aantal gesprekken om op te halen
            user_id: Filter op specifieke gebruiker (optioneel)

        Returns:
            Lijst van gesprekken, nieuwste eerst
        """
        if not self.enabled:
            return []

        conversations: list[dict[str, Any]] = []

        try:
            files = sorted(
                self.conversations_dir.glob("*.json"),
                key=lambda f: f.stat().st_mtime,
                reverse=True,
            )

            for filepath in files:
                try:
                    with open(filepath, "r", encoding="utf-8") as f:
                        conv = json.load(f)

                    # Filter op user_id als opgegeven
                    if user_id and conv.get("user_id") != user_id:
                        continue

                    conversations.append(conv)

                    if len(conversations) >= n:
                        break
                except (json.JSONDecodeError, OSError) as e:
                    logger.warning(f"Fout bij lezen gesprek {filepath}: {e}")

        except OSError as e:
            logger.error(f"Fout bij ophalen gesprekken: {e}")

        return conversations

    def save_knowledge(self, topic: str, content: str) -> None:
        """Sla kennis op als markdown bestand.

        Args:
            topic: Onderwerp (wordt bestandsnaam)
            content: Inhoud in markdown formaat
        """
        if not self.enabled:
            return

        # Sanitize bestandsnaam
        safe_topic = self._sanitize_filename(topic)
        filepath = self.knowledge_dir / f"{safe_topic}.md"

        try:
            with open(filepath, "w", encoding="utf-8") as f:
                f.write(f"# {topic}\n\n")
                f.write(f"_Laatst bijgewerkt: {datetime.now().isoformat()}_\n\n")
                f.write(content)
            logger.info(f"Kennis opgeslagen: {topic}")
        except OSError as e:
            logger.error(f"Fout bij opslaan kennis '{topic}': {e}")

    def get_knowledge(self) -> str:
        """Haal alle kennis op als samengevoegde tekst.

        Returns:
            Alle kennisitems samengevoegd als tekst
        """
        if not self.enabled:
            return ""

        parts: list[str] = []

        try:
            for filepath in sorted(self.knowledge_dir.glob("*.md")):
                try:
                    with open(filepath, "r", encoding="utf-8") as f:
                        parts.append(f.read().strip())
                except OSError as e:
                    logger.warning(f"Fout bij lezen kennis {filepath}: {e}")
        except OSError as e:
            logger.error(f"Fout bij ophalen kennis: {e}")

        return "\n\n---\n\n".join(parts)

    def get_knowledge_topics(self) -> list[str]:
        """Haal lijst van alle kennisonderwerpen op.

        Returns:
            Lijst van onderwerp-namen
        """
        if not self.enabled:
            return []

        topics: list[str] = []
        try:
            for filepath in sorted(self.knowledge_dir.glob("*.md")):
                topics.append(filepath.stem)
        except OSError as e:
            logger.error(f"Fout bij ophalen kennisonderwerpen: {e}")

        return topics

    def forget(self, topic: str) -> bool:
        """Verwijder kennis over een onderwerp.

        Args:
            topic: Onderwerp om te verwijderen

        Returns:
            True als het onderwerp verwijderd is, False anders
        """
        if not self.enabled:
            return False

        safe_topic = self._sanitize_filename(topic)
        filepath = self.knowledge_dir / f"{safe_topic}.md"

        if filepath.exists():
            try:
                filepath.unlink()
                logger.info(f"Kennis verwijderd: {topic}")
                return True
            except OSError as e:
                logger.error(f"Fout bij verwijderen kennis '{topic}': {e}")
                return False

        logger.warning(f"Kennis niet gevonden: {topic}")
        return False

    def build_context(self, user_id: Optional[int] = None, n_conversations: int = 5) -> str:
        """Bouw context op voor de AI uit geheugen en kennis.

        Args:
            user_id: Filter gesprekken op gebruiker
            n_conversations: Aantal recente gesprekken

        Returns:
            Context string voor het AI systeem prompt
        """
        parts: list[str] = []

        # Kennisbank
        knowledge = self.get_knowledge()
        if knowledge:
            parts.append("=== KENNISBANK ===")
            parts.append(knowledge)

        # Recente gesprekken
        conversations = self.get_recent_conversations(n=n_conversations, user_id=user_id)
        if conversations:
            parts.append("\n=== RECENTE GESPREKKEN ===")
            for conv in reversed(conversations):  # Oudste eerst
                ts = conv.get("timestamp", "onbekend")
                user_msg = conv.get("user_message", "")
                asst_msg = conv.get("assistant_message", "")
                # Beperk lengte van eerdere antwoorden
                if len(asst_msg) > 500:
                    asst_msg = asst_msg[:500] + "..."
                parts.append(f"[{ts}]")
                parts.append(f"Gebruiker: {user_msg}")
                parts.append(f"Assistent: {asst_msg}")
                parts.append("")

        return "\n".join(parts)

    def auto_learn_from_conversation(
        self, user_msg: str, assistant_msg: str
    ) -> Optional[str]:
        """Analyseer een gesprek en extraheer kennis om op te slaan.

        Detecteert patronen zoals correcties, nieuwe feiten, en voorkeuren.

        Args:
            user_msg: Bericht van de gebruiker
            assistant_msg: Antwoord van de assistent

        Returns:
            Onderwerp als er iets geleerd is, None anders
        """
        if not self.enabled or not self.auto_learn:
            return None

        # Detecteer correcties en belangrijke informatie
        correction_indicators = [
            "dat klopt niet",
            "nee,",
            "fout,",
            "niet correct",
            "onthoud dat",
            "vergeet niet",
            "belangrijk:",
            "let op:",
            "voortaan",
        ]

        msg_lower = user_msg.lower()
        is_correction = any(ind in msg_lower for ind in correction_indicators)

        if is_correction:
            # Sla de correctie/informatie op als kennis
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            topic = f"geleerd_{timestamp}"

            content = (
                f"Gebruiker zei: {user_msg}\n\n"
                f"Context: {assistant_msg[:300]}"
            )

            self.save_knowledge(topic, content)
            logger.info(f"Auto-geleerd uit gesprek: {topic}")
            return topic

        return None

    @staticmethod
    def _sanitize_filename(name: str) -> str:
        """Maak een veilige bestandsnaam van een string.

        Args:
            name: Invoer string

        Returns:
            Veilige bestandsnaam
        """
        # Vervang onveilige tekens
        safe = name.lower().strip()
        safe = safe.replace(" ", "_")
        # Verwijder alles behalve alfanumeriek, underscore en streepje
        safe = "".join(c for c in safe if c.isalnum() or c in ("_", "-"))
        # Beperk lengte
        return safe[:100] if safe else "untitled"
