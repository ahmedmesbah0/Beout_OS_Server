<?php
namespace BeoutOS\Server;

class Crypto {
    /**
     * All keys live in the etc/ directory (consistent with generate_keys.sh).
     * generate_keys.sh creates: etc/ed25519_private_key.pem, etc/ed25519_public_key.pem
     * start_server.sh symlinks them to /etc/ed25519_*.pem for LicenseManager.php fallback
     */
    public static function getKeysDir() {
        return dirname(__DIR__) . '/etc';
    }

    public static function getPrivateKeyPath() {
        return self::getKeysDir() . '/ed25519_private_key.pem';
    }

    public static function getPublicKeyPath() {
        return self::getKeysDir() . '/ed25519_public_key.pem';
    }

    public static function isKeyPairAvailable() {
        return file_exists(self::getPrivateKeyPath()) && file_exists(self::getPublicKeyPath());
    }

    /**
     * Return the public key PEM.
     * @throws \RuntimeException if key file is missing
     */
    public static function getPublicKey() {
        $pubPath = self::getPublicKeyPath();
        if (!file_exists($pubPath)) {
            throw new \RuntimeException(
                "Ed25519 public key not found at {$pubPath}. " .
                "Please run bin/generate_keys.sh to create the signing key pair."
            );
        }
        return file_get_contents($pubPath);
    }

    /**
     * Sign a payload with the Ed25519 private key.
     *
     * @param string      $payload      The data to sign (typically a machine_id).
     * @param string|null $privKeyPath  Override path to private key (defaults to standard location).
     * @return string|null              Base64-encoded Ed25519 signature, or null on failure.
     */
    public static function signPayload($payload, $privKeyPath = null) {
        try {
            if ($privKeyPath === null) {
                $privKeyPath = self::getPrivateKeyPath();
            }
            if (!file_exists($privKeyPath)) {
                error_log("Crypto::signPayload: Private key not found at {$privKeyPath}");
                return null;
            }
            $privPem = file_get_contents($privKeyPath);
            if ($privPem === false) {
                error_log("Crypto::signPayload: Failed to read private key from {$privKeyPath}");
                return null;
            }

            // Parse PEM → extract raw DER bytes
            $lines = explode("\n", trim($privPem));
            $b64Lines = array_filter($lines, function($line) {
                $line = trim($line);
                return $line !== '' && strpos($line, '---') !== 0;
            });
            $b64 = implode("", $b64Lines);
            $der = base64_decode($b64);
            if (!$der) {
                error_log("Crypto::signPayload: Failed to base64-decode PEM body");
                return null;
            }

            // Ed25519 private key seed is the last 32 bytes of PKCS#8 structure
            $seed = substr($der, -32);
            if (strlen($seed) !== 32) {
                error_log("Crypto::signPayload: Extracted seed is " . strlen($seed) . " bytes, expected 32");
                return null;
            }

            $keypair = sodium_crypto_sign_seed_keypair($seed);
            $secret = sodium_crypto_sign_secretkey($keypair);
            $sigBytes = sodium_crypto_sign_detached($payload, $secret);

            return base64_encode($sigBytes);
        } catch (\Throwable $e) {
            error_log("Crypto::signPayload error: " . $e->getMessage());
            return null;
        }
    }
}
