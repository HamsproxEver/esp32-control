<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('No autorizado');
}
define('AES_KEY', 'RcS/VGrSn+s99L5yEMlkGrXxdWeyx5CiO/D3/1Nqvek=');

$data = $_POST['data'] ?? '';
if (empty($data)) {
    http_response_code(400);
    exit('Sin datos');
}

$key = AES_KEY;
if (strlen($key) !== 32) {
    http_response_code(500);
    exit('La clave debe tener exactamente 32 bytes');
}

$iv = random_bytes(16);
$encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
if ($encrypted === false) {
    http_response_code(500);
    exit('Error de cifrado');
}

$output = $iv . $encrypted;

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="esp32-logs-' . date('Y-m-d_H-i-s') . '.aes"');
header('Content-Length: ' . strlen($output));
echo $output;
exit();
