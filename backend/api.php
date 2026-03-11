<?php
/**
 * API - Lógica de Gerenciamento de Arquivos
 * Todos os métodos para manipular arquivos estão aqui
 */

require_once 'config.php';

class FileAPI {
    /**
     * Upload de arquivo(s)
     * @return array Resposta da operação
     */
    public static function upload() {
        $response = ['success' => false, 'message' => ''];
        
        if (!isset($_FILES['files'])) {
            $response['message'] = 'Nenhum arquivo foi enviado';
            return $response;
        }

        $uploadedCount = 0;
        $errors = [];
        
        // Tratamento para múltiplos arquivos
        $files = $_FILES['files'];
        $fileCount = is_array($files['name']) ? count($files['name']) : 1;
        
        for ($i = 0; $i < $fileCount; $i++) {
            // Para múltiplos arquivos
            if (is_array($files['name'])) {
                $fileName = $files['name'][$i];
                $fileTmp = $files['tmp_name'][$i];
                $fileError = $files['error'][$i];
                $fileSize = $files['size'][$i];
            } else {
                // Para um único arquivo
                $fileName = $files['name'];
                $fileTmp = $files['tmp_name'];
                $fileError = $files['error'];
                $fileSize = $files['size'];
            }

            // Validações
            if ($fileError !== UPLOAD_ERR_OK) {
                $errors[] = self::getUploadErrorMessage($fileError, $fileName);
                continue;
            }

            // Validar extensão
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_EXTENSIONS)) {
                $errors[] = "Arquivo '{$fileName}' possui extensão não permitida";
                continue;
            }

            // Validar tamanho
            if ($fileSize > MAX_FILE_SIZE) {
                $errors[] = "Arquivo '{$fileName}' ultrapassa o tamanho máximo de " . self::formatFileSize(MAX_FILE_SIZE);
                continue;
            }

            // Validar se é upload válido
            if (!is_uploaded_file($fileTmp)) {
                $errors[] = "Falha na validação do upload de '{$fileName}'";
                continue;
            }

            // Gerar nome único para o arquivo
            $uniqueName = self::generateUniqueFileName($fileName);
            $destination = UPLOAD_DIR . $uniqueName;

            // Mover arquivo
            if (move_uploaded_file($fileTmp, $destination)) {
                $uploadedCount++;
            } else {
                $errors[] = "Falha ao salvar o arquivo '{$fileName}'";
            }
        }

        if ($uploadedCount > 0) {
            $response['success'] = true;
            $response['message'] = $uploadedCount . ' arquivo(s) enviado(s) com sucesso';
        }
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return $response;
    }

    /**
     * Listar arquivos no diretório de upload
     * @return array Lista de arquivos
     */
    public static function listFiles() {
        $response = ['success' => false, 'files' => []];
        
        if (!is_dir(UPLOAD_DIR)) {
            $response['message'] = 'Diretório de upload não existe';
            return $response;
        }

        $files = [];
        $items = scandir(UPLOAD_DIR);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $filePath = UPLOAD_DIR . $item;
            
            if (is_file($filePath)) {
                $files[] = [
                    'name' => $item,
                    'size' => filesize($filePath),
                    'date' => filemtime($filePath),
                    'type' => self::getMimeType($filePath)
                ];
            }
        }

        // Ordenar por data (mais recentes primeiro)
        usort($files, function($a, $b) {
            return $b['date'] - $a['date'];
        });

        $response['success'] = true;
        $response['files'] = $files;
        return $response;
    }

    /**
     * Download de arquivo
     * @param string $filename Nome do arquivo
     * @return void
     */
    public static function download($filename) {
        // Validar nome do arquivo contra Path Traversal
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            self::sendError('Acesso negado');
            return;
        }

        $filePath = UPLOAD_DIR . basename($filename);

        if (!file_exists($filePath) || !is_file($filePath)) {
            self::sendError('Arquivo não encontrado');
            return;
        }

        // Headers para download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Pragma: public');
        header('Cache-Control: public, must-revalidate');

        // Enviar arquivo
        if (readfile($filePath) === false) {
            self::sendError('Erro ao ler o arquivo');
        }
        exit;
    }

    /**
     * Deletar arquivo
     * @param string $filename Nome do arquivo
     * @return array Resposta da operação
     */
    public static function delete($filename) {
        $response = ['success' => false, 'message' => ''];
        
        // Validar nome do arquivo contra Path Traversal
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            $response['message'] = 'Acesso negado';
            return $response;
        }

        $filePath = UPLOAD_DIR . basename($filename);

        if (!file_exists($filePath) || !is_file($filePath)) {
            $response['message'] = 'Arquivo não encontrado';
            return $response;
        }

        if (unlink($filePath)) {
            $response['success'] = true;
            $response['message'] = 'Arquivo deletado com sucesso';
        } else {
            $response['message'] = 'Falha ao deletar o arquivo';
        }

        return $response;
    }

    /**
     * Gerar nome único para arquivo
     * @param string $filename Nome original
     * @return string Nome único
     */
    private static function generateUniqueFileName($filename) {
        $pathinfo = pathinfo($filename);
        $basename = $pathinfo['filename'];
        $extension = $pathinfo['extension'];
        
        // Remover caracteres especiais
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        
        $uniqueName = $basename . '.' . $extension;
        $counter = 1;
        
        while (file_exists(UPLOAD_DIR . $uniqueName)) {
            $uniqueName = $basename . '_' . $counter . '.' . $extension;
            $counter++;
        }
        
        return $uniqueName;
    }

    /**
     * Formatar tamanho de arquivo
     * @param int $bytes Tamanho em bytes
     * @return string Tamanho formatado
     */
    private static function formatFileSize($bytes) {
        if ($bytes === 0) return '0 Bytes';
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        return round(($bytes / pow($k, $i)) * 100) / 100 . ' ' . $sizes[$i];
    }

    /**
     * Obter mensagem de erro do upload
     * @param int $error Código do erro
     * @param string $filename Nome do arquivo
     * @return string Mensagem de erro
     */
    private static function getUploadErrorMessage($error, $filename = '') {
        $errors = [
            UPLOAD_ERR_OK => 'Nenhum erro',
            UPLOAD_ERR_INI_SIZE => 'Arquivo maior que o permitido (php.ini)',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo maior que o permitido (form)',
            UPLOAD_ERR_PARTIAL => 'Arquivo apenas parcialmente enviado',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado',
            UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever no disco',
            UPLOAD_ERR_EXTENSION => 'Extensão de arquivo não permitida'
        ];
        
        $message = $errors[$error] ?? 'Erro desconhecido';
        return $filename ? "'{$filename}': {$message}" : $message;
    }

    /**
     * Obter MIME type do arquivo (compatível com PHP 8+)
     * @param string $filePath Caminho do arquivo
     * @return string MIME type
     */
    private static function getMimeType($filePath) {
        // Tentar usar finfo se disponível
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            if ($mimeType) {
                return $mimeType;
            }
        }
        
        // Fallback: use extension mapping
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            '7z' => 'application/x-7z-compressed',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'flac' => 'audio/flac',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska'
        ];
        
        return $mimeTypes[$ext] ?? 'application/octet-stream';
    }

    /**
     * Enviar resposta de erro JSON
     * @param string $message Mensagem de erro
     * @return void
     */
    public static function sendError($message) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}
?>
