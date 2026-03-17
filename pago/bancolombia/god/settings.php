<?php
// god/settings.php
header('Content-Type: application/json');
require_once __DIR__ . '/auth.php'; // Ensure user is logged in

$configFile = __DIR__ . '/../config/redirect_status.json';

// Helper to read config
function readConfig($file)
{
    if (!file_exists($file))
        return ['enabled' => true];
    $content = file_get_contents($file);
    if (!$content)
        return ['enabled' => true];
    $json = json_decode($content, true);
    if (!is_array($json))
        return ['enabled' => true];
    return $json;
}

// Helper to write config
function writeConfig($file, $data)
{
    // Write safely
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

$action = $_GET['action'] ?? '';

if ($action === 'get_redirect') {
    $config = readConfig($configFile);
    echo json_encode(['status' => 'success', 'enabled' => $config['enabled']]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'set_redirect') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['enabled'])) {
        $config = ['enabled' => (bool) $input['enabled']];
        writeConfig($configFile, $config);
        echo json_encode(['status' => 'success', 'enabled' => $config['enabled']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing enabled param']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
