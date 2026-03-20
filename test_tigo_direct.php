<?php
/**
 * DIAGNÓSTICO DIRECTO - Tigo Balance
 * Usa 2Captcha para resolver reCAPTCHA v3 Enterprise.
 * Sin proxy (BrightData bloquea POST sin KYC).
 */
header('Content-Type: application/json');
set_time_limit(120);

$twoCaptchaKey = "79d0a9dbd67f003b17f58c5ac657cefb";
$number = $_GET['n'] ?? '3001063286';
$type = $_GET['type'] ?? 'line'; // line o document

$results = [];

// ============================================================
// PASO 1: Resolver CAPTCHA con 2Captcha
// ============================================================
$results['step1_captcha'] = ['status' => 'creating_task', 'service' => '2captcha'];

$taskData = json_encode([
    'clientKey' => $twoCaptchaKey,
    'task' => [
        'type' => 'RecaptchaV3TaskProxyless',
        'websiteURL' => 'https://mi.tigo.com.co/pago-express/facturas',
        'websiteKey' => '6Ldat4QsAAAAABNF7g9awFqFmozAQD8GYKOsFYm1',
        'minScore' => 0.9,
        'pageAction' => 'submit',
        'isEnterprise' => true
    ]
]);

$ch = curl_init('https://api.2captcha.com/createTask');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $taskData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$createRes = curl_exec($ch);
$createErr = curl_error($ch);
curl_close($ch);

$createResult = json_decode($createRes, true);
$results['step1_captcha']['createTask_response'] = $createResult;
$results['step1_captcha']['createTask_curl_error'] = $createErr;

if (!isset($createResult['taskId']) || ($createResult['errorId'] ?? 1) !== 0) {
    $results['step1_captcha']['error'] = 'No se pudo crear la tarea: ' . ($createResult['errorDescription'] ?? $createRes);
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$taskId = $createResult['taskId'];
$token = null;

// Polling (max 90s)
for ($i = 0; $i < 30; $i++) {
    sleep(3);
    $ch = curl_init('https://api.2captcha.com/getTaskResult');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['clientKey' => $twoCaptchaKey, 'taskId' => $taskId]));
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
    if (($pollResult['errorId'] ?? 0) !== 0) {
        $results['step1_captcha']['error'] = 'Error en poll: ' . ($pollResult['errorDescription'] ?? '');
        echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!$token) {
    $results['step1_captcha']['error'] = 'Captcha timeout after 90s';
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// PASO 2: Petición a Tigo SIN PROXY
// ============================================================
if ($type === 'document') {
    $url = "https://micuenta2-tigo-com-co-prod.tigocloud.net/api/v2.0/convergent/billing/cc/{$number}/express/balance?_format=json";
    $payload = [
        "isCampaign" => false, "skipFromCampaign" => false, "isAuth" => false,
        "searchType" => "documents", "token" => $token,
        "documentType" => "cc", "email" => "", "zrcCode" => ""
    ];
} else {
    $url = "https://micuenta2-tigo-com-co-prod.tigocloud.net/api/v2.0/mobile/billing/subscribers/{$number}/express/balance?_format=json";
    $payload = [
        "isCampaign" => false, "skipFromCampaign" => false, "isAuth" => false,
        "searchType" => "subscribers", "token" => $token,
        "documentType" => "subscribers", "email" => "{$number}@mitigoexpress.com", "zrcCode" => ""
    ];
}

$payloadJson = json_encode($payload);

$results['step2_request'] = [
    'url' => $url,
    'payload_length' => strlen($payloadJson),
    'type' => $type
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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
curl_close($ch);

$responseHeaders = substr($fullResponse, 0, $headerSize);
$responseBody = substr($fullResponse, $headerSize);

$results['step3_response'] = [
    'http_code' => $httpCode,
    'curl_error' => $curlError,
    'response_body_decoded' => json_decode($responseBody, true),
    'response_headers' => $responseHeaders
];

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
