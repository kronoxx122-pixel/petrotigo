<?php
// Imprimir la respuesta completa JSON con formato legible

$phoneNumber = "3002727129";
$fakeToken = "03AGdBq27fake_token_for_testing_response";

$url = "https://micuenta2-tigo-com-co-prod.tigocloud.net/api/v2.0/mobile/billing/subscribers/$phoneNumber/express/balance?_format=json";

$payload = [
    "isCampaign" => false,
    "skipFromCampaign" => false,
    "isAuth" => false,
    "searchType" => "subscribers",
    "token" => $fakeToken,
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
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    "Origin: https://mi.tigo.com.co",
    "Referer: https://mi.tigo.com.co/pago-express/facturas?origin=web"
]);

$response = curl_exec($ch);
curl_close($ch);

echo "RESPUESTA JSON FORMATEADA:\n";
echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>