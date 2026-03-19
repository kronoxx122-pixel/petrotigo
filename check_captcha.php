<?php
header('Content-Type: application/json');
$apiKey = "842d558abb1609e49f1bec6d54106c57"; 
$taskId = $_GET['taskId'] ?? '';

if (empty($taskId)) {
    echo json_encode(["status" => "error", "errorDescription" => "Missing taskId"]);
    exit;
}

$ch = curl_init('https://api.capmonster.cloud/getTaskResult');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['clientKey' => $apiKey, 'taskId' => $taskId]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
curl_close($ch);

echo $response; // Devolvemos la respuesta de CapMonster directamente (status y solution)
?>
