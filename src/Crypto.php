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
        $privKeyPath = self::getPrivateKeyPath();
        
        // Write payload to temporary file
        $tempPayload = tempnam(sys_get_temp_dir(), 'pay');
        file_put_contents($tempPayload, $payload);
        $tempSig = $tempPayload . '.sig';

        // Execute OpenSSL Ed25519 signature
        $cmd = "openssl pkeyutl -sign -inkey " . escapeshellarg($privKeyPath) . " -rawin -in " . escapeshellarg($tempPayload) . " -out " . escapeshellarg($tempSig) . " 2>&1";
        exec($cmd, $output, $returnVar);

        if ($returnVar === 0 && file_exists($tempSig)) {
            $sigBytes = file_get_contents($tempSig);
            @unlink($tempPayload);
            @unlink($tempSig);
            return base64_encode($sigBytes);
        }

        @unlink($tempPayload);
        if (file_exists($tempSig)) {
            @unlink($tempSig);
        }
        return null;
    }
}
