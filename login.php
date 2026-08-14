<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verificarTokenCSRF($_POST['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: index.php?error=token_invalido');
        exit();
    }

    // Límite de intentos (5 en 15 min)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM intentos_login WHERE ip = ? AND fecha > NOW() - INTERVAL '15 minutes'");
    $stmt->execute([$ip]);
    $intentos = $stmt->fetchColumn();

    if ($intentos >= 5) {
        header('Location: index.php?error=demasiados_intentos');
        exit();
    }

    // Procesar login
    $usuario = sanitizar($_POST['usuario']);
    $contrasena = $_POST['contrasena'];

    $stmt = $pdo->prepare("SELECT u.*, r.nombre as rol_nombre 
                           FROM usuarios u 
                           JOIN roles r ON u.rol_id = r.id 
                           WHERE u.usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if ($user && verificarPassword($contrasena, $user['contrasena'])) {

        // Borrar intentos fallidos
        $stmt = $pdo->prepare("DELETE FROM intentos_login WHERE ip = ?");
        $stmt->execute([$ip]);

        // ============================================
        // SI NO TIENE 2FA ACTIVADO → OBLIGAR A ACTIVAR
        // ============================================
        if (!$user['totp_enabled'] || empty($user['totp_secret'])) {
            $_SESSION['2fa_setup_user_id'] = $user['id'];
            $_SESSION['2fa_setup_rol'] = $user['rol_nombre'];
            $_SESSION['2fa_setup_nombre'] = $user['nombre'];
            $_SESSION['2fa_setup_usuario'] = $user['usuario'];
            header('Location: activar_2fa_obligatorio.php');
            exit();
        }

        // ============================================
        // SI TIENE 2FA ACTIVADO → IR A VERIFICACIÓN 2FA
        // ============================================
        if ($user['totp_enabled'] && !empty($user['totp_secret'])) {
            $_SESSION['2fa_user_id'] = $user['id'];
            $_SESSION['2fa_rol'] = $user['rol_nombre'];
            $_SESSION['2fa_nombre'] = $user['nombre'];
            $_SESSION['2fa_usuario'] = $user['usuario'];
            header('Location: verificar_2fa.php');
            exit();
        }

    } else {
        $stmt = $pdo->prepare("INSERT INTO intentos_login (ip, usuario, fecha) VALUES (?, ?, NOW())");
        $stmt->execute([$ip, $usuario]);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: index.php?error=1');
        exit();
    }
}

header('Location: index.php');
exit();
?>
