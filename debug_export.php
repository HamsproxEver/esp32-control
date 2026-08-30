<?php
require_once 'config.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Sesion activa: " . (isset($_SESSION['user_id']) ? 'SI (' . $_SESSION['user_id'] . ')' : 'NO') . "\n";

// Prueba la clave
$clave = 'RcS/VGrSn+s99L5yEMlkGrXxdWeyx5CiO/D3/1Nqvek=';
echo "Longitud clave: " . strlen($clave) . " bytes\n";
echo "Es 32: " . (strlen($clave) === 32 ? 'SI' : 'NO') . "\n";

// Prueba cifrado
$iv = random_bytes(16);
$enc = openssl_encrypt('test', 'AES-256-CBC', $clave, OPENSSL_RAW_DATA, $iv);
echo "Cifrado OK: " . ($enc !== false ? 'SI' : 'NO') . "\n";
echo "IV length: " . strlen($iv) . "\n";
