<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$usuario = $_SESSION['user_nombre'];

$stmt = $pdo->prepare("SELECT totp_enabled, totp_secret FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguridad - ESP32-Control</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #1e1e2e; color: #cdd6f4; font-family: 'Inter', sans-serif; min-height: 100vh; }
        .header {
            background: #181825; padding: 12px 24px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #313244;
        }
        .header-left h1 { color: #89b4fa; font-size: 20px; font-weight: 800; }
        .header-right { display: flex; align-items: center; gap: 12px; }
        .btn { background: #313244; color: #cdd6f4; border: 1px solid #45475a;
               border-radius: 8px; padding: 8px 16px; font-weight: 600;
               cursor: pointer; transition: 0.3s; text-decoration: none; font-size: 13px; }
        .btn:hover { background: #45475a; }
        .btn-primary { background: #1a237e; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card {
            background: #181825; border-radius: 12px; padding: 30px;
            border: 1px solid #313244; text-align: center;
        }
        .card h2 { color: #89b4fa; margin-bottom: 10px; font-size: 22px; }
        .estado-activo { background: #e8f5e9; color: #2e7d32; padding: 12px 20px; 
                         border-radius: 10px; font-weight: 600; margin-bottom: 20px; 
                         display: inline-block; }
        .info-seguridad { text-align: left; background: #1e1e2e; padding: 20px; 
                          border-radius: 12px; margin-top: 20px; }
        .info-seguridad ul { margin: 0; padding-left: 20px; color: #a6adc8; 
                             font-size: 14px; line-height: 1.8; }
        .aviso { color: #6c7086; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left"><h1>🔐 Seguridad de la Cuenta</h1></div>
        <div class="header-right">
            <a href="dashboard.php" class="btn btn-primary">📡 Dashboard</a>
            <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div class="estado-activo">✅ Autenticación en dos pasos ACTIVADA</div>
            <p style="color:#a6adc8; font-size:14px;">
                Tu cuenta está protegida con 2FA. Cada vez que inicies sesión deberás 
                ingresar el código de 6 dígitos de tu app autenticadora.
            </p>

            <div class="info-seguridad">
                <strong style="color:#89b4fa;">Medidas activas en tu cuenta:</strong>
                <ul>
                    <li>🔐 Autenticación en dos pasos (TOTP)</li>
                    <li>🔒 Sesiones seguras con cookies HttpOnly y SameSite</li>
                    <li>⏱️ Timeout de sesión por inactividad (30 min)</li>
                    <li>🛡️ Validación de IP y navegador</li>
                    <li>🔑 Contraseña hasheada con bcrypt</li>
                </ul>
            </div>

            <p class="aviso">
                🔒 La autenticación en dos pasos es <strong>obligatoria</strong> para todos 
                los usuarios del panel ESP32-Control y no puede desactivarse.
            </p>
        </div>
    </div>
</body>
</html>
