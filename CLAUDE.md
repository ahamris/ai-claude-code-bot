# AI Bot Framework - Claude Code Instructies

Dit bestand wordt gelezen door Claude Code wanneer het op de LXC draait.

## Wat is dit?
Een generiek AI bot framework dat meerdere Telegram bots aanstuurt.
Elke bot heeft een eigen YAML configuratie en kan eigen plugins laden.

## Architectuur
- `src/bot.py` - Hoofdclass AIBot, laadt config en start polling
- `src/ai_handler.py` - Claude CLI integratie (subprocess aanroep)
- `src/formatter.py` - Telegram output formatting (HTML/plain/MarkdownV2)
- `src/memory.py` - Gespreksgeheugen en kennisbank (JSON + markdown)
- `src/monitoring.py` - Health checks, security scans, alerts
- `src/commands/` - Standaard commands (status, admin, security, memory, network)
- `src/plugins/` - Uitbreidbare plugins (Proxmox, Home Assistant, MikroTik)
- `configs/` - YAML configuraties per bot instance

## Nieuwe bot toevoegen
1. Kopieer een voorbeeld config: `cp configs/example-devops.yaml configs/mijnbot.yaml`
2. Pas de config aan (token, chat_ids, system_prompt, plugins)
3. Start: `systemctl enable --now ai-bot@mijnbot`

## Nieuw plugin schrijven
1. Maak een bestand in `src/plugins/` (bijv. `mijn_plugin.py`)
2. Erf van `BasePlugin`:
```python
from .base import BasePlugin

class MijnPlugin(BasePlugin):
    name = "mijn_plugin"
    description = "Mijn custom plugin"
    commands = [("mijn_cmd", "Beschrijving")]

    async def initialize(self):
        pass

    async def handle_mijn_cmd(self, update, context):
        await update.effective_message.reply_text("Hallo!")
```
3. Registreer in `src/bot.py` in `PLUGIN_REGISTRY`:
```python
from .plugins.mijn_plugin import MijnPlugin
PLUGIN_REGISTRY["mijn_plugin"] = MijnPlugin
```
4. Voeg toe aan de YAML config: `plugins: [mijn_plugin]`

## SSH configuratie
SSH hosts worden geconfigureerd in de YAML config onder `ssh.hosts`.
Elk host kan een direct adres zijn of een dict met `host` en optioneel `jump`:
```yaml
ssh:
  user: "root"
  hosts:
    direct-host: "10.0.0.1"
    via-jump:
      host: "10.0.0.2"
      jump: "10.0.0.1"
```

## Belangrijk
- De bot draait als user `claude` (NIET als root)
- Claude credentials staan in `/home/claude/.claude/.credentials.json`
- Geheugen wordt opgeslagen in de `data_dir` uit de YAML config
- Logs via: `journalctl -u ai-bot@<naam> -f`
- NOOIT ether1 op MikroTik routers isoleren (is de uplink!)
