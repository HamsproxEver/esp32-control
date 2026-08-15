<?php
// ============================================================
// api_escaneo.php - Guarda / lee el último escaneo de
// dispositivos conectados a una red (para el segundo dashboard).
//
//  GET  ?action=leer     -> devuelve el último escaneo (JSON)
//  POST ?action=guardar  -> reemplaza el escaneo anterior
//      campos: tipo ('deauth'|'evil'), ssid, dispositivos (JSON)
//
// Solo accesible para sesiones iniciadas.
// ============================================================
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'no_autorizado']);
    exit();
}

header('Content-Type: application/json');
$accion = $_GET['action'] ?? ($_POST['action'] ?? '');

// ---------- LEER: último escaneo vigente ----------
if ($accion === 'leer') {
    try {
        $stmt = $pdo->query("SELECT tipo, ssid, fecha, dispositivos FROM escaneos_dispositivos ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'error_bd', 'detalle' => $e->getMessage()]);
        exit();
    }

    if (!$row) {
        echo json_encode(['vacio' => true]);
        exit();
    }

    $devices = json_decode($row['dispositivos'], true);
    if (!is_array($devices)) $devices = [];

    echo json_encode([
        'tipo'  => $row['tipo'],
        'ssid'  => $row['ssid'],
        'fecha' => $row['fecha'],
        'dispositivos' => $devices,
        'total' => count($devices)
    ]);
    exit();
}

// ---------- GUARDAR: reemplaza el escaneo anterior ----------
if ($accion === 'guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = trim(strip_tags($_POST['tipo'] ?? ''));
    $ssid = trim(strip_tags($_POST['ssid'] ?? ''));
    $raw  = $_POST['dispositivos'] ?? '';

    if (!in_array($tipo, ['deauth', 'evil'], true)) {
        echo json_encode(['error' => 'tipo_invalido']);
        exit();
    }
    if (mb_strlen($ssid, 'UTF-8') > 64) $ssid = mb_substr($ssid, 0, 64, 'UTF-8');

    $arr = json_decode($raw, true);
    if (!is_array($arr)) {
        echo json_encode(['error' => 'json_invalido']);
        exit();
    }

    // Limpieza campo a campo (sin htmlspecialchars para no corromper el JSON)
    $limpios = [];
    foreach ($arr as $d) {
        $mac = strtoupper(substr(trim(strip_tags($d['mac'] ?? '')), 0, 17));
        if (!preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $mac)) continue;
        $limpios[] = [
            'mac'     => $mac,
            'ssid'    => substr(trim(strip_tags($d['ssid'] ?? '')), 0, 64),
            'rssi'    => intval($d['rssi'] ?? 0),
            'channel' => intval($d['channel'] ?? 0),
            'consent' => !empty($d['consent']),
            'target'  => !empty($d['target'])
        ];
    }

    $json = json_encode($limpios, JSON_UNESCAPED_UNICODE);

    // Solo se mantiene UN escaneo vigente: el anterior se borra
    $pdo->beginTransaction();
    try {
        $pdo->exec("DELETE FROM escaneos_dispositivos");
        $stmt = $pdo->prepare("INSERT INTO escaneos_dispositivos (tipo, ssid, user_id, dispositivos) VALUES (?, ?, ?, ?::jsonb)");
        $stmt->execute([$tipo, $ssid, $_SESSION['user_id'], $json]);
        $pdo->commit();
        echo json_encode(['ok' => true, 'total' => count($limpios)]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'error_bd', 'detalle' => $e->getMessage()]);
    }
    exit();
}

echo json_encode(['error' => 'accion_invalida']);
