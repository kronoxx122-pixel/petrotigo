<?php
header('Content-Type: application/json');

// --- DETECTAR TIPO DE CAPTCHA DE TIGO ---

function getTigoConfig() {
    $url = 'https://micuenta2-tigo-com-co-prod.tigocloud.net/api/app/configuration?_format=json';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function getTigoImageCaptcha() {
    $url = 'https://micuenta2-tigo-com-co-prod.tigocloud.net/api/v2.0/convergent/billing/contracts/me/express/captcha?_format=json';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function find_recursive($array, $key) {
    if (isset($array[$key])) return $array[$key];
    if (is_array($array)) {
        foreach ($array as $sub) {
            $found = find_recursive($sub, $key);
            if ($found) return $found;
        }
    }
    return null;
}

// 1. Verificar si hay captcha de imagen (Letras y Rayas)
$imageData = getTigoImageCaptcha();
$imageBase64 = find_recursive($imageData, 'image');
$imageToken = find_recursive($imageData, 'token');

if ($imageBase64 && $imageToken) {
    echo json_encode([
        "success" => true,
        "type" => "image",
        "image" => $imageBase64,
        "captchaToken" => $imageToken
    ]);
    exit;
}

// 2. Si no hay imagen, verificar configuración para reCAPTCHA
$config = getTigoConfig();
// El sitio oficial usa 6Ldat4QsAAAAABNF7g9awFqFmozAQD8GYKOsFYm1 para Enterprise
$siteKeyEnterprise = "6Ldat4QsAAAAABNF7g9awFqFmozAQD8GYKOsFYm1";

// Si force_auth_catpcha es true (o si queremos ser proactivos con Enterprise)
echo json_encode([
    "success" => true,
    "type" => "recaptcha-enterprise",
    "siteKey" => $siteKeyEnterprise
]);
?>
