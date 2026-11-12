<?php
namespace Helpers;

class JwtHelper {
    private static $alg = 'HS256';

    private static function getSecret() {
        $secret = getenv('JWT_SECRET');
        if (!$secret || strlen($secret) < 32) {
            throw new \RuntimeException('Configure JWT_SECRET con al menos 32 caracteres.');
        }
        return $secret;
    }

    public static function generateToken($payload, $expiryMinutes = 30) {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$alg]);
        $payload['exp'] = time() + ($expiryMinutes * 60);
        $payload['iat'] = time();
        $payloadJson = json_encode($payload);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payloadJson));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getSecret(), true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($header, $payload, $signature) = $parts;

        // Verificar la firma
        $validSignature = hash_hmac('sha256', $header . "." . $payload, self::getSecret(), true);
        $base64UrlValidSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));

        if (!hash_equals($base64UrlValidSignature, $signature)) {
            return false; // Firma inválida
        }

        // Verificar expiración
        $payloadData = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return false; // Token expirado
        }

        return $payloadData;
    }
}
