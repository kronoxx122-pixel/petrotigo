<?php
/**
 * Proxy para cargar Tigo y permitir iframe
 * Elimina headers anti-iframe e inyecta script para capturar token
 */

$url = "https://mi.tigo.com.co/pago-express/facturas?origin=web";

// Fetch de la página de Tigo
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

// Capturar headers de respuesta
$headers = [];
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headers) {
    $len = strlen($header);
    $header = explode(':', $header, 2);
    if (count($header) < 2)
        return $len;

    $name = strtolower(trim($header[0]));
    // Filtrar headers anti-iframe
    if (!in_array($name, ['x-frame-options', 'content-security-policy', 'x-content-type-options'])) {
        $headers[trim($header[0])] = trim($header[1]);
    }

    return $len;
});

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo "Error cargando Tigo: HTTP $httpCode";
    exit;
}

// Script para detectar y enviar token al parent
$captureScript = <<<'SCRIPT'
<script>
(function() {
    console.log('[Proxy] Script de captura de token iniciado');
    
    // Función para enviar token al parent
    function sendTokenToParent(token) {
        console.log('[Proxy] Enviando token al parent:', token.substring(0, 20) + '...');
        window.parent.postMessage({
            type: 'TIGO_CAPTCHA_TOKEN',
            token: token
        }, '*');
    }
    
    // Método 1: Observar el campo g-recaptcha-response
    const observer = new MutationObserver(() => {
        const tokenField = document.querySelector('[name="g-recaptcha-response"]');
        if (tokenField && tokenField.value) {
            console.log('[Proxy] Token detectado via MutationObserver');
            sendTokenToParent(tokenField.value);
        }
    });
    
    // Observar todo el document por si el campo se crea dinámicamente
    observer.observe(document.documentElement, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['value']
    });
    
    // Método 2: Interceptar grecaptcha.getResponse()
    window.addEventListener('load', () => {
        console.log('[Proxy] Página cargada, intentando interceptar grecaptcha');
        
        const checkGreCaptcha = setInterval(() => {
            if (typeof grecaptcha !== 'undefined' && grecaptcha.getResponse) {
                console.log('[Proxy] grecaptcha encontrado');
                
                // Chequear periódicamente si hay respuesta
                setInterval(() => {
                    const response = grecaptcha.getResponse();
                    if (response) {
                        console.log('[Proxy] Token obtenido via grecaptcha.getResponse()');
                        sendTokenToParent(response);
                    }
                }, 500);
                
                clearInterval(checkGreCaptcha);
            }
        }, 100);
        
        // Timeout después de 10 segundos
        setTimeout(() => clearInterval(checkGreCaptcha), 10000);
    });
    
    console.log('[Proxy] Observadores configurados correctamente');
})();
</script>
SCRIPT;

// Inyectar base tag para que los assets se carguen desde Tigo
$baseTag = '<base href="https://mi.tigo.com.co/">';

// Buscar el <head> e inyectar el base tag
if (preg_match('/<head[^>]*>/i', $html, $matches)) {
    $html = str_replace($matches[0], $matches[0] . $baseTag, $html);
} else {
    // Si no hay <head>, agregarlo al inicio
    $html = $baseTag . $html;
}

// Inyectar script antes de </body>
$html = str_replace('</body>', $captureScript . '</body>', $html);

// Si no hay </body>, inyectar al final
if (strpos($html, '</body>') === false) {
    $html .= $captureScript;
}

// Enviar headers filtrados
foreach ($headers as $name => $value) {
    header("$name: $value");
}

// Headers para permitir iframe
header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: ALLOWALL', true);
header_remove('X-Frame-Options');

echo $html;
?>