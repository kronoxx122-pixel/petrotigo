<?php
$config = include __DIR__ . '/config.php';

$host = $config['db_host'];
$port = $config['db_port'];
$db = $config['db_name'];
$user = $config['db_user'];
$pass = $config['db_pass'];

$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require;options=endpoint=ep-twilight-king-ada5hr56";

try {
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Purge blocked_ips
    $stmt = $conn->query("DELETE FROM blocked_ips");
    echo "Purgadas " . $stmt->rowCount() . " IPs de la lista negra." . PHP_EOL;

    // Optional: add a wildcard or dummy if we need to
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
