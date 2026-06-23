#!/bin/bash
# Generate Ed25519 key pair for Beout OS licensing system
# This script creates the private and public keys in PEM format compatible with both server and client

set -e

KEY_DIR="$(dirname "$0")/../etc"
PRIVATE_KEY="$KEY_DIR/ed25519_private_key.pem"
PUBLIC_KEY="$KEY_DIR/ed25519_public_key.pem"

# Create key directory if it doesn't exist
mkdir -p "$KEY_DIR"

echo "Generating Ed25519 key pair for Beout OS licensing system..."

# Generate Ed25519 key pair
# OpenSSL 1.1.1+ supports Ed25519 keys
if openssl genpkey -algorithm ED25519 -out "$PRIVATE_KEY"; then
    echo "Private key generated successfully: $PRIVATE_KEY"
else
    echo "Error: Failed to generate Ed25519 private key"
    exit 1
fi

# Extract public key from private key
if openssl pkey -in "$PRIVATE_KEY" -pubout -out "$PUBLIC_KEY"; then
    echo "Public key extracted successfully: $PUBLIC_KEY"
else
    echo "Error: Failed to extract public key from private key"
    exit 1
fi

# Set appropriate permissions
chmod 600 "$PRIVATE_KEY"
chmod 644 "$PUBLIC_KEY"

# Verify the keys
if [ -f "$PRIVATE_KEY" ] && [ -f "$PUBLIC_KEY" ]; then
    echo ""
    echo "Key generation completed successfully!"
    echo "Private key: $PRIVATE_KEY"
    echo "Public key: $PUBLIC_KEY"
    echo ""
    echo "To verify the keys are properly generated, run:"
    echo "openssl pkey -in $PRIVATE_KEY -text -noout"
    echo ""
    echo "IMPORTANT: Copy $PUBLIC_KEY to all clients at /opt/beout_os/etc/license_public_key.pem"
else
    echo "Error: One or both key files were not created correctly"
    exit 1
fi

# Create a sample license activation script to test the keys
cat > "$KEY_DIR/test_sign.sh" << 'EOF'
#!/bin/bash
# Test script to verify key functionality

TEST_MACHINE_ID="TEST-MACHINE-ID-0001"
PRIVATE_KEY="$KEY_DIR/ed25519_private_key.pem"

if [ ! -f "$PRIVATE_KEY" ]; then
    echo "Error: Private key not found at $PRIVATE_KEY"
    exit 1
fi

# Generate signature using OpenSSL's Ed25519
SIGNATURE=$(echo -n "$TEST_MACHINE_ID" | openssl pkeyutl -sign -inkey "$PRIVATE_KEY" -pkeyopt digest:sha512 2>/dev/null | base64 -w0)

if [ -z "$SIGNATURE" ]; then
    echo "Error: Failed to generate signature"
    exit 1
fi

echo "Test machine ID: $TEST_MACHINE_ID"
echo "Generated signature: $SIGNATURE"
echo ""
echo "You can verify this signature using the client's CryptoUtils::verify_signature() function"
echo "with the public key in $PUBLIC_KEY"
EOF

chmod +x "$KEY_DIR/test_sign.sh"

echo "Created test script: $KEY_DIR/test_sign.sh"

echo ""
echo "To use this key pair in your production environment:"
echo "1. Run this script on your license server to generate keys"
echo "2. Copy $PUBLIC_KEY to all client machines at /opt/beout_os/etc/license_public_key.pem"
echo "3. Restart the license server"
echo "4. Test license activation on a client machine"


