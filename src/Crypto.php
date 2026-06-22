<?php
namespace BeoutOS\Server;

class Crypto {
    private static $defaultPrivateKey = "-----BEGIN PRIVATE KEY-----\nMC4CAQAwBQYDK2VwBCIEIOXKVQsxlbHNAW0UGL5fadh8ELjimlCcKBS5dS57nqFF\n-----END PRIVATE KEY-----";
    private static $defaultPublicKey = "-----BEGIN PUBLIC KEY-----\nMCowBQYDK2VwAyEAvaUOLMIWZZgDTNnYbTi3r4gpLhMXMgo4PqgXUj1Njmk=\n-----END PUBLIC KEY-----";

    public static function getPrivateKeyPath() {
        $keysDir = dirname(__DIR__) . '/keys';
        if (!is_dir($keysDir)) {
            mkdir($keysDir, 0755, true);
        }
        $privPath = $keysDir . '/private_key.pem';
        if (!file_exists($privPath)) {
            file_put_contents($privPath, self::$defaultPrivateKey);
        }
        return $privPath;
    }

    public static function getPublicKey() {
        $keysDir = dirname(__DIR__) . '/keys';
        if (!is_dir($keysDir)) {
            mkdir($keysDir, 0755, true);
        }
        $pubPath = $keysDir . '/public_key.pem';
        if (!file_exists($pubPath)) {
            file_put_contents($pubPath, self::$defaultPublicKey);
        }
        return file_get_contents($pubPath);
    }

    public static function signPayload($payload) {
        try {
            $privKeyPath = self::getPrivateKeyPath();
            if (!file_exists($privKeyPath)) {
                return null;
            }
            $privPem = file_get_contents($privKeyPath);
            
            $lines = explode("\n", trim($privPem));
            // Filter out PEM header/footer lines and spaces
            $b64Lines = array_filter($lines, function($line) {
                $line = trim($line);
                return $line !== '' && strpos($line, '---') !== 0;
            });
            $b64 = implode("", $b64Lines);
            $der = base64_decode($b64);
            if (!$der) {
                return null;
            }
            
            // Extract the 32-byte private key seed from PKCS#8 DER structure (located at the end)
            $seed = substr($der, -32);
            if (strlen($seed) !== 32) {
                return null;
            }
            
            $keypair = sodium_crypto_sign_seed_keypair($seed);
            $secret = sodium_crypto_sign_secretkey($keypair);
            $sigBytes = sodium_crypto_sign_detached($payload, $secret);
            
            return base64_encode($sigBytes);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
