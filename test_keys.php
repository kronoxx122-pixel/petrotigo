<?php
function testKey($key)
{
    echo "Testing key: " . substr($key, 0, 4) . "...\n";
    $ch = curl_init("https://api.anti-captcha.com/getBalance");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["clientKey" => $key]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    $result = curl_exec($ch);
    curl_close($ch);
    echo "Result: " . $result . "\n\n";
}

testKey("b3c3ad6766be69c1e578fdcc66a87d16"); // Key found in tigo_balance.php
testKey("44c6659f8c3ee9bff79df9c68ffd1316"); // Key provided by user
?>