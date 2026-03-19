<?php
/**
 * DIAGNÓSTICO DIRECTO - Tigo Balance
 * Este script aísla y replica EXACTAMENTE la petición del navegador.
 * Sin capas intermedias, sin proxies que puedan fallar.
 */
header('Content-Type: application/json');
set_time_limit(120);

$config = require __DIR__ . '/config.php';
$apiKey = "842d558abb1609e49f1bec6d54106c57";
$number = $_GET['n'] ?? '3001063286';
$useProxy = ($_GET['proxy'] ?? '1') === '1';

$results = [];

// ============================================================
// PASO 1: Resolver CAPTCHA con CapMonster
// ============================================================
$results['step1_captcha'] = ['status' => 'starting'];

$taskData = json_encode([
    'clientKey' => $apiKey,
    'task' => [
        'type' => 'RecaptchaV3EnterpriseTask',
        'websiteURL' => 'https://mi.tigo.com.co/pago-express/facturas',
        'websiteKey' => '6Ldat4QsAAAAABNF7g9awFqFmozAQD8GYKOsFYm1',
        'minScore' => 0.9,
        'pageAction' => 'submit'
    ]
]);

$ch = curl_init('https://api.capmonster.cloud/createTask');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $taskData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$createRes = curl_exec($ch);
curl_close($ch);

$createResult = json_decode($createRes, true);
$results['step1_captcha']['createTask'] = $createResult;

if (!isset($createResult['taskId'])) {
    $results['step1_captcha']['error'] = 'No taskId returned';
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}

$taskId = $createResult['taskId'];
$token = null;

// Polling (max 60s)
for ($i = 0; $i < 30; $i++) {
    sleep(2);
    $ch = curl_init('https://api.capmonster.cloud/getTaskResult');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['clientKey' => $apiKey, 'taskId' => $taskId]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $pollRes = curl_exec($ch);
    curl_close($ch);
    
    $pollResult = json_decode($pollRes, true);
    if (($pollResult['status'] ?? '') === 'ready') {
        $token = $pollResult['solution']['gRecaptchaResponse'] ?? null;
        $results['step1_captcha']['status'] = 'solved';
        $results['step1_captcha']['token_length'] = strlen($token);
        $results['step1_captcha']['attempts'] = $i + 1;
        break;
    }
}

if (!$token) {
    $results['step1_captcha']['error'] = 'Captcha timeout after 60s';
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}

// ============================================================
// PASO 2: Construir petición IDÉNTICA al navegador
// ============================================================
$url = "https://micuenta2-tigo-com-co-prod.tigocloud.net/api/v2.0/mobile/billing/subscribers/{$number}/express/balance?_format=json";

$payload = json_encode([
    "isCampaign" => false,
    "skipFromCampaign" => false,
    "isAuth" => false,
    "searchType" => "subscribers",
    "token" => $token,
    "documentType" => "subscribers",
    "email" => "{$number}@mitigoexpress.com",
    "zrcCode" => ""
]);

$results['step2_request'] = [
    'url' => $url,
    'payload_length' => strlen($payload),
    'payload_preview' => substr($payload, 0, 200) . '...',
    'using_proxy' => $useProxy
];

$ch = curl_init($url);

// Proxy
if ($useProxy && isset($config['proxy_host']) && !empty($config['proxy_host'])) {
    curl_setopt($ch, CURLOPT_PROXY, "{$config['proxy_host']}:{$config['proxy_port']}");
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$config['proxy_user']}:{$config['proxy_pass']}");
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
}

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HEADER, true); // Capturar headers de respuesta
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Headers EXACTOS del navegador
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: application/json, text/plain, */*',
    'accept-language: es-419,es;q=0.9',
    'client-version: 5.20.3',
    'content-type: application/json',
    'notoken: true',
    'origin: https://mi.tigo.com.co',
    'priority: u=1, i',
    'referer: https://mi.tigo.com.co/',
    'sec-ch-ua: "Chromium";v="146", "Not-A.Brand";v="24", "Google Chrome";v="146"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"',
    'sec-fetch-dest: empty',
    'sec-fetch-mode: cors',
    'sec-fetch-site: same-site',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'
]);

$fullResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$curlError = curl_error($ch);
$curlInfo = curl_getinfo($ch);
curl_close($ch);

// Separar headers y body
$responseHeaders = substr($fullResponse, 0, $headerSize);
$responseBody = substr($fullResponse, $headerSize);

$results['step3_response'] = [
    'http_code' => $httpCode,
    'curl_error' => $curlError,
    'response_headers' => $responseHeaders,
    'response_body' => $responseBody,
    'response_body_decoded' => json_decode($responseBody, true),
    'effective_url' => $curlInfo['url'],
    'primary_ip' => $curlInfo['primary_ip'],
    'total_time' => $curlInfo['total_time'],
    'connect_time' => $curlInfo['connect_time']
];

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
