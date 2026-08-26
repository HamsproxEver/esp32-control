<?php
require_once 'config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO BD ===\n\n";

// 1. Verificar tabla y schema
try {
    $stmt = $pdo->query("\n        SELECT schemaname, tablename\n        FROM pg_tables\n        WHERE tablename = 'portal_registros'\n    ");
    $tables = $stmt->fetchAll();

    if (empty($tables)) {
        echo "❌ La tabla 'portal_registros' NO EXISTE.\n\n";
    } else {
        foreach ($tables as $t) {
            echo "✅ Tabla encontrada en: {$t['schemaname']}.{$t['tablename']}\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "❌ Error buscando tabla: {$e->getMessage()}\n\n";
}

// 2. Ver columnas
try {
    $stmt = $pdo->query("\n        SELECT column_name, data_type, is_nullable\n        FROM information_schema.columns\n        WHERE table_name = 'portal_registros'\n        ORDER BY ordinal_position\n    ");
    $cols = $stmt->fetchAll();

    echo "Columnas actuales:\n";
    foreach ($cols as $c) {
        echo "  - {$c['column_name']} ({$c['data_type']}, nullable={$c['is_nullable']})\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Error leyendo columnas: {$e->getMessage()}\n\n";
}

// 3. SELECT
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM portal_registros");
    echo "✅ SELECT funciona. Registros actuales: " . $stmt->fetchColumn() . "\n\n";
} catch (Exception $e) {
    echo "❌ SELECT falló: {$e->getMessage()}\n\n";
}

// 4. INSERT de laboratorio sin credenciales
try {
    $stmt = $pdo->prepare("\n        INSERT INTO portal_registros (ip, mac, ssid, evento, user_id)\n        VALUES (?, ?, ?, ?, ?)\n        RETURNING id\n    ");
    $stmt->execute([
        '192.168.4.2',
        'AA:BB:CC:DD:EE:FF',
        'SSID-PRUEBA',
        'evento_portal_prueba',
        $_SESSION['user_id'] ?? null
    ]);
    echo "✅ INSERT de prueba funciona. ID: " . $stmt->fetchColumn() . "\n";
    echo "   El registro de prueba NO contiene correo ni contraseña.\n\n";
} catch (Exception $e) {
    echo "❌ INSERT falló: {$e->getMessage()}\n\n";
}

// 5. Conexión
try {
    $stmt = $pdo->query("SELECT current_user, current_database()");
    $u = $stmt->fetch();
    echo "Usuario BD: {$u['current_user']}\n";
    echo "Base de datos: {$u['current_database']}\n";
} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
}
?>
