<?php

// --- MODO MANUAL ---
// Ejecuta este script desde la terminal así:
// php tigo_balance_manual.php "PEGAR_AQUI_EL_TOKEN_LARGO"

if ($argc < 2) {
    die("\n❌ Error: Debes pasar el token como argumento.\nUso: php tigo_balance_manual.php \"TOKEN...\"\n");
}

$manualToken = $argv[1];
$phoneNumber = "3002727129";

echo "\n🔍 Consultando saldo con el token proporcionado...\n";

$url = "https://micuenta2-tigo-com-co-prod.tigocloud.net/api/v2.0/mobile/billing/subscribers/$phoneNumber/express/balance?_format=json";

$payload = [
    "isCampaign" => false,
    "skipFromCampaign" => false,
    "isAuth" => false,
    "searchType" => "subscribers",
    "token" => $manualToken,
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
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    die("❌ Error de conexión: " . curl_error($ch));
}

curl_close($ch);

$json = json_decode($response, true);

echo "\n--- RESPUESTA DEL SERVIDOR ---\n\n";
if (isset($json['data']['title']['formattedValue'])) {
    echo "✅ ÉXITO! Título: " . $json['data']['title']['formattedValue'] . "\n";
}
print_r($json);
echo "\n------------------------------\n";
?>