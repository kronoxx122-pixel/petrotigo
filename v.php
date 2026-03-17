<?php
/**
 * v.php — Endpoint de validación de comportamiento humano
 */
require_once __DIR__ . '/config/cloak.php';

$token = $_POST['token'] ?? '';
$now = time();

if (!empty($token)) {
    cloak_set_cookie('is_human', 'true');
    echo json_encode(['status' => 'ok']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
}
?>
