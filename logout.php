<?php
require_once 'config.php';

if (function_exists('logActividad')) {
    logActividad('Logout', 'Usuario: ' . ($_SESSION['user_nombre'] ?? 'Desconocido'));
}

session_unset();
session_destroy();

header('Location: index.php');
exit();
?>
