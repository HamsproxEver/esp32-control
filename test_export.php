<?php
require_once 'config.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "1. Sesion user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NO DEFINIDO') . "\n";

$clave = 'MiClaveSecretaESP32!2024#Segura';
echo "2. Clave longitud: " . strlen($clave) . " (debe ser 32)\n";

$iv = random_bytes(16);
echo "3. IV generado: " . strlen($iv) . " bytes\n";

$test = openssl_encrypt('hola', 'AES-256-CBC', $clave, OPENSSL_RAW_DATA, $iv);
echo "4. Cifrado test: " . ($test === false ? 'FALLO: ' . openssl_error_string() : 'OK (' . strlen($test) . ' bytes)') . "\n";

// Simular POST
$_POST['data'] = "linea1\nlinea2";
$data = $_POST['data'] ?? '';

$iv = random_bytes(16);
$enc = openssl_encrypt($data, 'AES-256-CBC', $clave, OPENSSL_RAW_DATA, $iv);
$output = $iv . $enc;
echo "5. Output final: " . strlen($output) . " bytes\n";
echo "6. Primeros 16 bytes (IV): " . bin2hex(substr($output, 0, 16)) . "\n";
echo "</pre>";
