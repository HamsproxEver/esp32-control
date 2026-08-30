<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('No autorizado');
}
define('LOG_CIPHER_PASSWORD', 'RcS/VGrSn+s99L5yEMlkGrXxdWeyx5CiO/D3/1Nqvek=');

$data = $_POST['data'] ?? '';
if (empty($data)) {
    http_response_code(400);
    exit('Sin datos');
}

// Derivar clave de 32 bytes (igual que config.php hace con $jwtSecret)
$key = hash('sha256', LOG_CIPHER_PASSWORD, true);

$iv = random_bytes(16);
$encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
if ($encrypted === false) {
    http_response_code(500);
    exit('Error de cifrado');
}

// Formato: base64(iv(16 bytes) + ciphertext)
$output = base64_encode($iv . $encrypted);

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="esp32-logs-' . date('Y-m-d_H-i-s') . '.enc"');
echo $output;
exit();
