<?php
header('Content-Type: application/json');

$number = $_GET['n'] ?? '3001063286';
$type = $_GET['type'] ?? 'line';
$results = [];

// Probar GET directo sin token
if ($type === 'document') {
    $url = "https://micuenta2-tigo-com-co-prod.tigocloud.net/api/v2.0/convergent/billing/cc/{$number}/express/balance?_format=json";
} else {
    $url = "https://micuenta2-tigo-com-co-prod.tigocloud.net/api/v2.0/mobile/billing/subscribers/{$number}/express/balance?_format=json";
}

$headers = [
    'accept: application/json, text/plain, */*',
    'accept-language: es-419,es;q=0.9',
    'client-version: 5.20.3',
    'notoken: true',
    'origin: https://mi.tigo.com.co',
    'referer: https://mi.tigo.com.co/',
    'sec-ch-ua: "Chromium";v="146"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"',
    'sec-fetch-dest: empty',
    'sec-fetch-mode: cors',
    'sec-fetch-site: same-site',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'
];

// TEST 1: GET sin nada
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$body1 = curl_exec($ch);
$code1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$results['test1_GET_sin_token'] = [
    'http_code' => $code1,
    'response' => json_decode($body1, true) ?? $body1
];

// TEST 2: POST sin token
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'isCampaign' => false,
    'skipFromCampaign' => false,
    'isAuth' => false,
    'searchType' => $type === 'document' ? 'documents' : 'subscribers',
    'token' => '',
    'documentType' => $type === 'document' ? 'cc' : 'subscribers',
    'email' => '',
    'zrcCode' => ''
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($headers, ['content-type: application/json']));
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$body2 = curl_exec($ch);
$code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$results['test2_POST_sin_token'] = [
    'http_code' => $code2,
    'response' => json_decode($body2, true) ?? $body2
];

// TEST 3: POST con token falso
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'isCampaign' => false,
    'skipFromCampaign' => false,
    'isAuth' => false,
    'searchType' => $type === 'document' ? 'documents' : 'subscribers',
    'token' => 'notoken',
    'documentType' => $type === 'document' ? 'cc' : 'subscribers',
    'email' => '',
    'zrcCode' => ''
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($headers, ['content-type: application/json']));
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$body3 = curl_exec($ch);
$code3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$results['test3_POST_token_falso'] = [
    'http_code' => $code3,
    'response' => json_decode($body3, true) ?? $body3
];

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
