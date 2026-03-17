<?php
if (session_status() === PHP_SESSION_NONE) {
if (session_status() === PHP_SESSION_NONE) { session_start(); }
}
include '../../../db.php';
// $config = include '../../../config.php'; // No es estrictamente necesario si solo consultamos DB

// Verificar si se ha pasado un ID por la URL
if (isset($_GET['id'])) {
    $cliente_id = $_GET['id'];

    // Preparar y ejecutar la consulta para obtener el estado del cliente
    $sql = "SELECT estado FROM nequi WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => $cliente_id]);

    // Verificar si se encontró el cliente
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $estado = $row['estado'];

        // Devolver el estado en formato JSON
        header('Content-Type: application/json');
        echo json_encode(['estado' => $estado]);
    } else {
        // Manejar el caso donde no se encuentra el cliente
        header('Content-Type: application/json');
        echo json_encode(['estado' => null]);
    }
} else {
    // Manejar el caso donde no se pasa un ID
    header('Content-Type: application/json');
    echo json_encode(['estado' => null]);
}

// $conn = null;
?>