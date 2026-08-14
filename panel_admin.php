<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SESSION['user_rol'] != 'administrador') {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$exito = '';

// Crear nuevo usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_usuario'])) {
    if (!isset($_POST['csrf_token']) || !verificarTokenCSRF($_POST['csrf_token'])) {
        $error = 'Error de seguridad. Intenta nuevamente.';
    } else {
        $nombre = sanitizar($_POST['nombre']);
        $usuario = sanitizar($_POST['usuario']);
        $contrasena = $_POST['contrasena'];
        $rol_id = intval($_POST['rol_id']);

        if (empty($nombre) || empty($usuario) || empty($contrasena)) {
            $error = 'Todos los campos son obligatorios.';
        } elseif (strlen($contrasena) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } else {
            $hash = hashPassword($contrasena);
            try {
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, usuario, contrasena, rol_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nombre, $usuario, $hash, $rol_id]);
                $exito = '✅ Usuario "' . escapar($usuario) . '" creado correctamente. Deberá activar 2FA en su primer login.';
                logActividad('Crear usuario', 'Admin creó usuario: ' . $usuario);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'unique constraint') !== false) {
                    $error = '❌ El usuario "' . escapar($usuario) . '" ya existe.';
                } else {
                    $error = '❌ Error al crear usuario: ' . $e->getMessage();
                }
            }
        }
    }
}

// Listar usuarios
$stmt = $pdo->query("SELECT u.id, u.nombre, u.usuario, u.totp_enabled, r.nombre as rol FROM usuarios u JOIN roles r ON u.rol_id = r.id ORDER BY u.id");
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - ESP32-Control</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #1e1e2e; color: #cdd6f4; font-family: 'Inter', sans-serif; min-height: 100vh; }

        .header {
            background: #181825; padding: 12px 24px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #313244; flex-wrap: wrap; gap: 10px;
        }
        .header-left h1 { color: #89b4fa; font-size: 20px; font-weight: 800; }
        .header-right { display: flex; align-items: center; gap: 12px; }
        .btn { background: #313244; color: #cdd6f4; border: 1px solid #45475a;
               border-radius: 8px; padding: 8px 16px; font-weight: 600;
               cursor: pointer; transition: 0.3s; text-decoration: none; font-size: 13px; }
        .btn:hover { background: #45475a; }
        .btn-primary { background: #1a237e; color: white; }
        .btn-primary:hover { background: #283593; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }

        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }

        .card {
            background: #181825; border-radius: 12px; padding: 24px;
            border: 1px solid #313244; margin-bottom: 20px;
        }
        .card h2 { color: #89b4fa; font-size: 18px; margin-bottom: 20px; }

        .form-row { display: flex; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
        .form-row input, .form-row select {
            background: #313244; color: #cdd6f4; border: 1px solid #45475a;
            border-radius: 8px; padding: 10px 14px; font-family: 'Inter', sans-serif;
            font-size: 13px; flex: 1; min-width: 180px;
        }
        .form-row input:focus, .form-row select:focus { outline: none; border-color: #89b4fa; }

        .mensaje-error { background: #ffebee; color: #c62828; padding: 12px 16px; 
                        border-radius: 10px; margin-bottom: 16px; font-weight: 600; }
        .mensaje-exito { background: #e8f5e9; color: #2e7d32; padding: 12px 16px; 
                         border-radius: 10px; margin-bottom: 16px; font-weight: 600; }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #313244; padding: 10px 12px; text-align: left; font-weight: 600; color: #89b4fa; }
        td { padding: 10px 12px; border-bottom: 1px solid #313244; }
        tr:hover { background: #1e1e2e; }
        .badge { padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
        .badge-ok { background: #a6e3a1; color: #1e1e2e; }
        .badge-warn { background: #f9e2af; color: #1e1e2e; }
        .badge-admin { background: #89b4fa; color: #1e1e2e; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>⚙️ Panel de Administración</h1>
        </div>
        <div class="header-right">
            <a href="dashboard.php" class="btn btn-primary">📡 Dashboard</a>
            <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
        </div>
    </div>

    <div class="container">
        <!-- Crear usuario -->
        <div class="card">
            <h2>➕ Crear nuevo usuario</h2>

            <?php if ($error): ?>
                <div class="mensaje-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($exito): ?>
                <div class="mensaje-exito"><?php echo $exito; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                <input type="hidden" name="crear_usuario" value="1">

                <div class="form-row">
                    <input type="text" name="nombre" placeholder="Nombre completo" required>
                    <input type="text" name="usuario" placeholder="Nombre de usuario" required>
                    <input type="password" name="contrasena" placeholder="Contraseña" required>
                    <select name="rol_id">
                        <option value="1">Estudiante</option>
                        <option value="2">Profesor</option>
                        <option value="3">Administrador</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Crear usuario</button>
                </div>
            </form>
        </div>

        <!-- Lista de usuarios -->
        <div class="card">
            <h2>👥 Usuarios registrados</h2>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>2FA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><?php echo $u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($u['usuario']); ?></td>
                            <td><span class="badge <?php echo $u['rol'] == 'administrador' ? 'badge-admin' : 'badge-ok'; ?>"><?php echo $u['rol']; ?></span></td>
                            <td><?php echo $u['totp_enabled'] ? '<span class="badge badge-ok">✅ Activo</span>' : '<span class="badge badge-warn">⏳ Pendiente</span>'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
