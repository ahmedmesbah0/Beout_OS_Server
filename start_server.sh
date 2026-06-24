#!/bin/bash
# Startup script for Beout OS License Server

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Ensure certificates are available
echo "Verifying SSL certificates..."
if [[ ! -f "certs/server.crt" ]] || [[ ! -f "certs/server.key" ]]; then
    echo "Generating SSL certificates..."
    mkdir -p certs
    openssl req -x509 -newkey rsa:2048 -keyout certs/server.key -out certs/server.crt -days 365 -nodes -subj "/CN=update.beout.ai"
fi

# Verify Ed25519 keys are available
echo "Verifying Ed25519 keys..."
if [[ ! -f "etc/ed25519_private_key.pem" ]] || [[ ! -f "etc/ed25519_public_key.pem" ]]; then
    echo "Generating Ed25519 keys..."
    mkdir -p etc
    "$SCRIPT_DIR/bin/generate_keys.sh"
fi

# Verify key links exist
if [[ ! -f "/etc/ed25519_private_key.pem" ]]; then
    sudo ln -sf "$SCRIPT_DIR/etc/ed25519_private_key.pem" /etc/ed25519_private_key.pem
fi

if [[ ! -f "/etc/ed25519_public_key.pem" ]]; then
    sudo ln -sf "$SCRIPT_DIR/etc/ed25519_public_key.pem" /etc/ed25519_public_key.pem
fi

# Kill any existing server processes
echo "Cleaning up old server processes..."
pkill -f "php -d.*-S 127.0.0.1:8000" 2>/dev/null || true
pkill -f "php -d.*-S 0.0.0.0:8000" 2>/dev/null || true
pkill -f "php -d.*-S 0.0.0.0:8443" 2>/dev/null || true
pkill -f "php -d.*-S 0.0.0.0:8080" 2>/dev/null || true
pkill -f "ssl_proxy.py" 2>/dev/null || true

echo "Starting Beout OS License Server Backend on http://0.0.0.0:8000..."
# Start the PHP server on port 8000 with index.php as the router
/usr/bin/php -d upload_max_filesize=100M -d post_max_size=100M -S 0.0.0.0:8000 -t public public/index.php 2>&1 >> php_server.log &
PHP_PID=$!

echo "Starting SSL/TLS Proxy on https://0.0.0.0:8443..."
# Start the Python SSL proxy on port 8443
python3 bin/ssl_proxy.py 2>&1 >> ssl_proxy.log &
PROXY_PID=$!

echo "PHP Backend PID: $PHP_PID, SSL Proxy PID: $PROXY_PID"

echo "Testing server connectivity..."
sleep 3

curl -k https://127.0.0.1:8443/api/health 2>/dev/null
if [[ $? -eq 0 ]]; then
    echo "✅ SUCCESS: Server is responding to health checks at https://localhost:8443/api/health!"
else
    echo "⚠️ WARNING: Server failed to respond to health checks. Check php_server.log and ssl_proxy.log for errors."
fi

echo "Beout OS License Server is now running."

# Keep the script running until interrupted
while true; do
    # Verify both processes are still running, restart them if they died
    if ! kill -0 $PHP_PID 2>/dev/null; then
        echo "PHP server died. Restarting..."
        /usr/bin/php -d upload_max_filesize=100M -d post_max_size=100M -S 0.0.0.0:8000 -t public public/index.php 2>&1 >> php_server.log &
        PHP_PID=$!
    fi
    if ! kill -0 $PROXY_PID 2>/dev/null; then
        echo "SSL Proxy died. Restarting..."
        python3 bin/ssl_proxy.py 2>&1 >> ssl_proxy.log &
        PROXY_PID=$!
    fi
    sleep 10
done