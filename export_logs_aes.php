<?php
require_once 'config.php';

// Forzar reporte de errores (quítalo después de arreglar)
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: text/plain');
        echo 'No autorizado';
        exit();
    }

    // ============================================================
    // CLAVE AES-256: EXACTAMENTE 32 BYTES
    // ============================================================
    $AES_KEY = 'RcS/VGrSn+s99L5yEMlkGrXxdWeyx5CiO/D3/1Nqvek=';

    if (strlen($AES_KEY) !== 32) {
        throw new Exception('La clave debe tener exactamente 32 bytes. Tiene: ' . strlen($AES_KEY));
    }

    $data = $_POST['data'] ?? '';
    if (empty($data)) {
        throw new Exception('Sin datos POST');
    }

    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $AES_KEY, OPENSSL_RAW_DATA, $iv);

    if ($encrypted === false) {
        throw new Exception('openssl_encrypt fallo: ' . openssl_error_string());
    }

    $output = $iv . $encrypted;

    // Limpiar cualquier output buffer previo
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="esp32-logs-' . date('Y-m-d_H-i-s') . '.aes"');
    header('Content-Length: ' . strlen($output));
    header('Cache-Control: no-cache, must-revalidate');

    echo $output;
    exit();

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'ERROR: ' . $e->getMessage();
    exit();
}
