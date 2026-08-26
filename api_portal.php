<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'no_autorizado']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');
$accion = $_GET['action'] ?? ($_POST['action'] ?? '');

/*
 * Portal cautivo - modo laboratorio seguro.
 * No se almacenan correos, contraseñas ni otros secretos.
 * Solo se guardan metadatos del evento: IP, MAC, SSID y tipo de evento.
 */

if ($accion === 'listar') {
    try {
        $stmt = $pdo->query("\n            SELECT id, ip, mac, ssid, evento, fecha\n            FROM portal_registros\n            ORDER BY fecha DESC\n            LIMIT 500\n        ");
        $registros = $stmt->fetchAll();
        echo json_encode(['ok' => true, 'registros' => $registros]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'error_bd', 'detalle' => $e->getMessage()]);
    }
    exit();
}

if ($accion === 'guardar_registro' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = trim(strip_tags($_POST['ip'] ?? ''));
    $mac = strtoupper(trim(strip_tags($_POST['mac'] ?? '')));
    $ssid = trim(strip_tags($_POST['ssid'] ?? ''));
    $evento = trim(strip_tags($_POST['evento'] ?? 'portal_registro'));

    if (empty($ip) || empty($mac)) {
        echo json_encode(['error' => 'campos_incompletos']);
        exit();
    }

    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        echo json_encode(['error' => 'ip_invalida']);
        exit();
    }

    if (!preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $mac)) {
        echo json_encode(['error' => 'mac_invalida']);
        exit();
    }

    if (mb_strlen($ssid, 'UTF-8') > 64) {
        $ssid = mb_substr($ssid, 0, 64, 'UTF-8');
    }

    $eventosPermitidos = ['portal_registro', 'evento_portal_prueba'];
    if (!in_array($evento, $eventosPermitidos, true)) {
        $evento = 'portal_registro';
    }

    try {
        // Evita duplicar exactamente el mismo evento recibido varias veces.
        $stmt = $pdo->prepare("\n            SELECT id\n            FROM portal_registros\n            WHERE ip = ? AND mac = ? AND ssid = ? AND evento = ?\n            ORDER BY fecha DESC\n            LIMIT 1\n        ");
        $stmt->execute([$ip, $mac, $ssid, $evento]);

        if ($stmt->fetch()) {
            echo json_encode(['error' => 'registro_existente']);
            exit();
        }

        $stmt = $pdo->prepare("\n            INSERT INTO portal_registros (ip, mac, ssid, evento, user_id)\n            VALUES (?, ?, ?, ?, ?)\n            RETURNING id\n        ");
        $stmt->execute([$ip, $mac, $ssid, $evento, $_SESSION['user_id']]);
        $id = $stmt->fetchColumn();

        logActividad(
            'Portal Cautivo',
            'Evento de laboratorio: ' . $evento . ' (MAC: ' . $mac . ', IP: ' . $ip . ')'
        );

        echo json_encode(['ok' => true, 'id' => $id]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'error_bd', 'detalle' => $e->getMessage()]);
    }
    exit();
}

echo json_encode(['error' => 'accion_invalida']);
