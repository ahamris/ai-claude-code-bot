# AI Bot Framework

Een generiek AI bot framework powered by Claude Code. Draai meerdere Telegram bots (DevOps, Facility, Executive Assistant, etc.) op een enkel Claude Code abonnement.

## Features

- **Multi-bot**: Meerdere bots met elk een eigen configuratie op dezelfde machine
- **Claude Code integratie**: Volledige AI-antwoorden via de Claude CLI
- **Zelflerend geheugen**: Slaat gesprekken en kennis op, leert automatisch van correcties
- **Plugin systeem**: Uitbreidbaar met Proxmox, Home Assistant, MikroTik en eigen plugins
- **Monitoring**: Automatische health checks, security scans en Telegram alerts
- **Telegram formatting**: HTML, plain text en MarkdownV2 met automatische fallback
- **Veilig**: Draait als non-root user, autorisatie via chat_id whitelist

## Quickstart

```bash
# 1. Clone de repository
git clone <repo-url> /tmp/ai-bot
cd /tmp/ai-bot

# 2. Voer de installer uit (als root)
sudo ./setup.sh

# 3. Kopieer en pas een configuratie aan
sudo cp /opt/ai-bot/configs/example-devops.yaml /opt/ai-bot/configs/devops.yaml
sudo nano /opt/ai-bot/configs/devops.yaml
# Vul in: bot token (van @BotFather) en je Telegram chat_id

# 4. Log in met Claude
sudo su - claude
claude login
exit

# 5. Start de bot
sudo systemctl enable --now ai-bot@devops

# 6. Bekijk de logs
journalctl -u ai-bot@devops -f
```

## Configuratie

Elke bot wordt geconfigureerd via een YAML bestand in `/opt/ai-bot/configs/`.

### Minimale configuratie

```yaml
bot:
  name: "Mijn Bot"
  token: "123456:ABC-DEF"
  chat_ids: [jouw_chat_id]

ai:
  system_prompt: |
    Je bent een behulpzame assistant.
```

### Volledige opties

| Sectie | Optie | Beschrijving | Standaard |
|--------|-------|--------------|-----------|
| `bot.name` | Botnaam | Weergavenaam | "AI Bot" |
| `bot.token` | Telegram token | Van @BotFather | (verplicht) |
| `bot.chat_ids` | Geautoriseerde IDs | Lijst van chat/user IDs | [] (iedereen) |
| `ai.max_turns` | Max conversatie-turns | Claude CLI parameter | 25 |
| `ai.timeout` | Max wachttijd | In seconden | 300 |
| `ai.system_prompt` | Systeem prompt | Instructies voor de AI | "" |
| `ai.working_dir` | Werkdirectory | Voor Claude Code | cwd |
| `memory.enabled` | Geheugen aan/uit | | false |
| `memory.auto_learn` | Automatisch leren | | true |
| `memory.data_dir` | Opslaglocatie | | /opt/ai-bot/data |
| `monitoring.enabled` | Monitoring aan/uit | | false |
| `monitoring.health_check_interval` | Interval health check | In seconden | 300 |
| `monitoring.security_scan_interval` | Interval security scan | In seconden | 900 |
| `formatter.mode` | Formatteringsmodus | plain/html/markdownv2 | plain |

## Commands

| Command | Beschrijving |
|---------|--------------|
| `/status` | Toon bot- en systeemstatus |
| `/create` | Maak container/VM aan (via AI) |
| `/install` | Installeer software (via AI) |
| `/list` | Lijst containers/VMs |
| `/logs` | Bekijk logs van een host |
| `/security` | Voer security scan uit |
| `/memory` | Toon geheugen en kennisbank |
| `/learn` | Sla kennis op |
| `/forget` | Verwijder kennis |
| `/ping` | Ping een host |
| `/scan` | Port scan |
| `/devices` | Toon bekende apparaten |

Plugins registreren extra commands (bijv. `/pve`, `/ha`, `/mikrotik`).

## Plugin systeem

### Beschikbare plugins

- **proxmox** - Proxmox VE beheer (containers, VMs via SSH)
- **homeassistant** - Home Assistant integratie (REST API)
- **mikrotik** - MikroTik RouterOS beheer (SSH)

### Eigen plugin schrijven

Maak een bestand in `src/plugins/` en erf van `BasePlugin`:

```python
from .base import BasePlugin

class MijnPlugin(BasePlugin):
    name = "mijn_plugin"
    description = "Mijn custom plugin"
    commands = [("mijn_cmd", "Doe iets")]

    async def initialize(self):
        # Wordt aangeroepen bij het starten
        pass

    async def handle_mijn_cmd(self, update, context):
        await update.effective_message.reply_text("Hallo!")
```

Registreer de plugin in `src/bot.py` (`PLUGIN_REGISTRY`) en voeg de naam toe aan je YAML config.

## Architectuur

```
                    +------------------+
                    |   Telegram API   |
                    +--------+---------+
                             |
                    +--------v---------+
                    |     AIBot        |
                    |  (src/bot.py)    |
                    +--+----+----+---+-+
                       |    |    |   |
          +------------+    |    |   +------------+
          |                 |    |                |
+---------v---+   +---------v-+  +---v---------+  +--v----------+
| Commands    |   | AIHandler |  | Monitoring  |  | Plugins     |
| /status     |   | Claude CLI|  | Health/Sec  |  | Proxmox     |
| /memory     |   | subprocess|  | Alerts      |  | HA          |
| /security   |   +---------+-+  +---+---------+  | MikroTik    |
+-------------+             |        |             +--+----------+
                            |        |                |
                    +-------v--------v-+    +---------v---+
                    |    Memory        |    |   SSH/API   |
                    | Conversations    |    | Remote hosts|
                    | Knowledge base   |    +-------------+
                    +------------------+
```

## Meerdere bots draaien

```bash
# DevOps bot
cp configs/example-devops.yaml configs/devops.yaml
systemctl enable --now ai-bot@devops

# Facility bot
cp configs/example-facility.yaml configs/facility.yaml
systemctl enable --now ai-bot@facility

# Executive bot
cp configs/example-executive.yaml configs/executive.yaml
systemctl enable --now ai-bot@executive
```

Elke bot draait als aparte systemd service met eigen configuratie, geheugen en plugins.

## Vereisten

- Debian/Ubuntu (of compatibel)
- Python 3.11+
- Claude Code CLI (geinstalleerd en ingelogd als user `claude`)
- Telegram Bot Token (via @BotFather)

## Licentie

MIT
