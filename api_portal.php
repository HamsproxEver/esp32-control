<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'no_autorizado']);
    exit();
}

header('Content-Type: application/json');
$accion = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($accion === 'listar') {
    try {
        $stmt = $pdo->query("SELECT id, nombres, apellidos, cedula, ip, mac, ssid, fecha FROM portal_registros ORDER BY fecha DESC LIMIT 500");
        $registros = $stmt->fetchAll();
        echo json_encode(['ok' => true, 'registros' => $registros]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'error_bd', 'detalle' => $e->getMessage()]);
    }
    exit();
}

if ($accion === 'guardar_registro' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres = trim(strip_tags($_POST['nombres'] ?? ''));
    $apellidos = trim(strip_tags($_POST['apellidos'] ?? ''));
    $cedula = trim(strip_tags($_POST['cedula'] ?? ''));
    $ip = trim(strip_tags($_POST['ip'] ?? ''));
    $mac = strtoupper(trim(strip_tags($_POST['mac'] ?? '')));
    $ssid = trim(strip_tags($_POST['ssid'] ?? ''));

    if (empty($nombres) || empty($apellidos) || empty($cedula) || empty($ip) || empty($mac)) {
        echo json_encode(['error' => 'campos_incompletos']);
        exit();
    }

    if (!preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $mac)) {
        echo json_encode(['error' => 'mac_invalida']);
        exit();
    }

    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        echo json_encode(['error' => 'ip_invalida']);
        exit();
    }

    if (!preg_match('/^[0-9]{8,15}$/', $cedula)) {
        echo json_encode(['error' => 'cedula_invalida']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM portal_registros WHERE cedula = ? AND mac = ?");
        $stmt->execute([$cedula, $mac]);
        if ($stmt->fetch()) {
            echo json_encode(['error' => 'registro_existente']);
            exit();
        }

        $stmt = $pdo->prepare("
            INSERT INTO portal_registros (nombres, apellidos, cedula, ip, mac, ssid, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nombres, $apellidos, $cedula, $ip, $mac, $ssid, $_SESSION['user_id']]);

        logActividad('Portal Cautivo', 'Nuevo registro: ' . $nombres . ' ' . $apellidos . ' (Cédula: ' . $cedula . ', MAC: ' . $mac . ', IP: ' . $ip . ')');

        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'error_bd', 'detalle' => $e->getMessage()]);
    }
    exit();
}

echo json_encode(['error' => 'accion_invalida']);
