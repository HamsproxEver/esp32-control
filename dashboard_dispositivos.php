<?php
// ============================================================
// dashboard_dispositivos.php - SEGUNDO DASHBOARD
// Muestra los dispositivos conectados a la red objetivo,
// obtenidos por los botones "🔎 Escanear dispositivos" del
// dashboard principal (Inhibición / Portal Cautivo).
// Se actualiza solo cada 4 segundos vía api_escaneo.php.
// ============================================================
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$rol = $_SESSION['user_rol'];
$nombre = $_SESSION['user_nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispositivos Conectados - ESP32-Control</title>
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
        .header-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .user-name { color: #cdd6f4; font-weight: 700; font-size: 13px; }
        .header-link { color: #89b4fa; text-decoration: none; font-size: 12px; font-weight: 600; 
                       padding: 6px 12px; border-radius: 8px; transition: 0.3s; }
        .header-link:hover { background: #313244; }
        .btn-logout { background: #dc3545; color: white; padding: 6px 16px; border-radius: 8px; 
                      text-decoration: none; font-size: 12px; font-weight: 600; transition: 0.3s; }
        .btn-logout:hover { background: #c82333; }

        /* Admin link */
        .admin-link { background: #f9e2af; color: #1e1e2e; padding: 6px 14px; border-radius: 8px; 
                      text-decoration: none; font-size: 12px; font-weight: 700; }
        .admin-link:hover { background: #f5c842; }

        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }

        .card {
            background: #181825; border-radius: 12px; padding: 20px;
            border: 1px solid #313244; margin-bottom: 20px;
        }
        .card h3 { color: #89b4fa; font-size: 15px; margin-bottom: 16px; font-weight: 700; }

        .info-bar {
            background: #181825; padding: 16px 24px; border-radius: 12px;
            margin-bottom: 20px; display: flex; justify-content: space-between;
            align-items: center; flex-wrap: wrap; gap: 12px;
            border-left: 4px solid #89b4fa;
        }
        .info-item { font-size: 14px; }
        .info-item strong { color: #89b4fa; }
        .badge { padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
        .badge-deauth { background: #f9e2af; color: #1e1e2e; }
        .badge-evil { background: #cba6f7; color: #1e1e2e; }
        .badge-none { background: #45475a; color: #a6adc8; }
        .badge-yes { background: #a6e3a1; color: #1e1e2e; }
        .badge-no { background: #45475a; color: #6c7086; }
        .badge-target { background: #f9e2af; color: #1e1e2e; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #313244; padding: 10px 12px; text-align: left; font-weight: 600; color: #89b4fa; }
        td { padding: 10px 12px; border-bottom: 1px solid #313244; }
        tr:hover { background: #1e1e2e; }

        .empty { text-align: center; color: #6c7086; padding: 30px 0; font-size: 14px; }
        .live-dot { width: 10px; height: 10px; border-radius: 50%; background: #a6e3a1; display: inline-block; 
                    box-shadow: 0 0 8px #a6e3a1; margin-right: 6px; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
        .muted { color: #6c7086; font-size: 12px; }
        .btn { background: #313244; color: #cdd6f4; border: 1px solid #45475a;
               border-radius: 8px; padding: 8px 16px; font-weight: 600;
               cursor: pointer; transition: 0.3s; font-family: 'Inter', sans-serif; font-size: 13px; }
        .btn:hover { background: #45475a; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>📊 Dispositivos Conectados</h1>
        </div>
        <div class="header-right">
            <span class="user-name">👋 <?php echo htmlspecialchars($nombre); ?></span>
            <?php if ($rol == 'administrador'): ?>
                <a href="panel_admin.php" class="admin-link">⚙️ Admin</a>
            <?php endif; ?>
            <a href="dashboard.php" class="header-link">📡 Dashboard</a>
            <a href="seguridad.php" class="header-link">🔐 Seguridad</a>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>

    <div class="container">
        <div class="info-bar" id="info-bar">
            <div class="info-item"><span class="live-dot"></span> <strong>Escaneo vigente:</strong> <span id="lbl-tipo">—</span></div>
            <div class="info-item"><strong>Red objetivo:</strong> <span id="lbl-ssid">—</span></div>
            <div class="info-item"><strong>Dispositivos:</strong> <span id="lbl-total">0</span></div>
            <div class="info-item"><strong>Fecha:</strong> <span id="lbl-fecha">—</span></div>
            <button class="btn" onclick="refresh()">🔄 Actualizar</button>
        </div>

        <div class="card">
            <h3>🖧 Dispositivos conectados a la red</h3>
            <p class="muted" style="margin-bottom:12px;">Aquí solo aparecen los dispositivos cuya MAC fue capturada al conectarse al portal cautivo. Durante el escaneo de la red objetivo sus MAC se mantienen ocultas; al entrar en el portal cautivo y autorizar la captura, el dispositivo aparece aquí automáticamente. Se actualiza cada 4 segundos.</p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>MAC</th>
                            <th>SSID</th>
                            <th>RSSI</th>
                            <th>Canal</th>
                            <th>Consentimiento</th>
                            <th>Objetivo</th>
                        </tr>
                    </thead>
                    <tbody id="devices-tbody">
                        <tr><td colspan="6" class="empty">Sin escaneo aún. Ve al 📡 Dashboard, selecciona una red y pulsa "🔎 Escanear dispositivos". Las MAC se capturan cuando cada dispositivo se conecta al portal cautivo.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const TIPO_LABEL = {
            'deauth': '<span class="badge badge-deauth">📶 Inhibición (Deauth)</span>',
            'evil':   '<span class="badge badge-evil">🕸️ Portal Cautivo (Evil Twin)</span>'
        };

        function esc(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }

        async function refresh() {
            try {
                const r = await fetch('api_escaneo.php?action=leer', { credentials: 'same-origin' });
                if (r.status === 401) { window.location.href = 'index.php'; return; }
                const data = await r.json();

                if (data.vacio) {
                    document.getElementById('lbl-tipo').innerHTML = '<span class="badge badge-none">Sin escaneo</span>';
                    document.getElementById('lbl-ssid').textContent = '—';
                    document.getElementById('lbl-total').textContent = '0';
                    document.getElementById('lbl-fecha').textContent = '—';
                    document.getElementById('devices-tbody').innerHTML =
                        '<tr><td colspan="6" class="empty">Sin escaneo aún. Ve al 📡 Dashboard, selecciona una red y pulsa "🔎 Escanear dispositivos". Las MAC se capturan cuando cada dispositivo se conecta al portal cautivo.</td></tr>';
                    return;
                }

                document.getElementById('lbl-tipo').innerHTML = TIPO_LABEL[data.tipo] || '<span class="badge badge-none">' + esc(data.tipo) + '</span>';
                document.getElementById('lbl-ssid').textContent = data.ssid || '—';
                document.getElementById('lbl-total').textContent = data.total || 0;
                document.getElementById('lbl-fecha').textContent = data.fecha || '—';

                const tbody = document.getElementById('devices-tbody');
                const list = data.dispositivos || [];
                if (list.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="empty">Aún no se ha capturado ninguna MAC: los dispositivos aparecerán aquí cuando se conecten al portal cautivo.</td></tr>';
                    return;
                }
                tbody.innerHTML = list.map(d => `
                    <tr>
                        <td>${esc(d.mac)}</td>
                        <td>${esc(d.ssid)}</td>
                        <td>${d.rssi ? d.rssi + ' dBm' : '—'}</td>
                        <td>${d.channel || '—'}</td>
                        <td>${d.consent ? '<span class="badge badge-yes">SÍ</span>' : '<span class="badge badge-no">NO</span>'}</td>
                        <td>${d.target ? '<span class="badge badge-target">SÍ</span>' : '<span class="badge badge-no">NO</span>'}</td>
                    </tr>
                `).join('');
            } catch (e) {
                // No romper la página; reintentará en el próximo ciclo
                console.error('Error refrescando escaneo:', e);
            }
        }

        refresh();
        setInterval(refresh, 4000);
    </script>
</body>
</html>
