<?php
/**
 * Rotas - Gerenciador de Arquivos
 * Define as rotas e ações disponíveis na API
 */

require_once 'config.php';
require_once 'api.php';

/**
 * Processamento de requisições
 */
function handleRequest() {
    // Obter ação da requisição (GET ou POST)
    $action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
    
    // Validar ação
    if (empty($action)) {
        FileAPI::sendError('Nenhuma ação especificada');
        return;
    }

    // Rotear para a ação apropriada
    switch ($action) {
        case 'upload':
            handleUpload();
            break;
            
        case 'list':
            handleList();
            break;
            
        case 'download':
            handleDownload();
            break;
            
        case 'delete':
            handleDelete();
            break;
            
        default:
            FileAPI::sendError('Ação não reconhecida: ' . htmlspecialchars($action));
            break;
    }
}

/**
 * Ação: Upload
 */
function handleUpload() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        FileAPI::sendError('Método HTTP não permitido para upload');
        return;
    }

    $response = FileAPI::upload();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
}

/**
 * Ação: Listar Arquivos
 */
function handleList() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        FileAPI::sendError('Método HTTP não permitido para listar');
        return;
    }

    $response = FileAPI::listFiles();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
}

/**
 * Ação: Download
 */
function handleDownload() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        FileAPI::sendError('Método HTTP não permitido para download');
        return;
    }

    if (!isset($_GET['file']) || empty($_GET['file'])) {
        FileAPI::sendError('Arquivo não especificado');
        return;
    }

    $filename = $_GET['file'];
    FileAPI::download($filename);
}

/**
 * Ação: Deletar
 */
function handleDelete() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        FileAPI::sendError('Método HTTP não permitido para deletar');
        return;
    }

    if (!isset($_POST['file']) || empty($_POST['file'])) {
        FileAPI::sendError('Arquivo não especificado');
        return;
    }

    $filename = $_POST['file'];
    $response = FileAPI::delete($filename);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
}

/**
 * Executar tratamento de requisição
 */
handleRequest();
?>
