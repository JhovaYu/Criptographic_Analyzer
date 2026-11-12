<?php
// Uso: php scripts/generar_claves.php /ruta/privada/fuera-del-proyecto
if (PHP_SAPI !== 'cli' || $argc !== 2) {
    fwrite(STDERR, "Uso: php scripts/generar_claves.php /ruta/privada/fuera-del-proyecto\n");
    exit(1);
}
$dir = $argv[1];
if (!is_dir($dir) && !mkdir($dir, 0700, true)) { throw new RuntimeException('No se pudo crear el directorio.'); }
$dir = realpath($dir);
$project = realpath(dirname(__DIR__));
if ($dir === $project || strpos($dir, $project . DIRECTORY_SEPARATOR) === 0) { throw new RuntimeException('El directorio debe estar fuera del proyecto.'); }
foreach (['private.pem', 'public.pem', 'secretos.txt'] as $name) {
    if (file_exists($dir . DIRECTORY_SEPARATOR . $name)) { throw new RuntimeException('Ya existe ' . $name . '; no se sobrescribirán claves.'); }
}
$key = openssl_pkey_new(['private_key_bits' => 3072, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
if (!$key || !openssl_pkey_export($key, $private)) { throw new RuntimeException('No se pudo generar la clave RSA.'); }
$details = openssl_pkey_get_details($key);
if (!$details || !isset($details['key'])) { throw new RuntimeException('No se pudo obtener la clave pública.'); }
umask(0077);
$secretos = 'JWT_SECRET=' . bin2hex(random_bytes(32)) . "\n" . 'DOCUMENT_AES_KEY=' . base64_encode(random_bytes(32)) . "\n";
foreach (['private.pem' => $private, 'public.pem' => $details['key'], 'secretos.txt' => $secretos] as $name => $value) {
    if (file_put_contents($dir . DIRECTORY_SEPARATOR . $name, $value, LOCK_EX) === false) { throw new RuntimeException('Error al guardar ' . $name); }
    chmod($dir . DIRECTORY_SEPARATOR . $name, 0600);
}
echo "Claves generadas en $dir. Copia los valores de secretos.txt a .env; no compartas la clave privada ni secretos.txt.\n";
echo "DOCUMENT_PRIVATE_KEY_PATH=$dir" . DIRECTORY_SEPARATOR . "private.pem\n";
echo "DOCUMENT_PUBLIC_KEY_PATH=$dir" . DIRECTORY_SEPARATOR . "public.pem\n";
