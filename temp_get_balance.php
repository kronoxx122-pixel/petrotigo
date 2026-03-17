<?php
header('Content-Type: application/json');

// --- CONFIGURACI├ôN ---
$apiKey = "b3c3ad6766be69c1e578fdcc66a87d16"; // Llave NUEVA y CORRECTA
$siteKeyTigo = "6LcS1L4pAAAAABHgXhZN6do4Ce7-D0jOEmXxg3H6";
$pageUrlTigo = "https://mi.tigo.com.co/pago-express/facturas?origin=web";

// Recibir datos del frontend
$input = json_decode(file_get_contents('php://input'), true);
// Support both old 'phoneNumber' and new 'value' keys
$value = $input['value'] ?? ($input['phoneNumber'] ?? '3002727129');
$type = $input['type'] ?? 'line'; // 'line' or 'document'

// 1. Mock para pruebas (Ahorrar saldo y tiempo)
if ($value === '3002727129') {
    echo json_encode([
        "success" => true,
        "balance" => "$ 110.635",
        "dueDate" => "10/01/2026",
        "full_data" => ["mock" => true]
    ]);
    exit;
}

// 2. Resolver Captcha
function solveAntiCaptcha($apiKey, $siteKey, $pageUrl)
{
    $payload = [
        "clientKey" => $apiKey,
        "task" => [
            "type" => "RecaptchaV2TaskProxyless",
            "websiteURL" => $pageUrl,
            "websiteKey" => $siteKey
        ]
    ];

    $ch = curl_init("https://api.anti-captcha.com/createTask");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if (!isset($result['taskId']))
        return false;

    $taskId = $result['taskId'];

    // Esperar resoluci├│n (m├íx 60s)
    for ($i = 0; $i < 12; $i++) {
        sleep(5);
        $checkPayload = ["clientKey" => $apiKey, "taskId" => $taskId];

        $ch = curl_init("https://api.anti-captcha.com/getTaskResult");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($checkPayload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        $checkResponse = curl_exec($ch);
        curl_close($ch);

        $checkResult = json_decode($checkResponse, true);

        if ($checkResult['status'] === 'ready') {
            return $checkResult['solution']['gRecaptchaResponse'];
        }
    }
    return false;
}

// 2. Consultar Tigo
function getTigoBalance($value, $type, $recaptchaToken)
{
    // Define URL based on type
    if ($type === 'document') {
        $url = "https://micuenta2-tigo-com-co-prod.tigocloud.net/api/v2.0/convergent/billing/nit/$value/express/balance?_format=json";
        // Payload might differ slightly or documentType might need to be 'nit' or similar? 
        // Based on provided URL, it is NIT based.
        // Usually payload "documentType" reflects this. 
        $docType = "nit";
        $searchType = "nit";
    } else {
        // Default to Line
        $url = "https://micuenta2-tigo-com-co-prod.tigocloud.net/api/v2.0/mobile/billing/subscribers/$value/express/balance?_format=json";
        $docType = "subscribers";
        $searchType = "subscribers";
    }

    $payload = [
        "isCampaign" => false,
        "skipFromCampaign" => false,
        "isAuth" => false,
        "searchType" => $searchType,
        "token" => $recaptchaToken,
        "documentType" => $docType,
        "email" => "$value@mitigoexpress.com", // Dummy email
        "zrcCode" => ""
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "noToken: true",
        "Content-Type: application/json",
        "client-version: 5.20.0",
        "Accept: application/json, text/plain, */*",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
        "Origin: https://mi.tigo.com.co",
        "Referer: https://mi.tigo.com.co/pago-express/facturas?origin=web"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// --- EJECUCI├ôN ---
$token = solveAntiCaptcha($apiKey, $siteKeyTigo, $pageUrlTigo);

if ($token) {
    $data = getTigoBalance($value, $type, $token);

    // Extraer valor formateado si existe
    $formattedValue = null;
    $dueDate = null;

    if (isset($data['data']['mobile']) && !empty($data['data']['mobile'])) {
        $formattedValue = $data['data']['mobile'][0]['dueAmount']['formattedValue'] ?? null;
        $dueDate = $data['data']['mobile'][0]['dueDate']['formattedValue'] ?? null;
    } elseif (isset($data['data']['billingAccounts']) && !empty($data['data']['billingAccounts'])) {
        // Sometimes document search returns billingAccounts
        $formattedValue = $data['data']['billingAccounts'][0]['balance']['formattedValue'] ?? null;
        $dueDate = $data['data']['billingAccounts'][0]['paymentDate']['formattedValue'] ?? null;
    } elseif (isset($data['data']['convergent']) && !empty($data['data']['convergent'])) {
        // Handle convergent billing
        $formattedValue = $data['data']['convergent'][0]['balance']['formattedValue'] ?? null;
        $dueDate = $data['data']['convergent'][0]['paymentDate']['formattedValue'] ?? null;
    }

    if ($formattedValue) {
        echo json_encode([
            "success" => true,
            "balance" => $formattedValue,
            "dueDate" => $dueDate,
            "full_data" => $data
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "No se encontr├│ saldo o error en respuesta Tigo", "debug" => $data]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Error resolviendo Captcha"]);
}
?>
