<?php
require_once 'config.php';

// Solo accesible si viene del login sin 2FA
if (!isset($_SESSION['2fa_setup_user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['2fa_setup_user_id'];
$usuario = $_SESSION['2fa_setup_usuario'];
$error = '';
$exito = '';

// PASO 2: Verificar código de prueba y activar
if (isset($_POST['activar_paso2']) && !empty($_SESSION['temp_totp_secret'])) {
    $codigo = preg_replace('/\D/', '', $_POST['codigo_activar']);
    $secret = $_SESSION['temp_totp_secret'];

    if (verifyTOTPCode($secret, $codigo)) {
        // Guardar en BD
        $stmt = $pdo->prepare("UPDATE usuarios SET totp_secret = ?, totp_enabled = TRUE WHERE id = ?");
        $stmt->execute([$secret, $user_id]);

        // Login completo
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_nombre'] = $_SESSION['2fa_setup_nombre'];
        $_SESSION['user_rol'] = $_SESSION['2fa_setup_rol'];
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        unset($_SESSION['2fa_setup_user_id'], $_SESSION['2fa_setup_rol'], 
              $_SESSION['2fa_setup_nombre'], $_SESSION['2fa_setup_usuario'],
              $_SESSION['temp_totp_secret']);

        header('Location: dashboard.php');
        exit();

    } else {
        $error = '❌ Código incorrecto. Intenta escanear el QR de nuevo.';
    }
}

// PASO 1: Generar QR
if (empty($_SESSION['temp_totp_secret'])) {
    $secret_nuevo = generateTOTPSecret(16);
    $_SESSION['temp_totp_secret'] = $secret_nuevo;
} else {
    $secret_nuevo = $_SESSION['temp_totp_secret'];
}
$qr_url = getQRCodeUrl($usuario, $secret_nuevo, 'ESP32-Control');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activar 2FA Obligatorio - ESP32-Control</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, #0d1445 0%, #1a237e 40%, #283593 100%); 
            font-family: 'Inter', sans-serif; 
            display: flex; justify-content: center; align-items: center; 
            min-height: 100vh; margin: 0; padding: 20px;
        }
        .box { 
            background: white; padding: 40px; border-radius: 20px; 
            box-shadow: 0 8px 40px rgba(26,35,126,0.12); 
            text-align: center; max-width: 450px; width: 90%; 
        }
        .box h2 { color: #1a237e; margin-bottom: 10px; font-size: 22px; }
        .warning { 
            background: #fff3e0; color: #e65100; 
            padding: 12px 16px; border-radius: 10px; 
            margin-bottom: 20px; font-weight: 600; font-size: 14px;
        }
        .qr-box { background: #f8f9ff; border-radius: 16px; padding: 20px; margin: 20px 0; display: inline-block; }
        .qr-box img { border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
        .secret-code { 
            background: #1a237e; color: #dcc97a; padding: 12px 20px; 
            border-radius: 10px; font-family: monospace; font-size: 18px; 
            letter-spacing: 4px; margin: 15px 0; display: inline-block; font-weight: 700; 
        }
        .form-codigo { margin-top: 20px; }
        .form-codigo label { 
            display: block; color: #1a237e; font-weight: 600; 
            margin-bottom: 8px; font-size: 13px; text-align: left; 
        }
        .form-codigo input { 
            width: 100%; padding: 14px; font-size: 20px; text-align: center; 
            letter-spacing: 6px; border: 2px solid #e8ecf5; border-radius: 10px; 
            margin-bottom: 16px; font-weight: 700; color: #1a237e; outline: none; 
        }
        .form-codigo input:focus { border-color: #1a237e; box-shadow: 0 0 0 4px rgba(26,35,126,0.08); }
        .btn-verificar { 
            background: #4caf50; color: white; padding: 12px 30px; 
            border: none; border-radius: 10px; font-weight: 700; font-size: 14px; 
            cursor: pointer; transition: 0.3s; width: 100%; 
        }
        .btn-verificar:hover { background: #388e3c; transform: translateY(-2px); }
        .error { 
            background: #ffebee; color: #c62828; padding: 12px 16px; 
            border-radius: 10px; margin-bottom: 16px; font-weight: 600; 
            border-left: 4px solid #c62828; text-align: left; 
        }
        .instrucciones { 
            text-align: left; background: #f8f9ff; padding: 20px; 
            border-radius: 12px; margin-bottom: 20px; 
        }
        .instrucciones ol { margin: 0; padding-left: 20px; color: #444; font-size: 14px; line-height: 1.8; }
        .no-salir { color: #dc3545; font-size: 12px; margin-top: 15px; font-weight: 500; }
    </style>
</head>
<body>
    <div class="box">
        <div style="font-size: 48px; margin-bottom: 15px;">🔐</div>
        <h2>Autenticación en dos pasos obligatoria</h2>

        <div class="warning">⚠️ Debes activar el 2FA para continuar. No puedes acceder al panel sin esta protección.</div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="instrucciones">
            <strong style="color:#1a237e;">Sigue estos pasos:</strong>
            <ol>
                <li>Descarga <strong>Google Authenticator</strong> o <strong>Microsoft Authenticator</strong> en tu celular.</li>
                <li>Escanea el código QR de abajo con la app.</li>
                <li>Ingresa el código de 6 dígitos que aparece en la app para verificar.</li>
            </ol>
        </div>

        <div class="qr-box">
            <img src="<?php echo htmlspecialchars($qr_url); ?>" alt="QR 2FA" width="200" height="200">
        </div>

        <div>
            <p style="font-size:12px; color:#888; margin-bottom:5px;">Si no puedes escanear, ingresa este código manualmente:</p>
            <div class="secret-code"><?php echo chunk_split($secret_nuevo, 4, ' '); ?></div>
        </div>

        <form method="POST" class="form-codigo" style="margin-top:25px;">
            <input type="hidden" name="activar_paso2" value="1">
            <label>Ingresa el código de 6 dígitos de tu app:</label>
            <input type="text" name="codigo_activar" maxlength="6" placeholder="000000" pattern="\d{6}" required autofocus>
            <button type="submit" class="btn-verificar">✅ Activar y entrar al panel</button>
        </form>

        <p class="no-salir">🔒 Esta medida es obligatoria para todos los usuarios del panel ESP32-Control.</p>
    </div>
</body>
</html>
