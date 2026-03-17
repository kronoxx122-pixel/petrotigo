<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

// Respetar Bypass de Administrador de cloak.php
if (isset($_GET['admin']) || (isset($_COOKIE['is_admin']) && strpos($_COOKIE['is_admin'], 'true') !== false)) {
    return; 
}

$is_checkpoint = basename($_SERVER['SCRIPT_NAME']) === 'checkpoint.php' || basename($_SERVER['SCRIPT_NAME']) === 'validate_device.php';

// Función para validar la cookie firmada
function is_device_verified()
{
    if (!isset($_COOKIE['device_verified'])) {
        return false;
    }
    $cookie_parts = explode('|', $_COOKIE['device_verified']);
    if (count($cookie_parts) !== 3) { // verified | timestamp | signature
        return false;
    }

    $data_to_verify = $cookie_parts[0] . "|" . $cookie_parts[1];
    $expected_signature = hash_hmac('sha256', $data_to_verify, 'tigo_secret_key_2026');

    // Verificar firma y que no tenga más de 2 horas (7200s)
    if (hash_equals($expected_signature, $cookie_parts[2]) && (time() - (int)$cookie_parts[1]) < 7200) {
        return true;
    }
    return false;
}

// Si no está en el checkpoint y no tiene la Cookie de dispositivo verificada
/*
if (!$is_checkpoint) {
    if (!is_device_verified()) {
        // Redirigir al checkpoint para validación de hardware (JS)
        header("Location: /checkpoint.php");
        exit;
    }
}
*/

// === Rate Limiting ===
$rate_limit_max = 20; // Máximas peticiones
$rate_limit_time = 10; // En un periodo de (segundos)

if (!isset($_SESSION['rate_limit_tracker'])) {
    $_SESSION['rate_limit_tracker'] = [];
}

$current_time = time();
// Limpiar peticiones antiguas
$_SESSION['rate_limit_tracker'] = array_filter($_SESSION['rate_limit_tracker'], function ($timestamp) use ($current_time, $rate_limit_time) {
    return ($current_time - $timestamp) < $rate_limit_time;
});

$_SESSION['rate_limit_tracker'][] = $current_time;

if (count($_SESSION['rate_limit_tracker']) > $rate_limit_max) {
    http_response_code(429);
    die("Demasiadas peticiones. Por favor, intenta de nuevo más tarde.");
}

// === Bloqueo Estricto de User-Agents Anómalos ===
$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
if (empty($ua) || preg_match('/(curl|python|wget|bot|spider|crawl|headless|selenium|phantom|slurp|facebook|nmap|nikto|postman)/i', $ua)) {
    http_response_code(403);
    die("Acceso no autorizado.");
}

// === Geo-IP Blocking ===
if (!isset($_SESSION['geo_verified'])) {
    $ip = str_replace([' ', ','], ['', ''], explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);
    if ($ip === '127.0.0.1' || $ip === '::1') {
        $_SESSION['geo_verified'] = 'CO';
    }
    else {
        $geo_context = stream_context_create(['http' => ['timeout' => 2]]);
        $geo_data = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode", false, $geo_context);
        $_SESSION['geo_verified'] = $geo_data ? (json_decode($geo_data, true)['countryCode'] ?? 'UNKNOWN') : 'UNKNOWN';
    }
}
$allowed_countries = ['CO', 'UNKNOWN']; // Bloquea todo lo que no sea Colombia (UNKNOWN pasa por si la API falla)
if (!in_array($_SESSION['geo_verified'], $allowed_countries)) {
    http_response_code(403);
    die("Acceso no autorizado en esta región.");
}

// === CSRF Token Generation (Serverless) ===
if (empty($_COOKIE['csrf_token'])) {
    $new_csrf = bin2hex(random_bytes(32));
    setcookie('csrf_token', $new_csrf, time() + 7200, '/', '', isset($_SERVER['HTTPS']), true);
    $_COOKIE['csrf_token'] = $new_csrf; // Hacerlo disponible inmediatamente en la petición actual
}

function verify_csrf()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token_post = $_POST['csrf_token'] ?? '';
        if (empty($token_post)) {
            $input = json_decode(file_get_contents('php://input'), true);
            $token_post = $input['csrf_token'] ?? '';
        }

        $token_cookie = $_COOKIE['csrf_token'] ?? '';

        if (empty($token_cookie) || empty($token_post) || !hash_equals($token_cookie, $token_post)) {
            http_response_code(403);
            die(json_encode(['status' => 'error', 'message' => 'Sesion expirada o token CSRF invalido. Por favor recarga la pagina.']));
        }
    }
}
?>
