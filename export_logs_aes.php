<?php
while (ob_get_level() > 0) { ob_end_clean(); }
require_once 'config.php';

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo 'No autorizado';
        exit();
    }

    // ============================================================
    // CLAVE EN BASE64 (44 chars) → 32 bytes binarios AES-256
    // ============================================================
    $AES_KEY_B64 = 'RcS/VGrSn+s99L5yEMlkGrXxdWeyx5CiO/D3/1Nqvek=';
    $AES_KEY = base64_decode($AES_KEY_B64, true);

    if ($AES_KEY === false || strlen($AES_KEY) !== 32) {
        throw new Exception('Clave invalida: ' . ($AES_KEY === false ? 'Base64 corrupto' : strlen($AES_KEY) . ' bytes'));
    }

    $data = $_POST['data'] ?? '';
    if (empty($data)) {
        throw new Exception('Sin datos');
    }

    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $AES_KEY, OPENSSL_RAW_DATA, $iv);

    if ($encrypted === false) {
        throw new Exception('openssl fallo: ' . openssl_error_string());
    }

    $output = $iv . $encrypted;

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="esp32-logs-' . date('Y-m-d_H-i-s') . '.aes"');
    header('Content-Length: ' . strlen($output));
    header('Cache-Control: no-store');

    echo $output;
    exit();

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'ERROR: ' . $e->getMessage();
    exit();
}
