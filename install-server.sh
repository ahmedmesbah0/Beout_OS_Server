#!/bin/bash
# File: Beout_OS_Server/install-server.sh
set -euo pipefail

echo "🚀 Installing Beout OS Server..."
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SERVICE_FILE="/etc/systemd/system/beout-server.service"

# Copy service file to systemd
echo "🔧 Copying service file to: $SERVICE_FILE"
sudo cp "$REPO_ROOT/beout-server.service" "$SERVICE_FILE"

# Reload and restart
echo "🔄 Reloading systemd..."
sudo systemctl daemon-reload
echo "▶️ Restarting beout-server.service..."
sudo systemctl restart beout-server.service
echo "✅ Checking status..."
sudo systemctl status beout-server.service --no-pager --lines=10
echo ""
echo "🌐 Testing local access..."
if curl -s http://localhost >/dev/null; then
    echo "🎉 SUCCESS: Server is running at http://localhost"
else
    echo "⚠️ ERROR: Server not responding. Check logs with:"
    echo " journalctl -u beout-server.service -n 20"
fi
echo ""
echo "📌 Next: Access from browser: http://<your-server-ip>"