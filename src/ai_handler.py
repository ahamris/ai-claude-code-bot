"""Claude Code integratie module.

Roept de Claude CLI aan als subprocess en verwerkt de output.
Moet als non-root user draaien vanwege --dangerously-skip-permissions.
"""

import asyncio
import logging
import os
import shutil
from typing import Optional

from telegram import Update
from telegram.constants import ChatAction

from .memory import Memory

logger = logging.getLogger(__name__)

# Maximale wachttijd voor Claude CLI response
DEFAULT_TIMEOUT = 300
DEFAULT_MAX_TURNS = 25


class AIHandler:
    """Verwerkt AI-verzoeken via de Claude CLI."""

    def __init__(
        self,
        system_prompt: str = "",
        max_turns: int = DEFAULT_MAX_TURNS,
        timeout: int = DEFAULT_TIMEOUT,
        memory: Optional[Memory] = None,
        claude_binary: str = "claude",
        working_dir: Optional[str] = None,
    ) -> None:
        """Initialiseer de AI handler.

        Args:
            system_prompt: Systeem prompt voor de AI
            max_turns: Maximaal aantal conversatie-turns
            timeout: Maximale wachttijd in seconden
            memory: Memory instance voor context
            claude_binary: Pad naar de claude binary
            working_dir: Werkdirectory voor Claude Code
        """
        self.system_prompt = system_prompt
        self.max_turns = max_turns
        self.timeout = timeout
        self.memory = memory
        self.claude_binary = claude_binary
        self.working_dir = working_dir

    def _build_prompt(self, user_message: str, user_id: Optional[int] = None) -> str:
        """Bouw het volledige prompt op inclusief context uit geheugen.

        Args:
            user_message: Het bericht van de gebruiker
            user_id: Telegram user ID voor context filtering

        Returns:
            Volledig prompt met context
        """
        parts: list[str] = []

        # Systeem prompt
        if self.system_prompt:
            parts.append(self.system_prompt)

        # Geheugen context
        if self.memory and self.memory.enabled:
            context = self.memory.build_context(user_id=user_id)
            if context:
                parts.append(context)

        # Het eigenlijke bericht
        parts.append(f"\nGebruiker vraagt: {user_message}")

        return "\n\n".join(parts)

    async def process_message(
        self,
        update: Update,
        user_message: str,
    ) -> str:
        """Verwerk een bericht via Claude Code.

        Stuurt typing indicators tijdens de verwerking.

        Args:
            update: Telegram Update object voor typing indicators
            user_message: Het te verwerken bericht

        Returns:
            Claude Code response als tekst

        Raises:
            TimeoutError: Als Claude niet binnen de timeout reageert
            RuntimeError: Als Claude een fout teruggeeft
        """
        user_id = update.effective_user.id if update.effective_user else None

        # Controleer of claude binary beschikbaar is
        if not shutil.which(self.claude_binary):
            raise RuntimeError(
                f"Claude binary niet gevonden: {self.claude_binary}. "
                "Zorg dat claude geinstalleerd is en in het PATH staat."
            )

        # Bouw prompt
        full_prompt = self._build_prompt(user_message, user_id=user_id)

        # Start typing indicator
        typing_task = asyncio.create_task(
            self._send_typing_indicator(update)
        )

        try:
            # Bouw het commando
            cmd = [
                self.claude_binary,
                "-p",
                full_prompt,
                "--dangerously-skip-permissions",
                "--max-turns",
                str(self.max_turns),
                "--output-format",
                "text",
            ]

            logger.debug(f"Claude CLI commando: {' '.join(cmd[:3])}...")

            # Bepaal werkdirectory
            cwd = self.working_dir or os.getcwd()

            # Voer Claude uit als subprocess
            process = await asyncio.create_subprocess_exec(
                *cmd,
                stdout=asyncio.subprocess.PIPE,
                stderr=asyncio.subprocess.PIPE,
                cwd=cwd,
            )

            try:
                stdout, stderr = await asyncio.wait_for(
                    process.communicate(),
                    timeout=self.timeout,
                )
            except asyncio.TimeoutError:
                process.kill()
                await process.wait()
                raise TimeoutError(
                    f"Claude reageerde niet binnen {self.timeout} seconden."
                )

            # Verwerk output
            if process.returncode != 0:
                error_msg = stderr.decode("utf-8", errors="replace").strip()
                logger.error(f"Claude CLI fout (code {process.returncode}): {error_msg}")
                raise RuntimeError(
                    f"Claude CLI fout: {error_msg or 'onbekende fout'}"
                )

            response = stdout.decode("utf-8", errors="replace").strip()

            if not response:
                response = "Geen antwoord ontvangen van Claude."

            # Sla gesprek op in geheugen
            if self.memory:
                self.memory.save_conversation(
                    user_msg=user_message,
                    assistant_msg=response,
                    user_id=user_id,
                )

                # Automatisch leren
                self.memory.auto_learn_from_conversation(user_message, response)

            return response

        finally:
            # Stop typing indicator
            typing_task.cancel()
            try:
                await typing_task
            except asyncio.CancelledError:
                pass

    async def _send_typing_indicator(self, update: Update) -> None:
        """Stuur continu typing indicators tot geannuleerd.

        Args:
            update: Telegram Update object
        """
        try:
            while True:
                try:
                    await update.effective_chat.send_action(ChatAction.TYPING)
                except Exception:
                    pass
                await asyncio.sleep(4)  # Telegram typing duurt ~5 seconden
        except asyncio.CancelledError:
            pass
