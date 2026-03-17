<?php
header('Content-Type: application/json');

function getTigoCaptcha()
{
    $url = "https://micuenta2-tigo-com-co-prod.tigocloud.net/api/v2.0/convergent/billing/contracts/me/express/captcha?_format=json";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // Headers obligatorios
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Origin: https://mi.tigo.com.co",
        "Referer: https://mi.tigo.com.co/pago-express/facturas?origin=web",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ["success" => false, "error" => "HTTP $httpCode"];
    }

    $tigoData = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ["success" => false, "error" => "Respuesta Tigo no es JSON válido"];
    }

    // Búsqueda Recursiva Agresiva
    $imgVal = null;
    $tokVal = null;

    array_walk_recursive($tigoData, function ($value, $key) use (&$imgVal, &$tokVal) {
        if (is_string($value)) {
            // Si parece base64 imagen
            if (preg_match('/^\/9j\/|iVBORw0KGgo/', $value) && strlen($value) > 1000) {
                $imgVal = $value;
            }
            // Si parece token hexa o alfanumérico largo típico de captcha
            if (($key === 'value' || strpos(strtolower($key), 'token') !== false) && strlen($value) > 40 && !preg_match('/^\/9j\/|iVBORw0KGgo/', $value)) {
                $tokVal = $value;
            }
        }
    });

    if ($imgVal && $tokVal) {
        return [
            "success" => true,
            "image" => $imgVal,
            "captchaToken" => $tokVal
        ];
    }

    return ["success" => false, "error" => "No se encontró el patrón de imagen en el JSON", "raw" => $tigoData];
}

echo json_encode(getTigoCaptcha());
?>
