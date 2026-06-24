#!/bin/bash
# File: Beout_OS_Server/install-server.sh
set -euo pipefail

echo "🚀 Installing Beout OS Server..."
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Determine if sudo is required and available
SUDO=""
if [ "$EUID" -ne 0 ] && command -v sudo >/dev/null 2>&1; then
    SUDO="sudo"
fi

# Ensure scripts are executable
chmod +x "$REPO_ROOT/start_server.sh"
chmod +x "$REPO_ROOT/bin/ssl_proxy.py"

# Check if systemd is available and running
HAS_SYSTEMD=false
if [ -d /run/systemd/system ]; then
    HAS_SYSTEMD=true
fi

if [ "$HAS_SYSTEMD" = true ]; then
    SERVICE_FILE="/etc/systemd/system/beout-server.service"

    # Determine the non-root user who owns this repository directory
    RUN_USER=$(stat -c '%U' "$REPO_ROOT")
    if [ -z "$RUN_USER" ] || [ "$RUN_USER" = "root" ]; then
        RUN_USER="${SUDO_USER:-$USER}"
    fi

    echo "🔧 Generating systemd service file dynamically..."
    echo "   User: $RUN_USER"
    echo "   WorkingDirectory: $REPO_ROOT"

    $SUDO tee "$SERVICE_FILE" > /dev/null <<EOF
[Unit]
Description=Beout_OS Licensing and Update Server
After=network.target

[Service]
Type=simple
User=$RUN_USER
WorkingDirectory=$REPO_ROOT
ExecStart=$REPO_ROOT/start_server.sh
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

    # Reload and restart
    echo "🔄 Reloading systemd..."
    $SUDO systemctl daemon-reload
    echo "▶️ Restarting beout-server.service..."
    $SUDO systemctl restart beout-server.service
    echo "✅ Checking status..."
    $SUDO systemctl status beout-server.service --no-pager --lines=10
    echo ""
    echo "Waiting for server to initialize..."
    sleep 3
    echo "🌐 Testing local access..."
    if curl -k -s https://localhost:8443/api/health >/dev/null; then
        echo "🎉 SUCCESS: Server is running at https://localhost:8443"
    else
        echo "⚠️ ERROR: Server not responding. Check logs with:"
        echo " journalctl -u beout-server.service -n 20"
    fi
    echo ""
    echo "📌 Next: Access from browser: https://<your-server-ip>:8443"
else
    echo "🐳 Container or non-systemd environment detected!"
    echo "   Skipping systemd service registration."
    echo ""
    echo "Checking if dependencies are installed..."
    if ! command -v php >/dev/null 2>&1; then
        echo "⚠️ WARNING: 'php' is not installed. Please install php-cli (e.g., 'apt-get install php-cli')."
    else
        echo "✅ PHP is installed."
    fi
    if ! command -v python3 >/dev/null 2>&1; then
        echo "⚠️ WARNING: 'python3' is not installed. Please install python3 (e.g., 'apt-get install python3')."
    else
        echo "✅ Python3 is installed."
    fi
    if ! command -v openssl >/dev/null 2>&1; then
        echo "⚠️ WARNING: 'openssl' is not installed. Please install openssl."
    else
        echo "✅ OpenSSL is installed."
    fi
    echo ""
    echo "💡 How to run the server in this container:"
    echo "   👉 To run in the foreground (perfect for Docker CMD / ENTRYPOINT):"
    echo "      ./start_server.sh"
    echo ""
    echo "   👉 To run in the background:"
    echo "      ./start_server.sh &"
fi