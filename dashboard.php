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
        .hint { color: #6c7086; font-size: 11px; margin-top: 4px; }

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
        .badge-origen { background: #89b4fa; color: #1e1e2e; }

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

        .btn-active { background: #89b4fa; color: #1e1e2e; border-color: #89b4fa; }
        .log-err-bold { font-weight: 700; }
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
            <a href="dashboard_dispositivos.php" class="header-link">📊 Dispositivos</a>
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
                    <button class="btn btn-primary" id="btn-scan" onclick="scanRedes()" disabled>📡 Escanear redes</button>
                    <button class="btn" id="btn-stop" onclick="sendCmd('STOP')" disabled>⏹ Detener</button>
                    <button class="btn" id="btn-list" onclick="sendCmd('LIST_DEVICES')" disabled>📋 Listar</button>
		    <button class="btn" id="btn-ping" onclick="pingESP32()" disabled>🏓 PING</button>
                    <button class="btn" id="btn-status" onclick="sendCmd('STATUS')" disabled>ℹ️ Estado</button>
                </div>
                <p class="hint">"Escanear redes" detecta los puntos de acceso (AP) de alrededor. Los botones "🔎 Escanear dispositivos" de abajo buscan los clientes conectados a la red elegida.</p>
            </div>
        </div>

        <div class="grid">
            <!-- Jamming -->
            <div class="card">
                <h3>📶 Inhibición (Deauth)</h3>
                <div class="ssid-row">
                    <label>Objetivo:</label>
                    <select class="ssid-select" id="ssid-jam"><option value="">— seleccionar SSID —</option></select>
                    <button class="btn btn-primary" id="btn-scan-jam" onclick="scanClients('deauth')" disabled>🔎 Escanear dispositivos</button>
                    <button class="btn btn-warning" id="btn-jam" onclick="jam()" disabled>🚫 INHIBIR</button>
                    <button class="btn" id="btn-unjam" onclick="sendCmd('STOP_JAM')" disabled>✋ Detener</button>
                </div>
                <p class="hint">El escaneo de dispositivos sintoniza el canal de la red elegida y detecta sus clientes (~8 s). Los resultados se envían también al dashboard "📊 Dispositivos".</p>
            </div>

            <!-- Evil Twin -->
            <div class="card">
                <h3>🕸️ Portal Cautivo (Evil Twin)</h3>
                <div class="ssid-row">
                    <label>Clonar:</label>
                    <select class="ssid-select" id="ssid-evil"><option value="">— seleccionar SSID —</option></select>
                    <button class="btn btn-primary" id="btn-scan-evil" onclick="scanClients('evil')" disabled>🔎 Escanear dispositivos</button>
                    <button class="btn btn-purple" id="btn-evil" onclick="evilTwin()" disabled>🕸️ Activar</button>
                    <button class="btn" id="btn-unevil" onclick="sendCmd('STOP_EVIL')" disabled>✋ Detener</button>
                </div>
                <p class="hint">El escaneo de dispositivos aquí es independiente del de Inhibición: al escanear desde un servicio se borran automáticamente los resultados del otro.</p>
            </div>
        </div>

        <!-- Tabla de dispositivos -->
        <div class="card" style="margin-bottom: 20px;">
            <h3>📊 Dispositivos detectados <span class="badge badge-origen" id="tabla-origen" style="display:none;"></span></h3>
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
                <div class="log-info">ESP32-Control v2.1 - Listo para conectar</div>
            </div>
            <div style="display:flex; gap:8px; margin-top:10px;">
                <input type="text" id="cmd-input" placeholder="Escribe un comando (SCAN, STATUS, PING, SCAN_CLIENTS:SSID, JAM:SSID...)"
                       style="flex:1; background:#313244; color:#cdd6f4; border:1px solid #45475a; border-radius:8px;
                              padding:10px 14px; font-family:'JetBrains Mono',monospace; font-size:12px; outline:none;">
                <button class="btn btn-primary" id="btn-cmd-send" onclick="sendTypedCmd()" disabled>Enviar</button>
            </div>
        </div>

        <!-- Registro completo de eventos -->
        <div class="card">
            <h3>📋 Registro de eventos (logs)</h3>
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:12px;">
                <button class="btn btn-active" data-logfilter="all" onclick="setLogFilter('all')">Todos</button>
                <button class="btn" data-logfilter="error" onclick="setLogFilter('error')">✖ Errores</button>
                <button class="btn" data-logfilter="warn" onclick="setLogFilter('warn')">⚠ Avisos</button>
                <button class="btn" data-logfilter="tx" onclick="setLogFilter('tx')">⇢ Comandos</button>
                <button class="btn" data-logfilter="rx" onclick="setLogFilter('rx')">⇠ Respuestas</button>
                <span style="flex:1"></span>
                <button class="btn" onclick="runDiagnostics()">🔍 Diagnóstico</button>
                <button class="btn" onclick="exportLogs()">💾 Guardar</button>
                <button class="btn" onclick="clearLogs()">🗑 Limpiar</button>
            </div>
            <div class="terminal" id="log-console" style="height:260px;">
                <div class="log-info">Registro vacío. Todos los eventos del panel aparecerán aquí.</div>
            </div>
        </div>

        <!-- Emergencia -->
        <div class="emergency-section">
            <button class="btn-emergency" id="btn-emergency" onclick="emergency()">
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
        // Origen del escaneo actual de la tabla: 'redes' | 'deauth' | 'evil' | null
        let scanSource = null;

        const KNOWN_VIDS = [0x10C4, 0x1A86, 0x0403, 0x303A];

        // ============================================================
        // MOTOR DE LOGS: registra TODO (conexión, comandos, respuestas,
        // errores JS, eventos del ESP32) en dos consolas:
        //  - #terminal     : consola rápida (siempre visible)
        //  - #log-console  : registro completo con filtros
        // ============================================================
        const LOG_CSS = { debug:'info', info:'info', ok:'ok', warn:'warn', error:'error', tx:'cmd', rx:'ok', sys:'info' };
        let logHistory = [];
        let logFilter = 'all';
        let rxCount = 0;
        let lastRxAt = 0;
        let connWatchdog = null;

        // Log rápido al terminal (compatibilidad con el código existente)
        function log(msg, type = 'info') {
            logEvent(type, 'sistema', msg);
        }

        function logEvent(level, source, msg, details) {
            const entry = {
                ts: new Date().toLocaleTimeString('es-ES'),
                level: level,
                source: source,
                msg: msg,
                details: details || ''
            };
            logHistory.push(entry);
            if (logHistory.length > 800) logHistory.shift();

            // Terminal rápido
            const term = document.getElementById('terminal');
            if (term) {
                const div = document.createElement('div');
                div.className = 'log-' + (LOG_CSS[level] || 'info');
                div.textContent = '[' + entry.ts + '] ' + entry.msg + (entry.details ? ' — ' + entry.details : '');
                term.appendChild(div);
                while (term.children.length > 400) term.removeChild(term.firstChild);
                term.scrollTop = term.scrollHeight;
            }

            // Consola de logs completa
            const consoleEl = document.getElementById('log-console');
            if (consoleEl) {
                const div = document.createElement('div');
                div.className = 'log-' + (LOG_CSS[level] || 'info');
                div.dataset.level = level;
                div.textContent = '[' + entry.ts + '] [' + level.toUpperCase() + '] [' + source + '] ' + entry.msg + (entry.details ? ' — ' + entry.details : '');
                consoleEl.appendChild(div);
                while (consoleEl.children.length > 500) consoleEl.removeChild(consoleEl.firstChild);
                if (logFilter !== 'all' && logFilter !== level) div.style.display = 'none';
                consoleEl.scrollTop = consoleEl.scrollHeight;
            }
        }

        function setLogFilter(f) {
            logFilter = f;
            document.querySelectorAll('[data-logfilter]').forEach(b => {
                b.classList.toggle('btn-active', b.dataset.logfilter === f);
            });
            const consoleEl = document.getElementById('log-console');
            if (!consoleEl) return;
            for (const child of consoleEl.children) {
                child.style.display = (f === 'all' || child.dataset.level === f) ? '' : 'none';
            }
        }

        function clearLogs() {
            document.getElementById('log-console').innerHTML = '';
            logHistory = [];
            logEvent('ok', 'sistema', 'Registro de logs limpiado.');
        }

        function exportLogs() {
            const lines = logHistory.map(e =>
                '[' + e.ts + '] [' + e.level.toUpperCase() + '] [' + e.source + '] ' + e.msg + (e.details ? ' — ' + e.details : ''));
            const blob = new Blob([lines.join('\n')], { type: 'text/plain;charset=utf-8' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'esp32-control-logs-' + new Date().toISOString().replace(/[:.]/g, '-') + '.txt';
            a.click();
            URL.revokeObjectURL(a.href);
            logEvent('ok', 'sistema', 'Logs exportados: ' + lines.length + ' eventos.');
        }

        function runDiagnostics() {
            logEvent('sys', 'diagnóstico', '══════ DIAGNÓSTICO ══════');
            logEvent('sys', 'diagnóstico', 'Contexto seguro (HTTPS): ' + (window.isSecureContext ? 'SÍ ✔' : 'NO ✘ (WebSerial NO funcionará)'));
            logEvent('sys', 'diagnóstico', 'WebSerial disponible: ' + (navigator.serial ? 'SÍ ✔' : 'NO ✘ (usa Chrome/Edge 89+)'));
            logEvent('sys', 'diagnóstico', 'Navegador: ' + navigator.userAgent);
            logEvent('sys', 'diagnóstico', 'ESP32 conectado: ' + (connected ? 'SÍ ✔' : 'NO ✘'));
            logEvent('sys', 'diagnóstico', 'Datos recibidos del ESP32: ' + rxCount + (rxCount === 0 ? ' (cero — revisa firmware / baud rate 115200 / cable de datos)' : ''));
            logEvent('sys', 'diagnóstico', 'Última actividad del ESP32: ' + (lastRxAt ? new Date(lastRxAt).toLocaleTimeString() : 'nunca'));
            if (connected) {
                logEvent('sys', 'diagnóstico', 'Enviando PING y STATUS de prueba...');
                sendCmd('PING');
                sendCmd('STATUS');
            } else {
                logEvent('warn', 'diagnóstico', 'Pulsa "Conectar" y elige el puerto del ESP32.');
            }
            logEvent('sys', 'diagnóstico', '══════ FIN DIAGNÓSTICO ══════');
        }

        // Actualizar estado visual
        function updateState(newState, msg) {
            state = newState;
            const led = document.getElementById('status-led');
            const label = document.getElementById('status-label');
            const text = document.getElementById('state-text');

	    const states = {
                'idle': { text: 'inactivo', led: 'connected' },
                'active': { text: 'ACTIVO', led: 'connected' },
                'scanning': { text: 'ESCANEANDO...', led: 'scanning' },
                'jamming': { text: 'INHIBIENDO', led: 'jamming' },
                'evil_twin': { text: 'PORTAL CAUTIVO ACTIVO', led: 'scanning' },
                'client_scan': { text: 'ESCANEANDO DISPOSITIVOS...', led: 'scanning' },
                'emergency': { text: 'PARADA DE EMERGENCIA', led: 'jamming' }
            };

            const st = states[newState] || { text: newState, led: '' };
            text.textContent = 'Estado: ' + st.text + (msg ? ' — ' + msg : '');
            label.textContent = st.text;
            led.className = 'led ' + st.led;

            log('Estado: ' + st.text + (msg ? ' | ' + msg : ''), 'info');
        }

        // Conectar al ESP32 vía WebSerial (con diagnóstico completo)
        async function connectESP32() {
            try {
                if (!window.isSecureContext) {
                    logEvent('error', 'conexión', 'No se puede usar WebSerial: la página NO está en un contexto seguro (HTTPS).', 'Sirve el panel con https:// o https://localhost y vuelve a intentar.');
                    return;
                }
                if (!navigator.serial) {
                    logEvent('error', 'conexión', 'Este navegador no soporta la WebSerial API.', 'Usa Chrome o Edge (versión 89+) en escritorio.');
                    return;
                }

                logEvent('info', 'conexión', 'Mostrando selector de puertos USB...');
                port = await navigator.serial.requestPort({
                    filters: KNOWN_VIDS.map(v => ({ usbVendorId: v }))
                });
                if (!port) {
                    logEvent('warn', 'conexión', 'Selector cancelado: no se eligió ningún puerto.');
                    return;
                }

                const info = port.getInfo ? port.getInfo() : {};
                logEvent('info', 'conexión', 'Puerto elegido: VID 0x' + (info.usbVendorId || 0).toString(16) + ' / PID 0x' + (info.usbProductId || 0).toString(16));

                await port.open({ baudRate: 115200 });
                logEvent('ok', 'conexión', 'Puerto abierto correctamente a 115200 baudios.');

                writer = port.writable.getWriter();
                reader = port.readable.getReader();
                connected = true;
                rxCount = 0;

                document.getElementById('btn-connect').textContent = 'Desconectar';
                document.getElementById('btn-connect').onclick = disconnectESP32;
                updateState('idle', 'Conectado');
                logEvent('ok', 'conexión', 'ESP32 conectado. Esperando respuesta del firmware...');

                enableControls(true);
                readLoop();

                // Watchdog: si el ESP32 no envía nada en 3 s, algo falla
                clearTimeout(connWatchdog);
                connWatchdog = setTimeout(() => {
                    if (connected && rxCount === 0) {
                        logEvent('error', 'conexión', 'El ESP32 NO responde (no llegaron datos en 3 segundos).', 'Causas probables: 1) firmware no flasheado, 2) baud rate distinto de 115200, 3) cable USB solo de carga (sin datos), 4) placa en modo download/ROM.');
                    }
                }, 3000);

                // Inicializar el ESP32 al conectar (comando START del firmware)
                setTimeout(() => { sendCmd('START'); }, 500);

            } catch (err) {
                const name = err && err.name ? err.name : 'Error';
                let det = err && err.message ? err.message : String(err);
                if (name === 'NotFoundError') det = 'Se canceló el selector de puertos.';
                else if (name === 'InvalidStateError') det = 'El puerto ya está abierto o no es compatible.';
                else if (name === 'NetworkError') det = 'No se pudo abrir el puerto: ¿cable de datos? ¿placa apagada?';
                else if (name === 'SecurityError') det = 'Permiso denegado por el navegador.';
                logEvent('error', 'conexión', 'Fallo al conectar (' + name + '): ' + (err && err.message ? err.message : String(err)), det);
                connected = false;
                port = null; writer = null; reader = null;
            }
        }

        async function disconnectESP32() {
            clearTimeout(connWatchdog);
            connected = false;
            try {
                if (reader) { await reader.cancel(); reader = null; }
                if (writer) { try { writer.releaseLock(); } catch(e){} writer = null; }
                if (port) { await port.close(); port = null; }
                logEvent('warn', 'conexión', 'ESP32 desconectado manualmente.');
            } catch (e) {
                logEvent('error', 'conexión', 'Error al cerrar el puerto: ' + e.message);
                port = null; writer = null; reader = null;
            }

            document.getElementById('btn-connect').textContent = 'Conectar ESP32';
            document.getElementById('btn-connect').onclick = connectESP32;
            updateState('idle', 'Desconectado');
            enableControls(false);
        }

        // Enviar comando al ESP32
        async function sendCmd(cmd) {
            if (!connected || !writer) {
                logEvent('error', 'panel', 'No se envió "' + cmd + '": el ESP32 no está conectado.');
                return;
            }
            try {
                const encoder = new TextEncoder();
                await writer.write(encoder.encode(cmd + '\r\n'));
                logEvent('tx', 'panel', 'Comando enviado: ' + cmd);
            } catch (e) {
                logEvent('error', 'panel', 'Fallo al escribir "' + cmd + '": ' + e.message, 'El ESP32 puede haberse desconectado del USB.');
            }
        }

	let pingPending = false;
        function pingESP32() {
            if (!connected) {
                logEvent('error', 'panel', 'No se puede hacer PING: el ESP32 no está conectado.');
                return;
            }
            pingPending = true;
            logEvent('info', 'panel', 'Enviando PING (prueba de vida vía STATUS)...');
            sendCmd('STATUS');
            setTimeout(() => { pingPending = false; }, 3000);
        }

        // ============================================================
        // ESCANEOS
        //  - scanRedes():   escanea APs de alrededor (botón de Acciones)
        //  - scanClients(): escanea dispositivos de la red elegida
        //                   (botones de Inhibición y Portal Cautivo).
        //                   Cada servicio es independiente: escanear
        //                   desde uno borra los resultados del otro.
        // ============================================================

        const NOMBRE_ORIGEN = { redes: 'Escaneo de redes', deauth: 'Inhibición (Deauth)', evil: 'Portal Cautivo (Evil Twin)' };

        function scanRedes() {
            scanSource = 'redes';
            sendCmd('SCAN');
        }

        function scanClients(source) {
            const selId = (source === 'deauth') ? 'ssid-jam' : 'ssid-evil';
            const ssid = document.getElementById(selId).value;
            if (!ssid) {
                log('Selecciona un SSID para escanear sus dispositivos', 'warn');
                return;
            }
            // Si hay resultados de otro servicio, se borran automáticamente
            if (scanSource && scanSource !== source) {
                log('Borrando resultados del escaneo anterior (' + (NOMBRE_ORIGEN[scanSource] || scanSource) + ')...', 'info');
                devices = {};
                renderDevices();
                // También se vacía el escaneo guardado para el segundo dashboard
                fetch('api_escaneo.php?action=guardar', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=guardar&tipo=' + source + '&ssid=' + encodeURIComponent(ssid) + '&dispositivos=' + encodeURIComponent('[]')
                }).catch(() => {});
            }
            scanSource = source;
            log('Escaneando dispositivos conectados a: ' + ssid + ' (desde ' + NOMBRE_ORIGEN[source] + ')', 'info');
            sendCmd('SCAN_CLIENTS:' + ssid);
        }

        // Guarda el escaneo actual en la BD para el segundo dashboard
        function guardarEscaneo() {
            if (scanSource !== 'deauth' && scanSource !== 'evil') {
                log('Escaneo de redes: no se guarda en el dashboard de dispositivos', 'info');
                return;
            }
            const selId = (scanSource === 'deauth') ? 'ssid-jam' : 'ssid-evil';
            const ssid = document.getElementById(selId).value;
            const list = Object.values(devices).map(d => ({
                mac: d.mac, ssid: d.ssid, rssi: d.rssi,
                channel: d.channel, consent: d.consent, target: d.target
            }));
            const body = new URLSearchParams({
                action: 'guardar',
                tipo: scanSource,
                ssid: ssid,
                dispositivos: JSON.stringify(list)
            }).toString();
            fetch('api_escaneo.php?action=guardar', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            }).then(r => r.json()).then(d => {
                if (d.ok) {
                    log('Escaneo guardado (' + d.total + ' dispositivos). Disponible en el dashboard 📊 Dispositivos.', 'ok');
                } else {
                    log('Error al guardar el escaneo: ' + (d.error || 'desconocido'), 'error');
                }
            }).catch(e => {
                log('No se pudo guardar el escaneo: ' + e.message, 'error');
            });
        }

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

        // EMERGENCIA: siempre disponible. Si el ESP32 está conectado
        // envía EMERGENCY (detiene jamming, portal y escaneos); si no,
        // lo indica claramente para que el operador conecte el módulo.
        function emergency() {
            if (!connected) {
                logEvent('error', 'emergencia', '⚠️ EMERGENCIA no enviada: el ESP32 no está conectado.', 'Conecta el ESP32 vía USB y pulsa de nuevo. También puedes pulsar el botón físico de emergencia (GPIO4) del módulo.');
                return;
            }
            if (!confirm('⚠️ ¿Enviar EMERGENCY? Se detendrá TODO (jamming, portal cautivo y escaneos).')) return;
            logEvent('warn', 'emergencia', '⚠️ ENVIANDO PARADA DE EMERGENCIA...');
            sendCmd('EMERGENCY');
            // Refuerzo local: limpia estados aunque no llegue confirmación
            updateState('emergency', 'PARADA DE EMERGENCIA enviada');
            setTimeout(() => {
                if (state === 'emergency') {
                    logEvent('info', 'emergencia', 'Esperando confirmación del ESP32...');
                    sendCmd('STATUS');
                }
            }, 1500);
        }

        // Leer respuestas del ESP32
        async function readLoop() {
            const decoder = new TextDecoder();
            let buffer = '';
            while (connected && port && port.readable) {
                try {
                    const { value, done } = await reader.read();
                    if (done) break;
                    rxCount++;
                    lastRxAt = Date.now();
                    if (rxCount === 1) {
                        clearTimeout(connWatchdog);
                        logEvent('ok', 'conexión', 'Primeros datos recibidos del ESP32. ¡El firmware está vivo!');
                    }
                    buffer += decoder.decode(value, { stream: true });
                    let lines = buffer.split('\n');
                    buffer = lines.pop();
                    for (let line of lines) {
                        handleLine(line.trim());
                    }
                } catch (e) {
                    logEvent('error', 'conexión', 'Error leyendo del puerto: ' + e.message, 'El ESP32 se desconectó o el cable falla.');
                    break;
                }
            }
            logEvent('warn', 'conexión', 'Bucle de lectura detenido.');
        }

        // Parsear líneas del ESP32 (igual que esp32_comm.py)
        function handleLine(line) {
            if (!line) return;
            logEvent('rx', 'esp32', line.length > 140 ? line.substring(0, 140) + '…' : line);
            if (line.startsWith('{')) {
                try { dispatch(JSON.parse(line)); }
                catch (e) { logEvent('error', 'esp32', 'JSON inválido recibido: ' + line, '¿Baud rate incorrecto? El firmware debe usar 115200.'); }
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
                case 'scan_end': log('Escaneo de redes finalizado: ' + msg.found + ' redes', 'ok'); break;
                case 'scan_clients_end':
                    log('Escaneo de dispositivos finalizado: ' + msg.found + ' encontrados', 'ok');
                    guardarEscaneo();
                    break;
                case 'log': log('[ESP32] ' + msg.msg, 'info'); break;
                case 'ping': log('ESP32 responde OK (PONG)', 'ok'); break;
                case 'clear': clearDevices(); break;
                default:
                    logEvent('warn', 'esp32', 'Tipo de mensaje desconocido: ' + (msg.t || '(vacío)'));
                    break;
            }
        }

	function handleLegacy(line) {
            if (line.startsWith('DEVICE:')) {
                handleDeviceLine(line);
                return;
            }
            if (line.startsWith('STATUS:')) {
                handleStatusLine(line.substring(7));
                return;
            }
            if (line.startsWith('PONG')) {
                logEvent('ok', 'esp32', 'ESP32 responde OK (PONG)');
                return;
            }
            if (line.indexOf('Comando desconocido') >= 0) {
                logEvent('error', 'esp32', line, 'El firmware no reconoce ese comando.');
                return;
            }
            // Resto de líneas de texto del firmware (banner, portal, etc.)
            // ya quedaron registradas como RX en handleLine().
        }

        // STATUS:ESTADO:mensaje  (READY / ACTIVE / SCANNING / SCAN_COMPLETE / WARNING / ERROR / JAMMING ...)
        function handleStatusLine(status) {
            let st = status;
            let msg = '';
            const sep = status.indexOf(':');
            if (sep >= 0) {
                st = status.substring(0, sep).trim();
                msg = status.substring(sep + 1).trim();
            }
            st = st.toUpperCase();

            if (pingPending) {
                pingPending = false;
                logEvent('ok', 'esp32', '✔ ESP32 responde OK (PONG): ' + status);
            }

            switch (st) {
                case 'READY':
                    updateState('idle', msg || 'Sistema listo');
                    break;
                case 'ACTIVE':
                    updateState('active', msg || 'Sistema activo');
                    break;
                case 'SCANNING':
                    updateState('scanning', msg || 'Escaneando redes...');
                    break;
                case 'SCAN_COMPLETE':
                    updateState('active', msg || 'Escaneo completado');
                    break;
                case 'JAMMING':
                    updateState('jamming', msg || 'Inhibiendo red...');
                    break;
                case 'CLIENT_SCAN':
                    updateState('client_scan', msg || 'Escaneando dispositivos...');
                    break;
                case 'WARNING':
                    logEvent('warn', 'esp32', '⚠ Firmware: ' + msg);
                    break;
                case 'ERROR':
                    logEvent('error', 'esp32', '❌ Firmware: ' + msg);
                    break;
                default:
                    logEvent('warn', 'esp32', 'Estado del firmware no reconocido: ' + status);
            }
        }

        // DEVICE:MAC(6 pares):SSID:RSSI:CANAL:CONSENT
        function handleDeviceLine(line) {
            const parts = line.split(':');
            if (parts.length < 10) {
                logEvent('warn', 'esp32', 'Línea DEVICE malformada: ' + line);
                return;
            }
            const mac = (parts[1] + ':' + parts[2] + ':' + parts[3] + ':' + parts[4] + ':' + parts[5] + ':' + parts[6]).toUpperCase();
            const rssi = parseInt(parts[parts.length - 3], 10) || 0;
            const channel = parseInt(parts[parts.length - 2], 10) || 0;
            const consentStr = (parts[parts.length - 1] || '').toUpperCase();
            const consent = (consentStr === 'CONSENT' || consentStr === 'YES' || consentStr === 'SI');
            const ssid = parts.slice(7, parts.length - 3).join(':').trim();
            addOrUpdateDevice({
                mac: mac, ssid: ssid, rssi: rssi,
                channel: channel, target: false, consent: consent
            });
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
            const badge = document.getElementById('tabla-origen');
            if (scanSource && NOMBRE_ORIGEN[scanSource]) {
                badge.textContent = NOMBRE_ORIGEN[scanSource];
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
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
                            'btn-jam','btn-unjam','btn-evil','btn-unevil',
                            'btn-scan-jam','btn-scan-evil',
                            'btn-cmd-send','btn-start']) {
                document.getElementById(id).disabled = !en;
            }
        }

        // Terminal de comandos manuales
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

        // Refrescar puertos (placeholder - WebSerial no enumera sin permiso)
        function refreshPorts() {
            log('Haz clic en "Conectar ESP32" y selecciona el puerto en el diálogo del navegador', 'info');
        }

        // ============================================================
        // INICIALIZACIÓN: detección global de errores y desconexiones
        // ============================================================
        enableControls(false);

        // Errores de JavaScript (los que rompen el panel silenciosamente)
        window.addEventListener('error', (e) => {
            logEvent('error', 'navegador', 'Error de JavaScript: ' + (e.message || 'desconocido'), (e.filename || '') + ' · línea ' + (e.lineno || '?'));
        });
        window.addEventListener('unhandledrejection', (e) => {
            logEvent('error', 'navegador', 'Promesa rechazada sin controlar: ' + (e.reason && e.reason.message ? e.reason.message : String(e.reason || 'desconocido')));
        });

        // Detectar desconexión física del ESP32 (cable USB retirado)
        if (navigator.serial) {
            navigator.serial.addEventListener('disconnect', (e) => {
                if (e.target === port) {
                    clearTimeout(connWatchdog);
                    connected = false;
                    logEvent('error', 'conexión', '¡El ESP32 se desconectó del USB!', 'Revisa el cable, el puerto o un reinicio de la placa.');
                    if (reader) { reader.cancel().catch(()=>{}); reader = null; }
                    if (writer) { try { writer.releaseLock(); } catch(err){} writer = null; }
                    if (port) { port.close().catch(()=>{}); port = null; }
                    document.getElementById('btn-connect').textContent = 'Conectar ESP32';
                    document.getElementById('btn-connect').onclick = connectESP32;
                    enableControls(false);
                    updateState('idle', 'ESP32 desconectado');
                }
            });
        }

        logEvent('info', 'sistema', 'Panel cargado. Listo para conectar el ESP32 vía USB.');
        if (!window.isSecureContext) {
            logEvent('error', 'sistema', '¡La página NO está en HTTPS! WebSerial no funcionará.', 'Usa https:// o https://localhost.');
        }
        if (!navigator.serial) {
            logEvent('error', 'sistema', 'Este navegador no tiene WebSerial (usa Chrome/Edge 89+).');
        }
    </script>
</body>
</html>
