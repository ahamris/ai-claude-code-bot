"""Telegram output formatting module.

Sanitize Claude Code output voor veilige weergave in Telegram.
Ondersteunt plain text, HTML en MarkdownV2 modi.
"""

import re
import logging
from typing import Optional

from telegram import Update
from telegram.constants import ParseMode

logger = logging.getLogger(__name__)

# Maximale berichtlengte voor Telegram
MAX_MESSAGE_LENGTH = 4096
# Veilige marge voor splitting
SAFE_MESSAGE_LENGTH = 4000


class TelegramFormatter:
    """Formatteert en sanitized tekst voor Telegram berichten."""

    def __init__(self, mode: str = "plain", strip_markdown: bool = True) -> None:
        """Initialiseer de formatter.

        Args:
            mode: Formattering modus - 'plain', 'html', of 'markdownv2'
            strip_markdown: Of markdown uit Claude output gestript moet worden
        """
        self.mode = mode.lower()
        self.strip_markdown = strip_markdown

    def format_message(self, text: str) -> str:
        """Formatteer een bericht voor Telegram op basis van de ingestelde modus.

        Args:
            text: Ruwe tekst van Claude Code

        Returns:
            Geformateerde en gesanitizede tekst
        """
        if not text:
            return ""

        if self.mode == "html":
            return self._to_html(text)
        elif self.mode == "markdownv2":
            return self._to_markdownv2(text)
        else:
            return self._to_plain(text)

    def _to_plain(self, text: str) -> str:
        """Converteer naar plain text door markdown te strippen."""
        if not self.strip_markdown:
            return text

        result = text

        # Verwijder code blocks met taal-indicator
        result = re.sub(r'```\w*\n?', '', result)

        # Verwijder headers (## Header -> Header)
        result = re.sub(r'^#{1,6}\s+', '', result, flags=re.MULTILINE)

        # Verwijder bold/italic markers maar behoud tekst
        result = re.sub(r'\*\*\*(.+?)\*\*\*', r'\1', result)
        result = re.sub(r'\*\*(.+?)\*\*', r'\1', result)
        result = re.sub(r'\*(.+?)\*', r'\1', result)
        result = re.sub(r'__(.+?)__', r'\1', result)
        result = re.sub(r'_(.+?)_', r'\1', result)

        # Verwijder inline code backticks
        result = re.sub(r'`(.+?)`', r'\1', result)

        # Verwijder horizontale lijnen
        result = re.sub(r'^-{3,}$', '', result, flags=re.MULTILINE)
        result = re.sub(r'^\*{3,}$', '', result, flags=re.MULTILINE)

        # Verwijder link formatting [text](url) -> text (url)
        result = re.sub(r'\[(.+?)\]\((.+?)\)', r'\1 (\2)', result)

        # Verwijder overbodige lege regels
        result = re.sub(r'\n{3,}', '\n\n', result)

        return result.strip()

    def _to_html(self, text: str) -> str:
        """Converteer markdown naar Telegram-compatibele HTML."""
        result = text

        # Verwerk code blocks eerst (bescherm inhoud tegen verdere conversie)
        code_blocks: list[str] = []

        def _replace_code_block(match: re.Match) -> str:
            idx = len(code_blocks)
            code_blocks.append(match.group(1))
            return f"__CODEBLOCK_{idx}__"

        result = re.sub(
            r'```\w*\n?(.*?)```',
            _replace_code_block,
            result,
            flags=re.DOTALL,
        )

        # Inline code
        inline_codes: list[str] = []

        def _replace_inline_code(match: re.Match) -> str:
            idx = len(inline_codes)
            inline_codes.append(match.group(1))
            return f"__INLINECODE_{idx}__"

        result = re.sub(r'`(.+?)`', _replace_inline_code, result)

        # Escape HTML speciale tekens in de gewone tekst
        result = self._escape_html(result)

        # Headers -> bold
        result = re.sub(
            r'^#{1,6}\s+(.+)$', r'<b>\1</b>', result, flags=re.MULTILINE
        )

        # Bold en italic
        result = re.sub(r'\*\*\*(.+?)\*\*\*', r'<b><i>\1</i></b>', result)
        result = re.sub(r'\*\*(.+?)\*\*', r'<b>\1</b>', result)
        result = re.sub(r'\*(.+?)\*', r'<i>\1</i>', result)
        result = re.sub(r'__(.+?)__', r'<b>\1</b>', result)
        result = re.sub(r'_(.+?)_', r'<i>\1</i>', result)

        # Links
        result = re.sub(r'\[(.+?)\]\((.+?)\)', r'<a href="\2">\1</a>', result)

        # Herstel code blocks
        for idx, code in enumerate(code_blocks):
            escaped_code = self._escape_html(code)
            result = result.replace(
                f"__CODEBLOCK_{idx}__", f"<pre>{escaped_code}</pre>"
            )

        # Herstel inline code
        for idx, code in enumerate(inline_codes):
            escaped_code = self._escape_html(code)
            result = result.replace(
                f"__INLINECODE_{idx}__", f"<code>{escaped_code}</code>"
            )

        # Horizontale lijnen verwijderen
        result = re.sub(r'^-{3,}$', '', result, flags=re.MULTILINE)
        result = re.sub(r'^\*{3,}$', '', result, flags=re.MULTILINE)

        # Overbodige lege regels
        result = re.sub(r'\n{3,}', '\n\n', result)

        return result.strip()

    def _to_markdownv2(self, text: str) -> str:
        """Converteer naar Telegram MarkdownV2 formaat.

        Let op: MarkdownV2 vereist escaping van speciale tekens.
        Dit is foutgevoelig - gebruik bij voorkeur HTML of plain text.
        """
        result = text

        # Verwerk code blocks eerst
        code_blocks: list[str] = []

        def _replace_code_block(match: re.Match) -> str:
            idx = len(code_blocks)
            lang = match.group(1) or ""
            code = match.group(2)
            code_blocks.append((lang, code))
            return f"__CODEBLOCK_{idx}__"

        result = re.sub(
            r'```(\w*)\n?(.*?)```',
            _replace_code_block,
            result,
            flags=re.DOTALL,
        )

        # Inline code
        inline_codes: list[str] = []

        def _replace_inline_code(match: re.Match) -> str:
            idx = len(inline_codes)
            inline_codes.append(match.group(1))
            return f"__INLINECODE_{idx}__"

        result = re.sub(r'`(.+?)`', _replace_inline_code, result)

        # Converteer markdown formatting
        result = re.sub(r'^#{1,6}\s+(.+)$', r'*\1*', result, flags=re.MULTILINE)
        result = re.sub(r'\*\*\*(.+?)\*\*\*', r'*_\1_*', result)
        # Bold **text** is al correct voor MarkdownV2 (*text*)
        result = re.sub(r'\*\*(.+?)\*\*', r'*\1*', result)
        # Italic _text_ is al correct

        # Links [text](url) zijn al correct voor MarkdownV2

        # Escape speciale tekens (behalve formatting tekens die we al gebruiken)
        special_chars = r'\.+!#=|{}()>-'
        for char in special_chars:
            result = result.replace(char, f'\\{char}')

        # Herstel code blocks
        for idx, (lang, code) in enumerate(code_blocks):
            block = f"```{lang}\n{code}```"
            result = result.replace(f"__CODEBLOCK_{idx}__", block)

        # Herstel inline code
        for idx, code in enumerate(inline_codes):
            result = result.replace(f"__INLINECODE_{idx}__", f"`{code}`")

        # Overbodige lege regels
        result = re.sub(r'\n{3,}', '\n\n', result)

        return result.strip()

    @staticmethod
    def _escape_html(text: str) -> str:
        """Escape HTML speciale tekens."""
        return (
            text.replace('&', '&amp;')
            .replace('<', '&lt;')
            .replace('>', '&gt;')
        )

    def split_message(self, text: str, max_len: int = SAFE_MESSAGE_LENGTH) -> list[str]:
        """Splits een lang bericht in meerdere delen.

        Probeert te splitsen op logische punten (lege regels, zinnen).

        Args:
            text: Het te splitsen bericht
            max_len: Maximale lengte per deel

        Returns:
            Lijst van berichtdelen
        """
        if len(text) <= max_len:
            return [text]

        parts: list[str] = []
        remaining = text

        while remaining:
            if len(remaining) <= max_len:
                parts.append(remaining)
                break

            # Zoek een goed splitspunt
            split_at = max_len

            # Probeer te splitsen op een lege regel
            last_empty_line = remaining[:max_len].rfind('\n\n')
            if last_empty_line > max_len // 2:
                split_at = last_empty_line + 1
            else:
                # Probeer te splitsen op een nieuwe regel
                last_newline = remaining[:max_len].rfind('\n')
                if last_newline > max_len // 2:
                    split_at = last_newline + 1
                else:
                    # Probeer te splitsen op een spatie
                    last_space = remaining[:max_len].rfind(' ')
                    if last_space > max_len // 2:
                        split_at = last_space + 1

            parts.append(remaining[:split_at].rstrip())
            remaining = remaining[split_at:].lstrip()

        return parts

    async def send_message(
        self,
        update: Update,
        text: str,
        html: Optional[bool] = None,
    ) -> None:
        """Stuur een bericht via Telegram met de juiste parse_mode.

        Valt terug op plain text als formatting faalt.

        Args:
            update: Telegram Update object
            text: Te verzenden tekst
            html: Override voor HTML modus (None = gebruik self.mode)
        """
        if not text:
            return

        # Bepaal parse mode
        use_html = html if html is not None else (self.mode == "html")

        if use_html:
            formatted = self._to_html(text) if html else text
            parse_mode = ParseMode.HTML
        elif self.mode == "markdownv2":
            formatted = self._to_markdownv2(text)
            parse_mode = ParseMode.MARKDOWN_V2
        else:
            formatted = self._to_plain(text)
            parse_mode = None

        parts = self.split_message(formatted)

        for part in parts:
            try:
                await update.effective_message.reply_text(
                    text=part,
                    parse_mode=parse_mode,
                )
            except Exception as e:
                logger.warning(
                    f"Bericht sturen met parse_mode={parse_mode} mislukt: {e}. "
                    "Fallback naar plain text."
                )
                # Fallback naar plain text
                plain = self._to_plain(part) if parse_mode else part
                try:
                    await update.effective_message.reply_text(
                        text=plain,
                        parse_mode=None,
                    )
                except Exception as e2:
                    logger.error(f"Bericht sturen als plain text ook mislukt: {e2}")
