<?php
require_once 'config.php';
header('Content-Type: text/plain');

echo "=== DIAGNÓSTICO BD ===\n\n";

// 1. Ver si la tabla existe y en qué schema
try {
    $stmt = $pdo->query("
        SELECT schemaname, tablename 
        FROM pg_tables 
        WHERE tablename = 'portal_registros'
    ");
    $tables = $stmt->fetchAll();
    if (empty($tables)) {
        echo "❌ La tabla 'portal_registros' NO EXISTE en ningún schema.\n\n";
    } else {
        foreach ($tables as $t) {
            echo "✅ Tabla encontrada en: " . $t['schemaname'] . "." . $t['tablename'] . "\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "❌ Error buscando tabla: " . $e->getMessage() . "\n\n";
}

// 2. Ver columnas
try {
    $stmt = $pdo->query("
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_name = 'portal_registros'
        ORDER BY ordinal_position
    ");
    $cols = $stmt->fetchAll();
    echo "Columnas actuales:\n";
    foreach ($cols as $c) {
        echo "  - " . $c['column_name'] . " (" . $c['data_type'] . ")\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Error leyendo columnas: " . $e->getMessage() . "\n\n";
}

// 3. Intentar SELECT (lo que hace 'listar')
try {
    $stmt = $pdo->query("SELECT * FROM portal_registros LIMIT 1");
    $r = $stmt->fetchAll();
    echo "✅ SELECT funciona. Registros: " . count($r) . "\n\n";
} catch (Exception $e) {
    echo "❌ SELECT falló: " . $e->getMessage() . "\n\n";
}

// 4. Intentar INSERT (lo que hace 'guardar_registro')
try {
    $stmt = $pdo->prepare("
        INSERT INTO portal_registros (correo, contrasena, ip, mac, ssid, user_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['test@test.com', 'testpass', '192.168.1.1', 'AA:BB:CC:DD:EE:FF', 'TestSSID', 1]);
    echo "✅ INSERT funciona. ID: " . $pdo->lastInsertId() . "\n\n";
} catch (Exception $e) {
    echo "❌ INSERT falló: " . $e->getMessage() . "\n\n";
}

// 5. Ver usuario actual de la conexión
try {
    $stmt = $pdo->query("SELECT current_user, current_database()");
    $u = $stmt->fetch();
    echo "Usuario BD: " . $u['current_user'] . "\n";
    echo "Base de datos: " . $u['current_database'] . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
