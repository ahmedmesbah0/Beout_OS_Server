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
