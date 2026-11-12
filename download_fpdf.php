<?php
$zipFile = 'fpdf.zip';
file_put_contents($zipFile, file_get_contents('http://www.fpdf.org/en/dl.php?v=186&f=zip'));
$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    $zip->extractTo(__DIR__ . '/fpdf_lib');
    $zip->close();
    unlink($zipFile);
    echo "FPDF descargado correctamente.";
} else {
    echo "Error al extraer FPDF.";
}
?>
