<?php
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
    <title>ESP32-Control - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #1e1e2e; color: #cdd6f4; font-family: 'Inter', sans-serif; min-height: 100vh; }

        /* Header */
        .header {
            background: #181825; padding: 12px 24px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #313244; flex-wrap: wrap; gap: 10px;
        }
        .header-left h1 { color: #89b4fa; font-size: 20px; font-weight: 800; }
        .header-left .subtitle { color: #6c7086; font-size: 10px; letter-spacing: 2px; margin-left: 8px; }
        .header-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .user-name { color: #cdd6f4; font-weight: 700; font-size: 13px; }
        .header-link { color: #89b4fa; text-decoration: none; font-size: 12px; font-weight: 600; 
                       padding: 6px 12px; border-radius: 8px; transition: 0.3s; }
        .header-link:hover { background: #313244; }
        .btn-logout { background: #dc3545; color: white; padding: 6px 16px; border-radius: 8px; 
                      text-decoration: none; font-size: 12px; font-weight: 600; transition: 0.3s; }
        .btn-logout:hover { background: #c82333; }

        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }

        /* Estado */
        .status-bar {
            background: #181825; padding: 16px 24px; border-radius: 12px;
            margin-bottom: 20px; display: flex; justify-content: space-between;
            align-items: center; flex-wrap: wrap; gap: 12px;
            border-left: 4px solid #89b4fa;
        }
        .status-text { font-size: 15px; font-weight: 600; }
        .status-indicator { display: flex; align-items: center; gap: 8px; }
        .led { width: 12px; height: 12px; border-radius: 50%; background: #f38ba8; }
        .led.connected { background: #a6e3a1; box-shadow: 0 0 8px #a6e3a1; }
        .led.scanning { background: #f9e2af; animation: pulse 1s infinite; }
        .led.jamming { background: #f38ba8; animation: pulse 0.2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

        /* Botones */
        .btn {
            background: #313244; color: #cdd6f4; border: 1px solid #45475a;
            border-radius: 8px; padding: 10px 20px; font-weight: 600;
            cursor: pointer; transition: 0.3s; font-family: 'Inter', sans-serif; font-size: 13px;
        }
        .btn:hover { background: #45475a; transform: translateY(-2px); }
        .btn:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }
        .btn-primary { background: #1a237e; color: white; border-color: #283593; }
        .btn-primary:hover { background: #283593; }
        .btn-danger { background: #b91c1c; color: white; border-color: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: #b45309; color: white; border-color: #f59e0b; }
        .btn-warning:hover { background: #d97706; }
        .btn-purple { background: #7c3aed; color: white; border-color: #a78bfa; }
        .btn-purple:hover { background: #8b5cf6; }

        /* Grid */
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } }

        .card {
            background: #181825; border-radius: 12px; padding: 20px;
            border: 1px solid #313244;
        }
        .card h3 { color: #89b4fa; font-size: 15px; margin-bottom: 16px; font-weight: 700; }

        /* Conexión */
        .connect-section { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .port-select {
            background: #313244; color: #cdd6f4; border: 1px solid #45475a;
            border-radius: 8px; padding: 10px 14px; font-family: 'Inter', sans-serif;
            min-width: 280px; font-size: 13px;
        }

        /* Selectores SSID */
        .ssid-row { display: flex; gap: 12px; align-items: center; margin-bottom: 12px; flex-wrap: wrap; }
        .ssid-select {
            background: #313244; color: #cdd6f4; border: 1px solid #45475a;
            border-radius: 8px; padding: 8px 12px; font-family: 'Inter', sans-serif;
            min-width: 220px; font-size: 13px;
        }

        /* Tabla */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #313244; padding: 10px 12px; text-align: left; font-weight: 600; color: #89b4fa; }
        td { padding: 10px 12px; border-bottom: 1px solid #313244; }
        tr:hover { background: #1e1e2e; }
        .badge { padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
        .badge-yes { background: #a6e3a1; color: #1e1e2e; }
        .badge-no { background: #45475a; color: #6c7086; }
        .badge-target { background: #f9e2af; color: #1e1e2e; }

        /* Terminal */
        .terminal {
            background: #11111b; border: 1px solid #313244; border-radius: 12px;
            padding: 16px; font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 12px; height: 200px; overflow-y: auto;
            line-height: 1.6;
        }
        .terminal .log-info { color: #a6adc8; }
        .terminal .log-ok { color: #a6e3a1; }
        .terminal .log-error { color: #f38ba8; }
        .terminal .log-warn { color: #f9e2af; }
        .terminal .log-cmd { color: #89b4fa; }

        /* Botón emergencia */
        .emergency-section { margin-top: 20px; text-align: center; }
        .btn-emergency {
            background: #b91c1c; color: white; padding: 14px 40px;
            border: 2px solid #ef4444; border-radius: 12px;
            font-size: 16px; font-weight: 800; cursor: pointer;
            transition: 0.3s; letter-spacing: 1px;
        }
        .btn-emergency:hover { background: #dc2626; transform: scale(1.05); box-shadow: 0 0 30px rgba(220, 38, 38, 0.4); }

        /* Admin link */
        .admin-link { background: #f9e2af; color: #1e1e2e; padding: 6px 14px; border-radius: 8px; 
                      text-decoration: none; font-size: 12px; font-weight: 700; }
        .admin-link:hover { background: #f5c842; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1>📡 ESP32-Control <span class="subtitle">PANEL DE CONTROL WIFI</span></h1>
        </div>
        <div class="header-right">
            <span class="user-name">👋 <?php echo htmlspecialchars($nombre); ?></span>
            <?php if ($rol == 'administrador'): ?>
                <a href="panel_admin.php" class="admin-link">⚙️ Admin</a>
            <?php endif; ?>
            <a href="seguridad.php" class="header-link">🔐 Seguridad</a>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>

    <div class="container">
        <!-- Estado -->
        <div class="status-bar">
            <div class="status-text" id="state-text">Estado: sin conexión</div>
            <div class="status-indicator">
                <div class="led" id="status-led"></div>
                <span id="status-label">Desconectado</span>
            </div>
        </div>

        <div class="grid">
            <!-- Conexión -->
            <div class="card">
                <h3>🔌 Conexión ESP32</h3>
                <div class="connect-section">
                    <button class="btn btn-primary" id="btn-connect" onclick="connectESP32()">Conectar ESP32</button>
                    <button class="btn" id="btn-refresh" onclick="refreshPorts()">Refrescar</button>
                    <select class="port-select" id="port-select">
                        <option value="">Auto (detectar)</option>
                    </select>
                </div>
            </div>

            <!-- Acciones principales -->
            <div class="card">
                <h3>⚡ Acciones</h3>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
		    <button class="btn btn-primary" id="btn-start" onclick="sendCmd('START')" disabled>▶️ Iniciar estado</button>
                    <button class="btn btn-primary" id="btn-scan" onclick="sendCmd('SCAN')" disabled>📡 Escanear</button>
                    <button class="btn" id="btn-stop" onclick="sendCmd('STOP')" disabled>⏹ Detener</button>
                    <button class="btn" id="btn-list" onclick="sendCmd('LIST_DEVICES')" disabled>📋 Listar</button>
                    <button class="btn" id="btn-ping" onclick="sendCmd('PING')" disabled>🏓 PING</button>
                    <button class="btn" id="btn-status" onclick="sendCmd('STATUS')" disabled>ℹ️ Estado</button>
                </div>
            </div>
        </div>

        <div class="grid">
            <!-- Jamming -->
            <div class="card">
                <h3>📶 Inhibición (Deauth)</h3>
                <div class="ssid-row">
                    <label>Objetivo:</label>
                    <select class="ssid-select" id="ssid-jam"><option value="">— seleccionar SSID —</option></select>
                    <button class="btn btn-warning" id="btn-jam" onclick="jam()" disabled>🚫 INHIBIR</button>
                    <button class="btn" id="btn-unjam" onclick="sendCmd('STOP_JAM')" disabled>✋ Detener</button>
                </div>
            </div>

            <!-- Evil Twin -->
            <div class="card">
                <h3>🕸️ Portal Cautivo (Evil Twin)</h3>
                <div class="ssid-row">
                    <label>Clonar:</label>
                    <select class="ssid-select" id="ssid-evil"><option value="">— seleccionar SSID —</option></select>
                    <button class="btn btn-purple" id="btn-evil" onclick="evilTwin()" disabled>🕸️ Activar</button>
                    <button class="btn" id="btn-unevil" onclick="sendCmd('STOP_EVIL')" disabled>✋ Detener</button>
                </div>
            </div>
        </div>

        <!-- Tabla de dispositivos -->
        <div class="card" style="margin-bottom: 20px;">
            <h3>📊 Dispositivos detectados</h3>
            <div class="table-wrap">
                <table id="devices-table">
                    <thead>
                        <tr>
                            <th>MAC</th>
                            <th>SSID</th>
                            <th>RSSI</th>
                            <th>Canal</th>
                            <th>Objetivo</th>
                            <th>Consentimiento</th>
                        </tr>
                    </thead>
                    <tbody id="devices-tbody">
                        <tr><td colspan="6" style="text-align:center; color:#6c7086;">Sin dispositivos detectados</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Terminal / Logs -->
	<div class="card">
            <h3>🖥️ Terminal / Logs</h3>
            <div class="terminal" id="terminal">
                <div class="log-info">ESP32-Control v2.0 - Listo para conectar</div>
            </div>
            <div style="display:flex; gap:8px; margin-top:10px;">
                <input type="text" id="cmd-input" placeholder="Escribe un comando (SCAN, STATUS, PING, JAM:SSID...)"
                       style="flex:1; background:#313244; color:#cdd6f4; border:1px solid #45475a; border-radius:8px;
                              padding:10px 14px; font-family:'JetBrains Mono',monospace; font-size:12px; outline:none;">
                <button class="btn btn-primary" id="btn-cmd-send" onclick="sendTypedCmd()" disabled>Enviar</button>
            </div>
        </div>

        <!-- Emergencia -->
        <div class="emergency-section">
            <button class="btn-emergency" id="btn-emergency" onclick="emergency()" disabled>
                ⚠️ EMERGENCIA - DETENER TODO ⚠️
            </button>
        </div>
    </div>

    <script>
        // ============================================================
        // WEBSERIAL API - Reemplaza pyserial + esp32_comm.py
        // ============================================================
        let port = null;
        let reader = null;
        let writer = null;
        let devices = {};
        let connected = false;
        let state = 'idle';

        const KNOWN_VIDS = [0x10C4, 0x1A86, 0x0403, 0x303A];

        // Log al terminal
        function log(msg, type = 'info') {
            const term = document.getElementById('terminal');
            const div = document.createElement('div');
            div.className = 'log-' + type;
            div.textContent = '[' + new Date().toLocaleTimeString() + '] ' + msg;
            term.appendChild(div);
            term.scrollTop = term.scrollHeight;
        }

        // Actualizar estado visual
        function updateState(newState, msg) {
            state = newState;
            const led = document.getElementById('status-led');
            const label = document.getElementById('status-label');
            const text = document.getElementById('state-text');

            const states = {
                'idle': { text: 'inactivo', led: 'connected' },
                'scanning': { text: 'ESCANEANDO...', led: 'scanning' },
                'jamming': { text: 'INHIBIENDO', led: 'jamming' },
                'evil_twin': { text: 'PORTAL CAUTIVO ACTIVO', led: 'scanning' },
                'emergency': { text: 'PARADA DE EMERGENCIA', led: 'jamming' }
            };

            const st = states[newState] || { text: newState, led: '' };
            text.textContent = 'Estado: ' + st.text + (msg ? ' — ' + msg : '');
            label.textContent = st.text;
            led.className = 'led ' + st.led;

            log('Estado: ' + st.text + (msg ? ' | ' + msg : ''), 'info');
        }

        // Conectar al ESP32 vía WebSerial
        async function connectESP32() {
            try {
                port = await navigator.serial.requestPort({
                    filters: KNOWN_VIDS.map(v => ({ usbVendorId: v }))
                });
                await port.open({ baudRate: 115200 });

                writer = port.writable.getWriter();
                reader = port.readable.getReader();
                connected = true;

                document.getElementById('btn-connect').textContent = 'Desconectar';
                document.getElementById('btn-connect').onclick = disconnectESP32;
                updateState('idle', 'Conectado');
                log('Conectado al ESP32', 'ok');

		enableControls(true);
                readLoop();

                // Inicializar el ESP32 al conectar (comando START del firmware)
                setTimeout(() => { sendCmd('START'); }, 500);

            } catch (err) {
                log('Error de conexión: ' + err.message, 'error');
            }
        }

        async function disconnectESP32() {
            connected = false;
            if (reader) { await reader.cancel(); reader = null; }
            if (writer) { writer.releaseLock(); writer = null; }
            if (port) { await port.close(); port = null; }

            document.getElementById('btn-connect').textContent = 'Conectar ESP32';
            document.getElementById('btn-connect').onclick = connectESP32;
            updateState('idle', 'Desconectado');
            log('Desconectado del ESP32', 'warn');
            enableControls(false);
        }

        // Enviar comando al ESP32
        async function sendCmd(cmd) {
            if (!connected || !writer) {
                log('Sin conexión', 'error');
                return;
            }
            const encoder = new TextEncoder();
            await writer.write(encoder.encode(cmd + '\r\n'));
            log('→ ' + cmd, 'cmd');
        }
	function sendTypedCmd() {
            const input = document.getElementById('cmd-input');
            const cmd = input.value.trim();
            if (!cmd) return;
            sendCmd(cmd);
            input.value = '';
            log('Enviado manualmente: ' + cmd, 'cmd');
        }

        document.getElementById('cmd-input').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') sendTypedCmd();
        });

        function jam() {
            const ssid = document.getElementById('ssid-jam').value;
            if (!ssid) { log('Selecciona un SSID para inhibir', 'warn'); return; }
            sendCmd('JAM:' + ssid);
        }

        function evilTwin() {
            const ssid = document.getElementById('ssid-evil').value;
            if (!ssid) { log('Selecciona un SSID para clonar', 'warn'); return; }
            sendCmd('EVIL:' + ssid);
        }

        function emergency() {
            if (!confirm('¿Enviar EMERGENCY? Detendrá jamming y portal cautivo.')) return;
            sendCmd('EMERGENCY');
        }

        // Leer respuestas del ESP32
        async function readLoop() {
            const decoder = new TextDecoder();
            let buffer = '';
            while (connected && port.readable) {
                try {
                    const { value, done } = await reader.read();
                    if (done) break;
                    buffer += decoder.decode(value, { stream: true });
                    let lines = buffer.split('\n');
                    buffer = lines.pop();
                    for (let line of lines) {
                        handleLine(line.trim());
                    }
                } catch (e) {
                    log('Error de lectura: ' + e.message, 'error');
                    break;
                }
            }
        }

        // Parsear líneas del ESP32 (igual que esp32_comm.py)
        function handleLine(line) {
            if (!line) return;
            if (line.startsWith('{')) {
                try { dispatch(JSON.parse(line)); } 
                catch (e) { log('[JSON inválido] ' + line, 'error'); }
            } else {
                handleLegacy(line);
            }
        }

        function dispatch(msg) {
            switch(msg.t) {
                case 'status': updateState(msg.type, msg.msg); break;
                case 'device': addOrUpdateDevice(msg); break;
                case 'devices': replaceDevices(msg.items); break;
                case 'jamming': 
                    if (msg.active) {
                        log('Jamming activo: ' + msg.ssid + ' | ch' + msg.channel + 
                            ' | tramas:' + msg.sent + ' | clientes:' + msg.clients, 'warn');
                    } else {
                        log('Jamming detenido: ' + (msg.msg || ''), 'ok');
                    }
                    break;
                case 'consent': updateConsent(msg.mac, msg.consent); break;
                case 'emergency': updateState('emergency', 'PARADA DE EMERGENCIA'); break;
                case 'scan_end': log('Escaneo finalizado: ' + msg.found + ' redes', 'ok'); break;
                case 'log': log('[ESP32] ' + msg.msg, 'info'); break;
                case 'ping': log('ESP32 responde OK (PONG)', 'ok'); break;
                case 'clear': clearDevices(); break;
            }
        }

        function handleLegacy(line) {
            if (line.startsWith('STATUS:')) {
                const st = line.substring(7).toLowerCase();
                if (st.startsWith('scanning')) updateState('scanning', '');
                else if (st.startsWith('jamming')) updateState('jamming', '');
                else updateState('idle', '');
            } else if (line.startsWith('PONG')) {
                log('ESP32 responde OK (PONG)', 'ok');
            }
        }

        // Tabla de dispositivos
        function addOrUpdateDevice(dev) {
            const mac = dev.mac.toUpperCase();
            devices[mac] = {
                mac: mac, ssid: dev.ssid || '', rssi: dev.rssi || 0,
                channel: dev.channel || 0, target: dev.target || false,
                consent: dev.consent || false
            };
            renderDevices();
            updateSSIDSelectors();
        }

        function replaceDevices(items) {
            devices = {};
            for (let item of items || []) {
                const mac = (item.mac || '').toUpperCase();
                if (mac) devices[mac] = {
                    mac: mac, ssid: item.ssid || '', rssi: item.rssi || 0,
                    channel: item.channel || 0, target: item.target || false,
                    consent: item.consent || false
                };
            }
            renderDevices();
            updateSSIDSelectors();
        }

        function updateConsent(mac, consent) {
            const m = (mac || '').toUpperCase();
            if (devices[m]) devices[m].consent = consent;
            renderDevices();
        }

        function clearDevices() {
            devices = {};
            renderDevices();
            updateSSIDSelectors();
            log('Lista de dispositivos vaciada', 'info');
        }

        function renderDevices() {
            const tbody = document.getElementById('devices-tbody');
            const list = Object.values(devices).sort((a, b) => (a.ssid || '').localeCompare(b.ssid || ''));
            if (list.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#6c7086;">Sin dispositivos detectados</td></tr>';
                return;
            }
            tbody.innerHTML = list.map(d => `
                <tr>
                    <td>${d.mac}</td>
                    <td>${esc(d.ssid)}</td>
                    <td>${d.rssi} dBm</td>
                    <td>${d.channel}</td>
                    <td>${d.target ? '<span class="badge badge-target">SÍ</span>' : '<span class="badge badge-no">NO</span>'}</td>
                    <td>${d.consent ? '<span class="badge badge-yes">SÍ</span>' : '<span class="badge badge-no">NO</span>'}</td>
                </tr>
            `).join('');
        }

        function updateSSIDSelectors() {
            const seen = new Set();
            const ssids = [];
            for (let d of Object.values(devices)) {
                if (d.ssid && !seen.has(d.ssid)) { seen.add(d.ssid); ssids.push(d.ssid); }
            }
            ssids.sort((a, b) => a.localeCompare(b));
            for (let id of ['ssid-jam', 'ssid-evil']) {
                const sel = document.getElementById(id);
                const val = sel.value;
                sel.innerHTML = '<option value="">— seleccionar SSID —</option>' +
                    ssids.map(s => `<option value="${esc(s)}">${esc(s)}</option>`).join('');
                sel.value = val;
            }
        }

        function esc(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }

	function enableControls(en) {
            for (let id of ['btn-scan','btn-stop','btn-list','btn-ping','btn-status',
                            'btn-jam','btn-unjam','btn-evil','btn-unevil','btn-emergency',
                            'btn-cmd-send','btn-start']) {
                document.getElementById(id).disabled = !en;
            }
        }



        // Refrescar puertos (placeholder - WebSerial no enumera sin permiso)
        function refreshPorts() {
            log('Haz clic en "Conectar ESP32" y selecciona el puerto en el diálogo del navegador', 'info');
        }

        // Inicializar
        enableControls(false);
        log('Panel listo. Conecta el ESP32 vía USB.', 'info');
    </script>
</body>
</html>
