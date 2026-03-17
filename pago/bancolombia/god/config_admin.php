<?php
// god/config_admin.php

// Credenciales de acceso al panel
// ¡CAMBIA ESTO EN PRODUCCIÓN!
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'BetGod2024*'); // Contraseña fuerte por defecto

// Incluir configuración global para BD
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
?>