# Product Roadmap — AI Claude Code Bot Framework

> Versie: 0.1.0 | Datum: 31 maart 2026
> Eigenaar: Said Ahamri (CodeLabs B.V.)

---

## Epic 1: Core Framework

> Het fundament: een werkende AI bot die via Telegram communiceert met Claude Code.

### Features

#### F1.1 Bot Lifecycle Management
| ID | User Story | Status |
|----|-----------|--------|
| US-101 | Als beheerder wil ik een bot starten met een YAML config zodat ik geen code hoef te wijzigen | done |
| US-102 | Als beheerder wil ik meerdere bots draaien op dezelfde machine via systemd templates | done |
| US-103 | Als beheerder wil ik een one-click installer (setup.sh) die alles configureert | done |
| US-104 | Als beheerder wil ik een bot kunnen stoppen en starten zonder dataverlies | done |
| US-105 | Als beheerder wil ik bots via Telegram kunnen beheren (/newbot, /stopbot, /startbot) | open |
| US-106 | Als beheerder wil ik een dashboard (Web App) in Telegram met bot-overzicht | open |

#### F1.2 Autorisatie & Beveiliging
| ID | User Story | Status |
|----|-----------|--------|
| US-111 | Als beheerder wil ik dat alleen mijn chat_id berichten kan sturen | done |
| US-112 | Als beheerder wil ik meerdere gebruikers per bot kunnen autoriseren | done |
| US-113 | Als beheerder wil ik dat de bot als non-root user draait | done |
| US-114 | Als beheerder wil ik tokens uit environment variables laden (niet hardcoded) | open |
| US-115 | Als beheerder wil ik een audit log van alle uitgevoerde commando's | open |
| US-116 | Als beheerder wil ik rate limiting per gebruiker | open |

#### F1.3 YAML Configuratie
| ID | User Story | Status |
|----|-----------|--------|
| US-121 | Als beheerder wil ik bot naam, token en system prompt in YAML defnieren | done |
| US-122 | Als beheerder wil ik monitoring hosts en intervals configureren | done |
| US-123 | Als beheerder wil ik plugins activeren per bot via YAML | done |
| US-124 | Als beheerder wil ik config hot-reload zonder bot herstart | open |
| US-125 | Als beheerder wil ik config validatie bij het opstarten met duidelijke foutmeldingen | open |

### Testplan F1
- [ ] Bot start succesvol met minimale YAML config (naam + token + chat_id)
- [ ] Bot weigert berichten van ongeautoriseerde chat_ids
- [ ] Meerdere bots draaien gelijktijdig met verschillende tokens
- [ ] setup.sh draait foutloos op schone Debian 13 LXC
- [ ] Bot herstart automatisch na crash (systemd)

---

## Epic 2: AI Assistant (Claude Code Integratie)

> De kern: natuurlijke taal verwerking via Claude Code CLI.

### Features

#### F2.1 Claude Code Communicatie
| ID | User Story | Status |
|----|-----------|--------|
| US-201 | Als gebruiker wil ik in natuurlijke taal met de bot praten | done |
| US-202 | Als gebruiker wil ik dat de bot een typing indicator toont terwijl Claude denkt | done |
| US-203 | Als gebruiker wil ik dat lange antwoorden automatisch gesplitst worden (4096 char limiet) | done |
| US-204 | Als gebruiker wil ik dat de bot een timeout geeft na 5 minuten | done |
| US-205 | Als gebruiker wil ik dat de bot SSH commando's kan uitvoeren via Claude | done |
| US-206 | Als gebruiker wil ik dat de bot code kan schrijven en bestanden kan aanmaken | done |
| US-207 | Als gebruiker wil ik bestanden kunnen ontvangen die Claude aanmaakt (als Telegram document) | open |
| US-208 | Als gebruiker wil ik foto's/screenshots kunnen sturen die Claude analyseert | open |
| US-209 | Als gebruiker wil ik een /cancel commando om een lopende AI taak te stoppen | open |

#### F2.2 Telegram Formatting
| ID | User Story | Status |
|----|-----------|--------|
| US-211 | Als gebruiker wil ik leesbare antwoorden zonder broken markdown | done |
| US-212 | Als gebruiker wil ik code blocks correct geformateerd zien in Telegram | open |
| US-213 | Als gebruiker wil ik dat de bot HTML formatting gebruikt voor structuur | done |
| US-214 | Als gebruiker wil ik fallback naar plain text als formatting faalt | done |
| US-215 | Als gebruiker wil ik inline keyboards voor veelgebruikte acties | open |
| US-216 | Als gebruiker wil ik een reply keyboard met snelkeuzes | open |

#### F2.3 System Prompt Management
| ID | User Story | Status |
|----|-----------|--------|
| US-221 | Als beheerder wil ik een system prompt definieren per bot in YAML | done |
| US-222 | Als beheerder wil ik dat kennis uit de memory automatisch in de prompt komt | done |
| US-223 | Als beheerder wil ik de system prompt aanpassen via /editprompt | open |
| US-224 | Als beheerder wil ik CLAUDE.md in de werkdirectory als extra context | done |

### Testplan F2
- [ ] Vrij-tekst bericht geeft antwoord binnen 60 seconden
- [ ] Antwoord bevat geen raw markdown (**, ##, ```)
- [ ] Lange antwoorden (>4096 chars) worden correct gesplitst
- [ ] Timeout na 300s geeft gebruiksvriendelijk bericht
- [ ] SSH commando (bijv. "check disk space op pve-00") wordt correct uitgevoerd

---

## Epic 3: Zelflerend Geheugen

> De bot wordt slimmer naarmate je hem meer gebruikt.

### Features

#### F3.1 Conversation Memory
| ID | User Story | Status |
|----|-----------|--------|
| US-301 | Als gebruiker wil ik dat de bot mijn eerdere vragen onthoudt | done |
| US-302 | Als gebruiker wil ik recente gesprekken als context bij nieuwe vragen | done |
| US-303 | Als gebruiker wil ik gesprekken per sessie/dag kunnen teruglezen | open |
| US-304 | Als gebruiker wil ik de conversatiehistorie kunnen wissen (/clear) | open |

#### F3.2 Knowledge Base
| ID | User Story | Status |
|----|-----------|--------|
| US-311 | Als gebruiker wil ik feiten opslaan via /learn | done |
| US-312 | Als gebruiker wil ik kennis verwijderen via /forget | done |
| US-313 | Als gebruiker wil ik de kennisbank bekijken via /memory | done |
| US-314 | Als gebruiker wil ik dat de bot automatisch leert van gesprekken | done |
| US-315 | Als gebruiker wil ik dat de bot correcties onthoudt en toepast | done |
| US-316 | Als gebruiker wil ik kennis kunnen importeren/exporteren (JSON/markdown) | open |
| US-317 | Als gebruiker wil ik dat de bot proactief relevante kennis aanbiedt | open |

### Testplan F3
- [ ] /learn "de wifi code is XYZ" slaat op en is terug te vinden via /memory
- [ ] /forget "wifi" verwijdert de opgeslagen kennis
- [ ] Na correctie ("nee, het IP is .9, niet .8") wordt dit onthouden
- [ ] Kennis uit vorige gesprekken wordt meegenomen in nieuwe AI antwoorden
- [ ] Auto-learn detecteert nieuwe feiten uit gesprekken

---

## Epic 4: Monitoring & Alerts

> Automatische bewaking van infrastructuur met Telegram notificaties.

### Features

#### F4.1 Health Checks
| ID | User Story | Status |
|----|-----------|--------|
| US-401 | Als beheerder wil ik periodieke ping checks van alle hosts | done |
| US-402 | Als beheerder wil ik port checks op web services (HTTP/HTTPS) | done |
| US-403 | Als beheerder wil ik alerts als een host offline gaat | done |
| US-404 | Als beheerder wil ik alerts als een host weer online komt | done |
| US-405 | Als beheerder wil ik CPU/RAM/disk monitoring via SSH | open |
| US-406 | Als beheerder wil ik SSL certificaat expiry monitoring | open |
| US-407 | Als beheerder wil ik uptime tracking en SLA rapportages | open |

#### F4.2 Security Monitoring
| ID | User Story | Status |
|----|-----------|--------|
| US-411 | Als beheerder wil ik failed SSH login detectie | done |
| US-412 | Als beheerder wil ik fail2ban status monitoring | done |
| US-413 | Als beheerder wil ik alerts bij brute force aanvallen (>50 attempts) | done |
| US-414 | Als beheerder wil ik open port scanning en onverwachte services detectie | open |
| US-415 | Als beheerder wil ik CVE/vulnerability scanning | open |
| US-416 | Als beheerder wil ik automatische IP banning bij herhaalde aanvallen | open |

#### F4.3 Alert Management
| ID | User Story | Status |
|----|-----------|--------|
| US-421 | Als beheerder wil ik alerts via Telegram | done |
| US-422 | Als beheerder wil ik alert severity levels (info/warning/critical) | open |
| US-423 | Als beheerder wil ik alert throttling (niet elke 5 min hetzelfde alarm) | open |
| US-424 | Als beheerder wil ik alert acknowledgement (/ack) via Telegram | open |
| US-425 | Als beheerder wil ik dagelijkse/wekelijkse status samenvattingen | open |

### Testplan F4
- [ ] Health check detecteert een offline host binnen 5 minuten
- [ ] Alert wordt gestuurd naar Telegram bij statuswijziging
- [ ] /security toont failed SSH logins en fail2ban bans
- [ ] Geen dubbele alerts voor dezelfde host in korte tijd
- [ ] Monitoring configureerbaar per bot (eigen hosts lijst)

---

## Epic 5: Plugin Systeem

> Uitbreidbare functionaliteit per domein.

### Features

#### F5.1 Plugin Framework
| ID | User Story | Status |
|----|-----------|--------|
| US-501 | Als ontwikkelaar wil ik plugins schrijven die eigen commands registreren | done |
| US-502 | Als beheerder wil ik plugins activeren/deactiveren via YAML config | done |
| US-503 | Als ontwikkelaar wil ik een BasePlugin class met standaard methoden | done |
| US-504 | Als ontwikkelaar wil ik plugins die SSH commands kunnen uitvoeren | done |
| US-505 | Als ontwikkelaar wil ik plugins die REST API calls kunnen doen | done |
| US-506 | Als beheerder wil ik plugins installeren vanaf een externe URL/git repo | open |
| US-507 | Als beheerder wil ik een plugin marketplace/registry | open |

#### F5.2 Proxmox Plugin
| ID | User Story | Status |
|----|-----------|--------|
| US-511 | Als beheerder wil ik LXC/VM lijst opvragen (/pve list) | done |
| US-512 | Als beheerder wil ik LXC/VM status bekijken (/pve status) | done |
| US-513 | Als beheerder wil ik LXC/VM starten/stoppen (/pve start/stop) | done |
| US-514 | Als beheerder wil ik commando's uitvoeren in een container (/pve exec) | done |
| US-515 | Als beheerder wil ik een LXC aanmaken met sizing (s/m/l/xl/xxl) | done |
| US-516 | Als beheerder wil ik 1-click software installatie (docker, nginx, etc.) | done |
| US-517 | Als beheerder wil ik backup/restore van containers | open |
| US-518 | Als beheerder wil ik container resource monitoring (CPU/RAM/disk per CT) | open |
| US-519 | Als beheerder wil ik live console output van een container via Telegram | open |

#### F5.3 Home Assistant Plugin
| ID | User Story | Status |
|----|-----------|--------|
| US-521 | Als gebruiker wil ik HA status opvragen (/ha status) | done |
| US-522 | Als gebruiker wil ik entiteiten bekijken (/ha entities) | done |
| US-523 | Als gebruiker wil ik apparaten aan/uitzetten (/ha toggle) | done |
| US-524 | Als gebruiker wil ik services aanroepen (/ha call) | done |
| US-525 | Als gebruiker wil ik HA events ontvangen als Telegram notificatie | open |
| US-526 | Als gebruiker wil ik een HA dashboard in Telegram (Web App) | open |

#### F5.4 MikroTik Plugin
| ID | User Story | Status |
|----|-----------|--------|
| US-531 | Als beheerder wil ik router status opvragen (/mikrotik status) | done |
| US-532 | Als beheerder wil ik interfaces en DHCP leases bekijken | done |
| US-533 | Als beheerder wil ik firewall regels bekijken | done |
| US-534 | Als beheerder wil ik commando's uitvoeren op de router (/mikrotik exec) | done |
| US-535 | Als beheerder wil ik een veiligheidscheck (ether1 nooit isoleren) | done |
| US-536 | Als beheerder wil ik traffic monitoring en bandwidth grafieken | open |
| US-537 | Als beheerder wil ik VPN peer management via Telegram | open |

### Testplan F5
- [ ] Plugin laadt correct bij activatie in YAML
- [ ] Plugin registreert eigen /commands in Telegram
- [ ] /pve list toont containers van geconfigureerde hosts
- [ ] /ha toggle schakelt een apparaat correct
- [ ] /mikrotik status toont router info zonder ether1 te raken
- [ ] Disabled plugin registreert geen commands

---

## Epic 6: Telegram UI/UX

> Een rijke, interactieve ervaring in Telegram.

### Features

#### F6.1 Interactieve Elementen
| ID | User Story | Status |
|----|-----------|--------|
| US-601 | Als gebruiker wil ik inline keyboard buttons voor ja/nee bevestigingen | open |
| US-602 | Als gebruiker wil ik een reply keyboard met veelgebruikte commando's | open |
| US-603 | Als gebruiker wil ik een command menu (BotFather /setcommands) | open |
| US-604 | Als gebruiker wil ik callback buttons voor paginated lijsten | open |
| US-605 | Als gebruiker wil ik een Telegram Web App voor complexe UI (dashboards) | open |

#### F6.2 Rich Media
| ID | User Story | Status |
|----|-----------|--------|
| US-611 | Als gebruiker wil ik grafieken ontvangen (CPU/RAM/traffic) als afbeelding | open |
| US-612 | Als gebruiker wil ik logbestanden als document download | open |
| US-613 | Als gebruiker wil ik locatie-gebaseerde features (kantoor geofencing) | open |

### Testplan F6
- [ ] Inline keyboard verschijnt bij destructieve acties (bevestiging)
- [ ] Command menu toont alle beschikbare commando's
- [ ] Grafieken zijn leesbaar als Telegram foto

---

## Epic 7: Multi-tenancy & Schaalbaarheid

> Meerdere organisaties, meerdere bots, een framework.

### Features

| ID | User Story | Status |
|----|-----------|--------|
| US-701 | Als beheerder wil ik bots aanmaken via Telegram (/newbot) | open |
| US-702 | Als beheerder wil ik bots beheren via Telegram (/stopbot, /startbot) | open |
| US-703 | Als beheerder wil ik een master bot die alle andere bots beheert | open |
| US-704 | Als beheerder wil ik resource isolatie per bot (eigen data/geheugen) | done |
| US-705 | Als beheerder wil ik centraal logging van alle bot activiteit | open |
| US-706 | Als beheerder wil ik webhooks i.p.v. polling voor productie | open |
| US-707 | Als beheerder wil ik de bots draaien in Docker containers | open |

### Testplan F7
- [ ] /newbot maakt een werkende bot aan met eigen token en config
- [ ] /stopbot stopt een draaiende bot zonder andere bots te raken
- [ ] Elke bot heeft eigen data directory en geheugen
- [ ] Webhooks werken met Cloudflare Tunnel of reverse proxy

---

## Samenvatting

| Epic | Totaal Stories | Done | Open | Voortgang |
|------|---------------|------|------|-----------|
| 1. Core Framework | 16 | 10 | 6 | 63% |
| 2. AI Assistant | 16 | 10 | 6 | 63% |
| 3. Zelflerend Geheugen | 11 | 7 | 4 | 64% |
| 4. Monitoring & Alerts | 15 | 7 | 8 | 47% |
| 5. Plugin Systeem | 21 | 15 | 6 | 71% |
| 6. Telegram UI/UX | 8 | 0 | 8 | 0% |
| 7. Multi-tenancy | 7 | 1 | 6 | 14% |
| **Totaal** | **94** | **50** | **44** | **53%** |

---

## Release Planning

| Versie | Focus | Epics | Status |
|--------|-------|-------|--------|
| **v0.1.0** | Core + AI + Memory + Plugins | 1,2,3,5 (basis) | done |
| **v0.2.0** | Monitoring + Security + Formatting fixes | 4, F2.2 | in progress |
| **v0.3.0** | Telegram UI (keyboards, menus, Web App) | 6 | planned |
| **v0.4.0** | Multi-tenancy (bot management via Telegram) | 7 | planned |
| **v0.5.0** | Advanced monitoring (CPU/RAM, SSL, SLA) | F4.1, F4.3 | planned |
| **v1.0.0** | Productie-ready: alle epics compleet | 1-7 | planned |
