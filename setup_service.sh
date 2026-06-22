#!/bin/bash
set -e

echo "=== Beout_OS Server Auto-Start Setup ==="

# 1. Check/Install Dependencies
if command -v apt-get >/dev/null 2>&1; then
    echo "Detected Debian/Ubuntu system. Installing PHP and SQLite modules..."
    apt-get update
    apt-get install -y php-cli php-sqlite3 php-xml php-mbstring
elif command -v apk >/dev/null 2>&1; then
    echo "Detected Alpine Linux system. Installing PHP and SQLite modules..."
    apk update
    apk add php-cli php-sqlite3 php-pdo_sqlite php-openssl php-mbstring php-xml
else
    echo "Unknown package manager. Please ensure php-cli and php-sqlite3 are installed."
fi

# 2. Verify PHP
if ! command -v php >/dev/null 2>&1; then
    echo "Error: PHP is still not installed or not in PATH."
    exit 1
fi

PHP_PATH=$(command -v php)
echo "Using PHP binary at: $PHP_PATH"

# 3. Setup Systemd Service
if command -v systemctl >/dev/null 2>&1; then
    echo "Setting up systemd service..."
    
    # Generate service file dynamically with current absolute directory path
    DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
    
    cat <<EOF > /etc/systemd/system/beout-server.service
[Unit]
Description=Beout_OS Licensing and Update Server
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=$DIR
ExecStart=$PHP_PATH -d upload_max_filesize=100M -d post_max_size=100M -S 0.0.0.0:8000 -t public
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

    echo "Reloading systemd daemon..."
    systemctl daemon-reload
    
    echo "Enabling beout-server service..."
    systemctl enable beout-server
    
    echo "Starting beout-server service..."
    systemctl start beout-server
    
    echo "Checking service status..."
    sleep 2
    if systemctl is-active --quiet beout-server; then
        echo "Success! Beout_OS Server is active and running automatically on port 8000."
    else
        echo "Warning: Service was registered but is not active. Run 'systemctl status beout-server' to debug."
    fi
else
    echo "Systemd not detected (non-systemd container/Docker)."
    echo "Configuring auto-start using crontab (@reboot)..."
    
    DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
    CRON_LINE="@reboot cd $DIR && $PHP_PATH -d upload_max_filesize=100M -d post_max_size=100M -S 0.0.0.0:8000 -t public > /var/log/beout-server.log 2>&1 &"
    
    (crontab -l 2>/dev/null | grep -Fv "$DIR" ; echo "$CRON_LINE") | crontab -
    
    # Run in background now
    nohup $PHP_PATH -d upload_max_filesize=100M -d post_max_size=100M -S 0.0.0.0:8000 -t public > /var/log/beout-server.log 2>&1 &
    
    echo "Auto-start added to crontab. Server started in background."
fi

echo "=== Setup Complete ==="
