<?php
/**
 * Teste de configuração
 */

require_once 'config.php';

echo json_encode([
    'status' => 'ok',
    'upload_dir' => UPLOAD_DIR,
    'upload_dir_exists' => is_dir(UPLOAD_DIR),
    'upload_dir_writable' => is_writable(UPLOAD_DIR),
    'max_file_size' => MAX_FILE_SIZE,
    'allowed_extensions' => ALLOWED_EXTENSIONS,
    'php_version' => phpversion(),
    'post_max_size' => ini_get('post_max_size'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'server_request_method' => $_SERVER['REQUEST_METHOD'],
    'get_params' => $_GET,
    'post_params' => array_keys($_POST)
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
