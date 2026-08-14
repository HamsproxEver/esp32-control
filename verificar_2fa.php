<?php
require_once 'config.php';

if (!isset($_SESSION['2fa_user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = preg_replace('/\D/', '', $_POST['codigo']);

    $stmt = $pdo->prepare("SELECT totp_secret FROM usuarios WHERE id = ? AND totp_enabled = TRUE");
    $stmt->execute([$_SESSION['2fa_user_id']]);
    $user = $stmt->fetch();

    if ($user && verifyTOTPCode($user['totp_secret'], $codigo)) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $_SESSION['2fa_user_id'];
        $_SESSION['user_nombre'] = $_SESSION['2fa_nombre'];
        $_SESSION['user_rol'] = $_SESSION['2fa_rol'];
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        unset($_SESSION['2fa_user_id'], $_SESSION['2fa_rol'], 
              $_SESSION['2fa_nombre'], $_SESSION['2fa_usuario']);

        header('Location: dashboard.php');
        exit();
    } else {
        $error = '❌ Código incorrecto. Inténtalo de nuevo.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación 2FA - ESP32-Control</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, #0d1445 0%, #1a237e 40%, #283593 100%); 
            font-family: 'Inter', sans-serif; 
            display: flex; justify-content: center; align-items: center; 
            min-height: 100vh; margin: 0; 
        }
        .box { 
            background: white; padding: 40px; border-radius: 20px; 
            box-shadow: 0 8px 40px rgba(26,35,126,0.12); 
            text-align: center; max-width: 400px; width: 90%; 
        }
        .box h2 { color: #1a237e; margin-bottom: 10px; font-size: 22px; }
        .box p { color: #666; font-size: 14px; margin-bottom: 25px; }
        .box input { 
            width: 100%; padding: 14px; font-size: 24px; text-align: center; 
            letter-spacing: 8px; border: 2px solid #e8ecf5; border-radius: 12px; 
            margin-bottom: 20px; font-weight: 700; color: #1a237e; outline: none; 
        }
        .box input:focus { border-color: #1a237e; box-shadow: 0 0 0 4px rgba(26,35,126,0.08); }
        .box button { 
            width: 100%; padding: 14px; background: #1a237e; color: white; 
            border: none; border-radius: 12px; font-size: 16px; font-weight: 700; 
            cursor: pointer; transition: 0.3s; 
        }
        .box button:hover { background: #0d1457; transform: translateY(-2px); }
        .error { 
            background: #ffebee; color: #c62828; padding: 12px; 
            border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 600; 
        }
        .icon { font-size: 48px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">🔐</div>
        <h2>Verificación en dos pasos</h2>
        <p>Abre tu app autenticadora e ingresa el código de 6 dígitos.</p>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="codigo" maxlength="6" placeholder="000000" pattern="\d{6}" required autofocus>
            <button type="submit">Verificar y entrar</button>
        </form>
    </div>
</body>
</html>
