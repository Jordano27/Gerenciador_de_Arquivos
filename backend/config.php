<?php
/**
 * Configurações do Gerenciador de Arquivos
 */

// Define o diretório para armazenar os arquivos
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Define a URL base para acesso aos arquivos
define('UPLOAD_URL', '/uploads/');

// Tamanho máximo de arquivo em bytes (50MB)
define('MAX_FILE_SIZE', 50 * 1024 * 1024);

// Extensões de arquivo permitidas
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'txt', 'mp3', 'mp4', 'avi', 'mkv', 'wav', 'flac', 'js', 'css', 'html', 'php', 'py', 'java', '7z']);

// Cria o diretório de upload se não existir
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0777, true);
}

/**
 * Configurações gerais
 */
ini_set('max_file_uploads', '20');
ini_set('post_max_size', '500M');
ini_set('upload_max_filesize', '50M');

// Configurar header JSON padrão
header('Content-Type: application/json; charset=utf-8');
?>
