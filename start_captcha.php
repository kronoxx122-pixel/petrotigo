<?php
header('Content-Type: application/json');
$apiKey = "842d558abb1609e49f1bec6d54106c57"; 
$siteKeyTigo = "6Ldat4QsAAAAABNF7g9awFqFmozAQD8GYKOsFYm1";
$pageUrlTigo = "https://mi.tigo.com.co/pago-express/facturas";

$config = include 'config.php';

$taskData = json_encode([
    'clientKey' => $apiKey,
    'task' => [
        'type' => 'RecaptchaV2EnterpriseTask',
        'websiteURL' => $pageUrlTigo,
        'websiteKey' => $siteKeyTigo,
        'pageAction' => 'pago_express',
        'isEnterprise' => true,
        'proxyType' => 'http',
        'proxyAddress' => $config['proxy_host'],
        'proxyPort' => (int)$config['proxy_port'],
        'proxyLogin' => $config['proxy_user'],
        'proxyPassword' => $config['proxy_pass']
    ]
]);

$ch = curl_init('https://api.capmonster.cloud/createTask');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $taskData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
curl_close($ch);

echo $response; // Devolvemos la respuesta de CapMonster directamente (contiene taskId)
?>
