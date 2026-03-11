<?php
// Obter a URI da requisição
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Debug
error_log("Requisição: $requestMethod $requestPath");

// Redirecionar para front/index.html na raiz
if ($requestPath === '/' || $requestPath === '') {
    $file = __DIR__ . '/front/index.html';
    if (is_file($file)) {
        header('Content-Type: text/html');
        readfile($file);
        exit;
    }
}

// Rotear requisições para backend/routes.php
if ($requestPath === '/backend/routes.php' || strpos($requestPath, '/backend/routes.php') === 0) {
    $_GET['_route'] = $requestPath;
    require __DIR__ . '/backend/routes.php';
    exit;
}

// Servir arquivos estáticos
$file = __DIR__ . $requestPath;

if (is_file($file)) {
    // Detectar tipo MIME
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $mimeTypes = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf',
        'zip' => 'application/zip',
    ];
    
    $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
    header("Content-Type: $mimeType");
    readfile($file);
    exit;
}

// Se chegou aqui, arquivo não encontrado
http_response_code(404);
echo "404 Not Found: $requestPath";
?>

