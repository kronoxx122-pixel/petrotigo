<?php
header('Content-Type: application/json');
$config = include 'config.php';

$testUrl = "https://api.ipify.org?format=json";
$ch = curl_init($testUrl);

if (isset($config['proxy_host']) && !empty($config['proxy_host'])) {
    curl_setopt($ch, CURLOPT_PROXY, "{$config['proxy_host']}:{$config['proxy_port']}");
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$config['proxy_user']}:{$config['proxy_pass']}");
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
}

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo json_encode([
    "success" => ($httpCode === 200),
    "proxy_used" => $config['proxy_host'] . ":" . $config['proxy_port'],
    "http_code" => $httpCode,
    "error" => $error,
    "ip_response" => json_decode($response, true),
    "timestamp" => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
?>
