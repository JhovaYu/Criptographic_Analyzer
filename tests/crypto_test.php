<?php
require_once __DIR__ . '/../api/Helpers/DocumentCrypto.php';
use Helpers\DocumentCrypto;
function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } echo "OK: $message\n"; }
$dir = sys_get_temp_dir() . '/crypto-test-' . bin2hex(random_bytes(8));
mkdir($dir, 0700);
try {
    $rsa = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($rsa, $private);
    file_put_contents($dir . '/private.pem', $private);
    file_put_contents($dir . '/public.pem', openssl_pkey_get_details($rsa)['key']);
    putenv('DOCUMENT_AES_KEY=' . base64_encode(random_bytes(32)));
    putenv('DOCUMENT_PRIVATE_KEY_PATH=' . $dir . '/private.pem');
    putenv('DOCUMENT_PUBLIC_KEY_PATH=' . $dir . '/public.pem');
    $title = 'Nota de prueba'; $plain = "Información inventada: reunión mañana.\nSegunda línea.";
    $cipher = DocumentCrypto::encrypt($plain);
    check($cipher !== $plain && strpos($cipher, $plain) === false, 'El texto guardado está cifrado');
    check(DocumentCrypto::decrypt($cipher) === $plain, 'Se recupera el texto original');
    check(DocumentCrypto::encrypt($plain) !== $cipher, 'Cada cifrado usa un IV aleatorio');
    $sig = DocumentCrypto::sign($title, $plain);
    check(DocumentCrypto::verify($title, $plain, $sig), 'La firma original es válida');
    check(!DocumentCrypto::verify($title . ' alterado', $plain, $sig), 'Una modificación del título invalida la firma');
    check(!DocumentCrypto::verify($title, $plain . ' alterado', $sig), 'Una modificación del contenido invalida la firma');
    $raw = base64_decode($cipher); $raw[28] = chr(ord($raw[28]) ^ 1); $rejected = false;
    try { DocumentCrypto::decrypt(base64_encode($raw)); } catch (RuntimeException $e) { $rejected = true; }
    check($rejected, 'AES-GCM rechaza contenido cifrado alterado');
    putenv('DOCUMENT_AES_KEY='); $rejected = false;
    try { DocumentCrypto::encrypt($plain); } catch (RuntimeException $e) { $rejected = true; }
    check($rejected, 'Se rechaza una clave AES ausente');
} finally {
    @unlink($dir . '/private.pem'); @unlink($dir . '/public.pem'); @rmdir($dir);
}
