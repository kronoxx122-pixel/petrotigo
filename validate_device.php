<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

// Permitir peticiones solo por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'msg' => 'Metodo no permitido']);
    exit;
}

// Recibir campos del JS
$token = isset($_POST['fingerprint_token']) ? $_POST['fingerprint_token'] : '';
$touch = isset($_POST['touch']) ? $_POST['touch'] : '0';

// Validación básica del token temporal
// El JS envía: btoa(Date.now().toString()).split('').reverse().join('')
if (empty($token) || $touch !== '1') {
    echo json_encode(['status' => 'error', 'msg' => 'Firma invalida']);
    exit;
}

try {
    // Revertir y decodificar el token para ver si es un timestamp válido cercano al actual
    $reversed = strrev($token);
    $decoded = base64_decode($reversed);

    if (!is_numeric($decoded)) {
        throw new Exception("Token manipulado");
    }

    $time_difference = abs(time() * 1000 - (int)$decoded);

    // Si la diferencia de tiempo es mayor a 60 segundos, rechazar
    // (previene reuso del mismo token POST por un bot)
    if ($time_difference > 60000) {
        throw new Exception("Token expirado");
    }

    // Marca de agua de que el dispositivo pasó las pruebas físicas (JS)
    // Para Vercel: Usar Cookie Firmada en vez de Sessíon
    $cookie_data = "verified|" . time();
    $signature = hash_hmac('sha256', $cookie_data, 'tigo_secret_key_2026');
    $signed_cookie = $cookie_data . "|" . $signature;

    // Cookie expira en 2 horas, disponible en todo el dominio, HTTPS only (si aplica), HttpOnly
    setcookie('device_verified', $signed_cookie, time() + 7200, '/', '', isset($_SERVER['HTTPS']), true);

    echo json_encode(['status' => 'ok']);

}
catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => 'Validacion fallida']);
}
?>
