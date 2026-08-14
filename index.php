<?php
require_once 'config.php';

// Si ya está logueado, redirigir
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32-Control - Iniciar sesión</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0d1445 0%, #1a237e 40%, #283593 100%);
            padding: 16px;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(ellipse at 20% 50%, rgba(220, 201, 122, 0.08) 0%, transparent 60%),
                        radial-gradient(ellipse at 80% 50%, rgba(220, 201, 122, 0.05) 0%, transparent 60%);
            pointer-events: none;
        }
        .login-wrapper { width: 100%; max-width: 400px; position: relative; z-index: 1; animation: fadeInUp 0.6s ease-out; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 32px 32px 28px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.30), 0 0 0 1px rgba(255, 255, 255, 0.06) inset;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .login-header { text-align: center; margin-bottom: 24px; }
        .logo-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #1a237e, #283593);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 12px;
            box-shadow: 0 6px 24px rgba(26, 35, 126, 0.25);
            font-size: 28px;
        }
        .login-header h1 { color: #1a237e; font-size: 22px; font-weight: 800; letter-spacing: -0.5px; }
        .login-header .subtitle { color: #6b7a9f; font-size: 11px; font-weight: 500; letter-spacing: 2.5px; text-transform: uppercase; margin-top: 2px; }
        .login-header .divider { width: 32px; height: 3px; background: linear-gradient(90deg, #dcc97a, #1a237e); border-radius: 4px; margin: 10px auto 0; }
        .login-box h2 { color: #1a237e; font-size: 17px; font-weight: 700; text-align: center; margin-bottom: 20px; }
        .input-group { margin-bottom: 14px; position: relative; }
        .input-group label { display: block; color: #1a237e; font-size: 12px; font-weight: 600; margin-bottom: 4px; }
        .input-group .input-icon { position: absolute; left: 14px; top: 36px; color: #9aa8c7; font-size: 16px; }
        .input-group input {
            width: 100%; padding: 11px 14px 11px 42px;
            border: 2px solid #e8ecf5; border-radius: 10px;
            font-size: 14px; font-weight: 500; color: #1a237e;
            background: #f8faff; transition: all 0.3s ease;
            font-family: 'Inter', sans-serif; outline: none;
        }
        .input-group input:focus { border-color: #1a237e; background: white; box-shadow: 0 0 0 4px rgba(26,35,126,0.08); }
        .btn-login {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #1a237e, #283593);
            color: white; border: none; border-radius: 10px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            transition: all 0.3s ease; font-family: 'Inter', sans-serif;
            margin-top: 4px;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(26, 35, 126, 0.30); }
        .error-msg {
            background: #fef2f2; color: #dc2626; padding: 10px 14px;
            border-radius: 8px; margin-bottom: 16px; text-align: center;
            font-size: 13px; font-weight: 600; border-left: 4px solid #dc2626;
        }
        .login-footer { margin-top: 24px; padding-top: 18px; border-top: 1px solid #e8ecf5; text-align: center; }
        .login-footer p { color: #9aa8c7; font-size: 11px; }
        @media (max-width: 480px) {
            .login-card { padding: 24px 20px 20px; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-icon">📡</div>
                <h1>ESP32-Control</h1>
                <p class="subtitle">PANEL DE CONTROL WIFI</p>
                <div class="divider"></div>
            </div>

            <div class="login-box">
                <h2>Iniciar sesión</h2>

                <?php if(isset($_GET['error'])): ?>
                    <div class="error-msg">
                        <?php 
                        if ($_GET['error'] == 'demasiados_intentos') echo 'Demasiados intentos. Espera 15 minutos.';
                        elseif ($_GET['error'] == 'token_invalido') echo 'Error de seguridad. Intenta nuevamente.';
                        elseif ($_GET['error'] == 'sesion_expirada') echo 'Tu sesión ha expirado. Inicia sesión nuevamente.';
                        else echo 'Usuario o contraseña incorrectos.';
                        ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">

                    <div class="input-group">
                        <label for="usuario">Usuario</label>
                        <input type="text" id="usuario" name="usuario" placeholder="Usuario" required>
                        <span class="input-icon">👤</span>
                    </div>

                    <div class="input-group">
                        <label for="contrasena">Contraseña</label>
                        <input type="password" id="contrasena" name="contrasena" placeholder="Contraseña" required>
                        <span class="input-icon">🔒</span>
                    </div>

                    <button type="submit" class="btn-login">Iniciar sesión</button>
                </form>
            </div>

            <div class="login-footer">
                <p>🔐 Autenticación en dos pasos obligatoria</p>
            </div>
        </div>
    </div>
</body>
</html>
