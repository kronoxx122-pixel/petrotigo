<?php
// 1. Fix index.php button activation
$f1 = 'index.php';
$c1 = file_get_contents($f1);
$newFunc = '        function checkFormValid() {
            const val = inputField.value.trim();
            const isValidLength = (searchMode === "line" ? val.length >= 10 : val.length >= 5);
            
            if (isValidLength && captchaResuelto) {
                btn.removeAttribute("disabled");
                btn.style.opacity = "1";
                btn.style.cursor = "pointer";
            } else {
                btn.setAttribute("disabled", "true");
                btn.style.opacity = "0.5";
                btn.style.cursor = "not-allowed";
            }
        }';
$c1 = preg_replace('/function checkFormValid\(\) \{.*?\}/s', $newFunc, $c1);
file_put_contents($f1, $c1);

// 2. Simplify get_balance.php - ONLY DB, NO TIGO API
$f2 = 'get_balance.php';
// We'll rewrite it with a clean version that only does DB
$newGetBalance = '<?php
ob_start();
header("Content-Type: application/json; charset=utf-8");
require_once "security.php";
require_once __DIR__ . "/config/cloak.php";
set_time_limit(30);
ini_set("display_errors", 0);

$config = require_once "config.php";
$inputRaw = file_get_contents("php://input");
$input = json_decode($inputRaw, true);
$value = $input["value"] ?? "";

if (empty($value)) {
    ob_clean(); echo json_encode(["success" => false, "message" => "Valor vacío"]);
    exit;
}

try {
    $dbConfig = require "config_db.php";
    $dbUrl = $dbConfig["db_url"];
    $urlParts = parse_url($dbUrl);
    $dbHost = $urlParts["host"];
    $dbPort = $urlParts["port"] ?? 5432;
    $dbName = ltrim($urlParts["path"], "/");
    $dbUser = $urlParts["user"];
    $dbPass = $urlParts["pass"];
    
    $endpointId = explode(".", $dbHost)[0];
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require;options=\'endpoint=$endpointId\'";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Consulta optimizada
    $stmt = $pdo->prepare("SELECT * FROM tigo_balances WHERE number = :val OR document = :val LIMIT 1");
    $stmt->execute([":val" => $value]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $data = json_decode($row["data"], true);
        $data["success"] = true;
        // Marcar como humano
        cloak_set_cookie("is_human", "true");
        ob_clean(); echo json_encode($data);
    } else {
        ob_clean(); echo json_encode(["success" => false, "message" => "Número no encontrado en la base de datos."]);
    }
} catch (Exception $e) {
    ob_clean(); echo json_encode(["success" => false, "message" => "Error de conexión temporal."]);
}
unlink(__FILE__);
';
file_put_contents($f2, $newGetBalance);
echo "Final cleanup done\n";
