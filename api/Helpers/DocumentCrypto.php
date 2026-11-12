<?php
namespace Helpers;

use RuntimeException;

/** Cifrado autenticado y firma de documentos con OpenSSL de PHP. */
class DocumentCrypto {
    private static function aesKey() {
        $encoded = getenv('DOCUMENT_AES_KEY');
        $key = $encoded ? base64_decode($encoded, true) : false;
        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('Configure DOCUMENT_AES_KEY con 32 bytes en base64.');
        }
        return $key;
    }

    private static function keyFile($name) {
        $path = getenv($name);
        if (!$path || !is_file($path) || !is_readable($path)) {
            throw new RuntimeException('No se puede leer la clave configurada en ' . $name . '.');
        }
        return file_get_contents($path);
    }

    private static function signedBytes($title, $content) {
        // Longitudes explícitas evitan ambigüedades entre título y contenido.
        return "documento-v1\n" . strlen($title) . ':' . $title . strlen($content) . ':' . $content;
    }

    public static function encrypt($plaintext) {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', self::aesKey(), OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($ciphertext === false || strlen($tag) !== 16) {
            throw new RuntimeException('No se pudo cifrar el documento.');
        }
        return base64_encode($iv . $tag . $ciphertext);
    }

    public static function decrypt($encoded) {
        $data = base64_decode($encoded, true);
        if ($data === false || strlen($data) < 28) {
            throw new RuntimeException('El documento cifrado tiene un formato inválido.');
        }
        $plaintext = openssl_decrypt(substr($data, 28), 'aes-256-gcm', self::aesKey(), OPENSSL_RAW_DATA, substr($data, 0, 12), substr($data, 12, 16));
        if ($plaintext === false) {
            throw new RuntimeException('No se pudo descifrar o el contenido fue alterado.');
        }
        return $plaintext;
    }

    public static function sign($title, $content) {
        $private = openssl_pkey_get_private(self::keyFile('DOCUMENT_PRIVATE_KEY_PATH'));
        if (!$private || !openssl_sign(self::signedBytes($title, $content), $signature, $private, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('No se pudo firmar el documento.');
        }
        return base64_encode($signature);
    }

    public static function verify($title, $content, $signature) {
        $public = openssl_pkey_get_public(self::keyFile('DOCUMENT_PUBLIC_KEY_PATH'));
        $decoded = base64_decode($signature, true);
        if (!$public || $decoded === false) {
            throw new RuntimeException('La clave pública o la firma tiene formato inválido.');
        }
        $result = openssl_verify(self::signedBytes($title, $content), $decoded, $public, OPENSSL_ALGO_SHA256);
        if ($result === -1) {
            throw new RuntimeException('No se pudo verificar la firma.');
        }
        return $result === 1;
    }
}
