#!/bin/bash
# File: Beout_OS_Server/install-server.sh
set -euo pipefail

echo "🚀 Installing Beout OS Server..."
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SERVICE_FILE="/etc/systemd/system/beout-server.service"

# Determine the non-root user who owns this repository directory
RUN_USER=$(stat -c '%U' "$REPO_ROOT")
if [ -z "$RUN_USER" ] || [ "$RUN_USER" = "root" ]; then
    RUN_USER="${SUDO_USER:-$USER}"
fi

echo "🔧 Generating systemd service file dynamically..."
echo "   User: $RUN_USER"
echo "   WorkingDirectory: $REPO_ROOT"

sudo tee "$SERVICE_FILE" > /dev/null <<EOF
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

# Ensure scripts are executable
chmod +x "$REPO_ROOT/start_server.sh"
chmod +x "$REPO_ROOT/bin/ssl_proxy.py"

# Reload and restart
echo "🔄 Reloading systemd..."
sudo systemctl daemon-reload
echo "▶️ Restarting beout-server.service..."
sudo systemctl restart beout-server.service
echo "✅ Checking status..."
sudo systemctl status beout-server.service --no-pager --lines=10
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