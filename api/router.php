<?php
// Extraemos la ruta principal
$path = isset($_GET['path']) ? $_GET['path'] : 'index.php';

// MUY IMPORTANTE PARA TELEGRAM WEBHOOKS:
// Vercel a veces pierde los parámetros GET adicionales cuando reescribe la URL hacia /api/router.php
// Aquí recuperamos todos los parámetros originales (id, estado, key, etc) de REQUEST_URI
$originalQuery = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
if ($originalQuery) {
    parse_str($originalQuery, $qParams);
    $_GET = array_merge($_GET, $qParams);
    $_REQUEST = array_merge($_REQUEST, $qParams);
}

// Inicializar el sistema de seguridad solo DESPUÉS de haber restaurado los $_GET params
// porque cloak.php necesita verificar $_GET['key'] para el bypass absoluto
require_once __DIR__ . '/../config/cloak.php';

// Si está vacía o es solo '/', redirigir a index.php
if (empty($path) || $path === '/') {

    $path = 'index.php';
}

// Seguridad básica: prevenir directory traversal
$path = str_replace(['../', '..\\'], '', $path);

// Definir la ruta absoluta al archivo en la carpeta raíz
$baseDir = dirname(__DIR__);
$fileToInclude = $baseDir . '/' . ltrim($path, '/');

// Si la ruta apunta a un directorio (como /pago/), intentar cargar index.php adentro
if (is_dir($fileToInclude)) {
    $fileToInclude = rtrim($fileToInclude, '/') . '/index.php';
}

// Verificar que el archivo existe y es un PHP
if (file_exists($fileToInclude) && is_file($fileToInclude)) {
    $extension = strtolower(pathinfo($fileToInclude, PATHINFO_EXTENSION));

    if ($extension === 'php') {
        // MUY IMPORTANTE: Cambiar el directorio de trabajo al del archivo que vamos a incluir
        // para que todos los require/include ("config.php", etc.) funcionen con rutas relativas
        chdir(dirname($fileToInclude));

        // Incluir y ejecutar el archivo original
        require $fileToInclude;
    }
    else {
        // Si por alguna razón Vercel manda un archivo estático aquí, evitamos mostrar el código
        http_response_code(404);
        echo "404 Not Found (Static files should be handled by Vercel routes)";
    }
}
else {
    // Archivo no encontrado
    http_response_code(404);
    echo "404 Not Found: " . htmlspecialchars($path);
}
