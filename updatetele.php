<?php
include 'db.php';
$config = include 'config.php';

$security_key = $config['security_key'];

if (isset($_GET['id'], $_GET['estado'], $_GET['key']) && $_GET['key'] === $security_key) {
    $id = intval($_GET['id']);
    $estado = intval($_GET['estado']);

    // Determinar qué tabla actualizar: pse (default) o nequi
    $tablaPermitida = ['pse', 'nequi'];
    $tabla = isset($_GET['tabla']) && in_array($_GET['tabla'], $tablaPermitida)
        ? $_GET['tabla']
        : 'pse';

    $sql = "UPDATE $tabla SET estado = :estado WHERE id = :id";
    $stmt = $conn->prepare($sql);

    if ($stmt && $stmt->execute(['estado' => $estado, 'id' => $id])) {
        header("Location: close.html");
        exit();
    }
    else {
        echo "Error al actualizar el estado.";
    }
}
else {
    echo "Acceso no autorizado o parámetros inválidos.";
}
?>