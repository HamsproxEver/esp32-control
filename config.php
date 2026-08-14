<?php
ob_start();

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'postgres';
$user = getenv('DB_USER') ?: 'postgres';
$pass = getenv('DB_PASS') ?: '';
$jwtSecret = getenv('JWT_SECRET') ?: 'default_secret_change_me';

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

$tiempo_inactividad = 1800;
if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad'] > $tiempo_inactividad)) {
    session_unset();
    session_destroy();
    header('Location: index.php?error=sesion_expirada');
    exit();
}
$_SESSION['ultima_actividad'] = time();

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    die("Error de conexion: " . $e->getMessage());
}

function sanitizar($input) { return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8'); }
function escapar($input) { return htmlspecialchars($input, ENT_QUOTES, 'UTF-8'); }
function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verificarTokenCSRF($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}
function hashPassword($password) { return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]); }
function verificarPassword($password, $hash) { return password_verify($password, $hash); }
function logActividad($accion, $detalle = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO logs_actividad (user_id, usuario, ip, accion, detalle, fecha) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'] ?? '0', $_SESSION['user_nombre'] ?? 'Anonimo', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $accion, $detalle, date('Y-m-d H:i:s')]);
    } catch (Exception $e) {}
}
function verificarSesion() {
    if (!isset($_SESSION['user_id'])) { header('Location: ../index.php'); exit(); }
    $ip_actual = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (isset($_SESSION['ip']) && $_SESSION['ip'] !== $ip_actual) { session_destroy(); header('Location: ../index.php?error=sesion_invalida'); exit(); }
    $ua_actual = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $ua_actual) { session_destroy(); header('Location: ../index.php?error=sesion_invalida'); exit(); }
}

// ============================================
// 2FA / TOTP (Google Authenticator compatible)
// ============================================
function base32_encode($data) {
    $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $encoded = '';
    $bits = 0;
    $value = 0;
    for ($i = 0; $i < strlen($data); $i++) {
        $value = ($value << 8) | ord($data[$i]);
        $bits += 8;
        while ($bits >= 5) {
            $encoded .= $map[($value >> ($bits - 5)) & 31];
            $bits -= 5;
        }
    }
    if ($bits > 0) {
        $encoded .= $map[($value << (5 - $bits)) & 31];
    }
    return $encoded;
}

function base32_decode($input) {
    $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $decoded = '';
    $bits = 0;
    $value = 0;
    $input = strtoupper($input);
    for ($i = 0; $i < strlen($input); $i++) {
        $char = $input[$i];
        $pos = strpos($map, $char);
        if ($pos === false) continue;
        $value = ($value << 5) | $pos;
        $bits += 5;
        if ($bits >= 8) {
            $decoded .= chr(($value >> ($bits - 8)) & 255);
            $bits -= 8;
        }
    }
    return $decoded;
}

function generateTOTPSecret($length = 16) {
    return base32_encode(random_bytes($length));
}

function getTOTPCode($secret, $timeStep = 30, $digits = 6, $time = null) {
    $time = $time ?? time();
    $secret = base32_decode($secret);
    $time = pack('N*', 0) . pack('N*', intval($time / $timeStep));
    $hm = hash_hmac('sha1', $time, $secret, true);
    $offset = ord($hm[19]) & 0x0F;
    $code = (
        ((ord($hm[$offset]) & 0x7F) << 24) |
        ((ord($hm[$offset + 1]) & 0xFF) << 16) |
        ((ord($hm[$offset + 2]) & 0xFF) << 8) |
        (ord($hm[$offset + 3]) & 0xFF)
    ) % pow(10, $digits);
    return str_pad($code, $digits, '0', STR_PAD_LEFT);
}

function verifyTOTPCode($secret, $code, $window = 1) {
    $time = time();
    for ($i = -$window; $i <= $window; $i++) {
        if (getTOTPCode($secret, 30, 6, $time + ($i * 30)) === $code) {
            return true;
        }
    }
    return false;
}

function getQRCodeUrl($username, $secret, $issuer = 'ESP32-Control') {
    $label = urlencode($issuer . ':' . $username);
    $issuer = urlencode($issuer);
    $otpauth = "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}";
    return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($otpauth);
}
?>
