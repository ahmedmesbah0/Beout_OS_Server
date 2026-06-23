#!/bin/bash
# Startup script for Beout OS License Server

cd /home/mesba7/Documents/GitHub/Beout_OS/Beout_OS_Server

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
    /home/mesba7/Documents/GitHub/Beout_OS/Beout_OS_Server/bin/generate_keys.sh
fi

# Verify key links exist
if [[ ! -f "/etc/ed25519_private_key.pem" ]]; then
    sudo ln -sf /home/mesba7/Documents/GitHub/Beout_OS/Beout_OS_Server/etc/ed25519_private_key.pem /etc/ed25519_private_key.pem
fi

if [[ ! -f "/etc/ed25519_public_key.pem" ]]; then
    sudo ln -sf /home/mesba7/Documents/GitHub/Beout_OS/Beout_OS_Server/etc/ed25519_public_key.pem /etc/ed25519_public_key.pem
fi

# Kill any existing server processes
pkill -f "php -S 0.0.0.0:8443" 2>/dev/null

echo "Starting Beout OS License Server on https://172.30.1.2:8443..."

# Start the server with SSL support
/usr/bin/php -d upload_max_filesize=100M -d post_max_size=100M -S 0.0.0.0:8443 -t public -c certs/server.crt -k certs/server.key 2>&1 >> server.log &

# Capture the PID
SERVER_PID=$!
echo "Server started with PID: $SERVER_PID"

echo "Testing server connectivity..."

# Test connectivity
sleep 5

curl -k https://localhost:8443/api/health 2>/dev/null
if [[ $? -eq 0 ]]; then
    echo "Server is responding to health checks!"
else
    echo "Server failed to respond to health checks. Check server.log for errors."
fi

echo "Beout OS License Server is now running."

# Keep the script running until interrupted
while true; do
    sleep 60
done