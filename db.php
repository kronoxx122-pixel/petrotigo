<?php
$config = include __DIR__ . '/config.php';

$host = $config['db_host'];
$port = $config['db_port'];
$db = $config['db_name'];
$user = $config['db_user'];
$pass = $config['db_pass'];

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
catch (PDOException $e) {
    // In production, log this instead of showing it
    error_log("Connection failed: " . $e->getMessage());
    die("Error connecting to the database.");
}
?>