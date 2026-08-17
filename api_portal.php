<?php
// ============================================================
// api_portal.php - API para gestionar los registros del portal cautivo
//
//  GET  ?action=listar     -> devuelve todos los registros (JSON)
//  POST ?action=guardar_registro -> guarda un nuevo registro
//      campos: nombres, apellidos, cedula, ip, mac, ssid
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

// ---------- LISTAR REGISTROS ----------
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

// ---------- GUARDAR REGISTRO ----------
if ($accion === 'guardar_registro' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres = trim(strip_tags($_POST['nombres'] ?? ''));
    $apellidos = trim(strip_tags($_POST['apellidos'] ?? ''));
    $cedula = trim(strip_tags($_POST['cedula'] ?? ''));
    $ip = trim(strip_tags($_POST['ip'] ?? ''));
    $mac = strtoupper(trim(strip_tags($_POST['mac'] ?? '')));
    $ssid = trim(strip_tags($_POST['ssid'] ?? ''));

    // Validaciones básicas
    if (empty($nombres) || empty($apellidos) || empty($cedula) || empty($ip) || empty($mac)) {
        echo json_encode(['error' => 'campos_incompletos', 'detalle' => 'Todos los campos son obligatorios']);
        exit();
    }

    // Validar formato de MAC
    if (!preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $mac)) {
        echo json_encode(['error' => 'mac_invalida', 'detalle' => 'Formato de MAC inválido']);
        exit();
    }

    // Validar formato de IP (IPv4 o IPv6)
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        echo json_encode(['error' => 'ip_invalida', 'detalle' => 'Formato de IP inválido']);
        exit();
    }

    // Validar cédula (solo números, longitud entre 8 y 15)
    if (!preg_match('/^[0-9]{8,15}$/', $cedula)) {
        echo json_encode(['error' => 'cedula_invalida', 'detalle' => 'La cédula debe contener solo números (8-15 dígitos)']);
        exit();
    }

    try {
        // Verificar si ya existe un registro con esta cédula y MAC
        $stmt = $pdo->prepare("SELECT id FROM portal_registros WHERE cedula = ? AND mac = ?");
        $stmt->execute([$cedula, $mac]);
        if ($stmt->fetch()) {
            echo json_encode(['error' => 'registro_existente', 'detalle' => 'Ya existe un registro con esta cédula y MAC']);
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

// Si el ESP32 envía datos en formato simple (para compatibilidad)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombres']) && isset($_POST['mac'])) {
    // Reutilizar la lógica anterior
    $_POST['action'] = 'guardar_registro';
    // Ejecutar nuevamente
    $accion = 'guardar_registro';
    // Aquí se ejecutaría el código de guardar registro
    // Pero para evitar duplicación, redirigimos la lógica
    // (en producción, reestructurar para evitar este bloque)
}

echo json_encode(['error' => 'accion_invalida']);
