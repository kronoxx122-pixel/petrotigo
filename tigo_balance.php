<?php

// --- CONFIGURACIÓN ANTI-CAPTCHA ---
$apiKey = "44c6659f8c3ee9bff79df9c68ffd1316";
$siteKeyTigo = "6LcS1L4pAAAAABHgXhZN6do4Ce7-D0jOEmXxg3H6";
$pageUrlTigo = "https://mi.tigo.com.co/pago-express/facturas?origin=web";
$phoneNumber = "3002727129";

function solveAntiCaptcha($apiKey, $siteKey, $pageUrl, $attempt = 1)
{
    if ($attempt > 2) {
        echo "   ❌ Se agotaron los intentos (tarda demasiado).\n";
        return false;
    }

    echo "   -> Creando tarea en Anti-Captcha (Intento $attempt)...\n";

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

    if (!isset($result['taskId'])) {
        echo "   ❌ Error creando tarea: " . json_encode($result) . "\n";
        return false;
    }

    $taskId = $result['taskId'];
    echo "   -> Tarea creada ID: $taskId. Esperando resolución...\n";

    // Esperar resolución con timeout corto
    $startTime = time();

    // Polling rápido cada 2 segundos
    while (true) {
        sleep(2);

        // Si pasan más de 120 segundos, abortar y reintentar
        if (time() - $startTime > 120) {
            echo "   ⚠️ Tarda más de 120s. Abortando este intento y probando uno nuevo...\n";
            return solveAntiCaptcha($apiKey, $siteKey, $pageUrl, $attempt + 1);
        }

        $checkPayload = [
            "clientKey" => $apiKey,
            "taskId" => $taskId
        ];

        $ch = curl_init("https://api.anti-captcha.com/getTaskResult");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($checkPayload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        $checkResponse = curl_exec($ch);
        curl_close($ch);

        $checkResult = json_decode($checkResponse, true);

        if ($checkResult['status'] === 'ready') {
            echo "   ✅ ¡Captcha resuelto! (" . (time() - $startTime) . "s)\n";
            return $checkResult['solution']['gRecaptchaResponse'];
        }

        if ($checkResult['errorId'] > 0) {
            echo "   ❌ Anti-Captcha devolvió error: " . $checkResult['errorDescription'] . "\n";
            return false;
        }

        echo "   ... (procesando) ...\n";
    }
}

function getTigoBalance($phoneNumber, $recaptchaToken)
{
    $url = "https://micuenta2-tigo-com-co-prod.tigocloud.net/api/v2.0/mobile/billing/subscribers/$phoneNumber/express/balance?_format=json";

    $payload = [
        "isCampaign" => false,
        "skipFromCampaign" => false,
        "isAuth" => false,
        "searchType" => "subscribers",
        "token" => $recaptchaToken,
        "documentType" => "subscribers",
        "email" => "$phoneNumber@mitigoexpress.com",
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
    echo "RAW RESPONSE: " . $response . "\n";
    if (curl_errno($ch)) {
        return ['error' => curl_error($ch)];
    }
    curl_close($ch);
    return json_decode($response, true);
}

// --- FLUJO PRINCIPAL ---
echo "\n🔄 Iniciando Anti-Captcha...\n";
$token = solveAntiCaptcha($apiKey, $siteKeyTigo, $pageUrlTigo);

if ($token) {
    echo "\n🎉 ¡Token obtenido! Consultando saldo en Tigo...\n";
    $balanceData = getTigoBalance($phoneNumber, 'line', $token);

    echo "\n---------------------------------------------------\n";
    echo "RESULTADO DE TIGO:\n";
    print_r($balanceData);
    echo "\n---------------------------------------------------\n";
    echo "DEBUG INFO:\n";
    var_dump($balanceData);
    echo "\n---------------------------------------------------\n";
} else {
    echo "\n⚠️ Estamos presentando problemas, vuelve a intentarlo.\n";
}
?>