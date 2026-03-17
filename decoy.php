<?php
http_response_code(503);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Servicio en mantenimiento</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f4f4f4;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #555;
        }
        .container {
            max-width: 480px;
            text-align: center;
            padding: 40px 20px;
        }
        .icon {
            font-size: 64px;
            margin-bottom: 24px;
            opacity: 0.6;
        }
        h1 {
            font-size: 1.6rem;
            color: #333;
            margin-bottom: 12px;
            font-weight: 600;
        }
        p {
            font-size: 1rem;
            line-height: 1.6;
            color: #666;
            margin-bottom: 8px;
        }
        .code {
            display: inline-block;
            margin-top: 20px;
            padding: 6px 16px;
            background: #e0e0e0;
            border-radius: 4px;
            font-size: 0.8rem;
            color: #999;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔧</div>
        <h1>Sitio en mantenimiento</h1>
        <p>Estamos realizando labores de mantenimiento programado.</p>
        <p>El servicio estará disponible nuevamente en breve.</p>
        <span class="code">503 – Service Unavailable</span>
    </div>
</body>
</html>
