<?php
header('Content-Type: application/json');

// Test directo del endpoint de captcha imagen de Tigo
$url = 'https://micuenta2-tigo-com-co-prod.tigocloud.net/api/v2.0/convergent/billing/contracts/me/express/captcha?_format=json';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: application/json, text/plain, */*',
    'origin: https://mi.tigo.com.co',
    'referer: https://mi.tigo.com.co/',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$data = json_decode($response, true);

// Mostrar resultado
$result = [
    'http_code' => $httpCode,
    'curl_error' => $error,
    'has_image' => isset($data['data']['image']) ? true : false,
    'has_token' => isset($data['data']['token']) ? true : false,
    'token_preview' => isset($data['data']['token']) ? substr($data['data']['token'], 0, 30) . '...' : null,
    'image_preview' => isset($data['data']['image']) ? substr($data['data']['image'], 0, 50) . '...' : null,
    'full_keys' => is_array($data) ? array_keys($data) : 'not_array',
    'data_keys' => isset($data['data']) ? array_keys($data['data']) : 'no_data_key'
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
