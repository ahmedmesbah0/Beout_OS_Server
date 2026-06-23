# Beout OS Key Management Guide

## Ed25519 Key Pair

The Beout OS licensing system uses Ed25519 cryptography for secure license activation and verification. This provides strong security with efficient signature verification.

### Key Generation

Run the following command on your Beout OS license server to generate a new Ed25519 key pair:

```bash
/bin/generate_keys.sh
```

This creates two files:
- `/etc/ed25519_private_key.pem` - Private key (keep secure on the server)
- `/etc/ed25519_public_key.pem` - Public key (distribute to all client devices)

### Key Distribution

Copy the public key from your license server to each client device:

```bash
# From license server, copy public key to client
scp /etc/ed25519_public_key.pem user@client-ip:/opt/beout_os/etc/license_public_key.pem
```

Ensure the public key is placed at `/opt/beout_os/etc/license_public_key.pem` on each client.

### Key Verification

Verify that the public key is correctly installed on clients:

```bash
# On client device
ls -la /opt/beout_os/etc/license_public_key.pem
openssl pkey -in /opt/beout_os/etc/license_public_key.pem -pubin -text -noout
```

### Key Rotation

To rotate keys:
1. Generate a new key pair on the server with `/bin/generate_keys.sh`
2. Distribute the new public key to all clients
3. Restart the license server service
4. Confirm all clients can still activate

## Important Notes

- The private key must NEVER be copied to client devices
- The public key can be distributed to all client devices
- If public keys are compromised, regenerate a new key pair and re-distribute
- Always validate the key distribution with the client's `/api/license` endpoint

## Troubleshooting

If activation fails:
1. Verify the public key is present at `/opt/beout_os/etc/license_public_key.pem` on the client
2. Check the server logs for any signature validation errors
3. Verify network connectivity between client and server
4. Ensure the server's license service is running

For further assistance, contact Beout OS Support.