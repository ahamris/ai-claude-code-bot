#!/bin/bash
# AI Bot Framework - One-click installer
# Installeert het framework op een Debian/Ubuntu systeem
set -euo pipefail

INSTALL_DIR="/opt/ai-bot"
BOT_USER="claude"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Kleuren
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

info() { echo -e "${GREEN}[INFO]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[FOUT]${NC} $1"; exit 1; }

# Root check
if [[ $EUID -eq 0 ]]; then
    info "Draait als root - goed voor installatie."
else
    error "Dit script moet als root uitgevoerd worden (sudo ./setup.sh)"
fi

echo ""
echo "========================================="
echo "  AI Bot Framework - Installer v0.1.0"
echo "========================================="
echo ""

# Stap 1: Gebruiker aanmaken
info "Stap 1/6: Gebruiker '${BOT_USER}' controleren..."
if id "${BOT_USER}" &>/dev/null; then
    info "Gebruiker '${BOT_USER}' bestaat al."
else
    useradd -r -m -s /bin/bash "${BOT_USER}"
    info "Gebruiker '${BOT_USER}' aangemaakt."
fi

# Stap 2: Systeem dependencies
info "Stap 2/6: Systeem dependencies installeren..."
apt-get update -qq
apt-get install -y -qq python3 python3-venv python3-pip git openssh-client iputils-ping > /dev/null

# Stap 3: Installatie directory
info "Stap 3/6: Installatie directory voorbereiden..."
mkdir -p "${INSTALL_DIR}"

# Kopieer bronbestanden
cp -r "${SCRIPT_DIR}/src" "${INSTALL_DIR}/"
cp -r "${SCRIPT_DIR}/configs" "${INSTALL_DIR}/"
cp "${SCRIPT_DIR}/requirements.txt" "${INSTALL_DIR}/"

if [[ -f "${SCRIPT_DIR}/CLAUDE.md" ]]; then
    cp "${SCRIPT_DIR}/CLAUDE.md" "${INSTALL_DIR}/"
fi

# Stap 4: Python venv en dependencies
info "Stap 4/6: Python virtual environment aanmaken..."
python3 -m venv "${INSTALL_DIR}/venv"
"${INSTALL_DIR}/venv/bin/pip" install --quiet --upgrade pip
"${INSTALL_DIR}/venv/bin/pip" install --quiet -r "${INSTALL_DIR}/requirements.txt"
info "Python dependencies geinstalleerd."

# Stap 5: Data directories aanmaken
info "Stap 5/6: Data directories aanmaken..."
mkdir -p "${INSTALL_DIR}/data/memory/conversations"
mkdir -p "${INSTALL_DIR}/data/memory/knowledge"

# Eigenaarschap instellen
chown -R "${BOT_USER}:${BOT_USER}" "${INSTALL_DIR}"

# Stap 6: Systemd service installeren
info "Stap 6/6: Systemd service installeren..."
cp "${SCRIPT_DIR}/systemd/ai-bot@.service" /etc/systemd/system/
systemctl daemon-reload
info "Systemd template service geinstalleerd."

echo ""
echo "========================================="
echo "  Installatie voltooid!"
echo "========================================="
echo ""
echo "Volgende stappen:"
echo ""
echo "1. Kopieer een voorbeeld configuratie en pas deze aan:"
echo "   cp ${INSTALL_DIR}/configs/example-devops.yaml ${INSTALL_DIR}/configs/devops.yaml"
echo "   nano ${INSTALL_DIR}/configs/devops.yaml"
echo ""
echo "2. Vul het Telegram bot token in (via @BotFather)"
echo ""
echo "3. Log in met Claude als de '${BOT_USER}' gebruiker:"
echo "   su - ${BOT_USER}"
echo "   claude login"
echo ""
echo "4. Start de bot:"
echo "   systemctl enable --now ai-bot@devops"
echo ""
echo "5. Bekijk de logs:"
echo "   journalctl -u ai-bot@devops -f"
echo ""
echo "Meerdere bots draaien:"
echo "   cp configs/example-facility.yaml configs/facility.yaml"
echo "   systemctl enable --now ai-bot@facility"
echo ""
