<?php
/**
 * pre_solve_captcha.php
 * Resuelve 1 captcha con CapMonster y lo guarda en cache compartido.
 */
header('Content-Type: application/json');
set_time_limit(150);

$apiKey = "842d558abb1609e49f1bec6d54106c57"; // CapMonster
$siteKey = "6LcS1L4pAAAAABHgXhZN6do4Ce7-D0jOEmXxg3H6";
$pageUrl = "https://mi.tigo.com.co/pago-express/facturas?origin=web";
$cacheFile = __DIR__ . '/captcha_cache.json';
$lockFile = __DIR__ . '/captcha_cache.lock';

$lock = fopen($lockFile, 'w');
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    fclose($lock);
    echo json_encode(["success" => false, "reason" => "already_solving"]);
    exit;
}

function solveCaptchaToken($apiKey, $siteKey, $pageUrl)
{
    $taskData = json_encode([
        'clientKey' => $apiKey,
        'task' => [
            'type' => 'NoCaptchaTaskProxyless',
            'websiteURL' => $pageUrl,
            'websiteKey' => $siteKey
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
    if (!isset($result['taskId']) || ($result['errorId'] ?? 1) !== 0)
        return false;

    $taskId = $result['taskId'];
    $maxAttempts = 30;
    $attempt = 0;

    while ($attempt < $maxAttempts) {
        sleep(3);

        $ch = curl_init('https://api.capmonster.cloud/getTaskResult');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['clientKey' => $apiKey, 'taskId' => $taskId]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $res = curl_exec($ch);
        curl_close($ch);

        $r = json_decode($res, true);
        if (($r['errorId'] ?? 1) !== 0)
            return false;
        if (($r['status'] ?? '') === 'ready')
            return $r['solution']['gRecaptchaResponse'];
        $attempt++;
    }
    return false;
}

$token = solveCaptchaToken($apiKey, $siteKey, $pageUrl);

if ($token) {
    file_put_contents($cacheFile, json_encode([
        'token' => $token,
        'timestamp' => time()
    ]));
    flock($lock, LOCK_UN);
    fclose($lock);
    echo json_encode(["success" => true]);
}
else {
    flock($lock, LOCK_UN);
    fclose($lock);
    echo json_encode(["success" => false]);
}
?>