<?php
// god/auth.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config_admin.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirigir al login si no hay sesión
    header('Location: index.php');
    exit();
}

// Función para cerrar sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>