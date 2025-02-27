<?php
set_time_limit(0); 
ini_set('memory_limit', '10240M'); 
ini_set('max_execution_time', 0); 
ini_set('max_input_time', 0); 

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding');
header('Content-Type: application/json');

try {
    // Verificar método OPTIONS (CORS preflight)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    // Criar diretórios se não existirem
    $dirs = ['uploads', 'outputs', 'logs'];
    foreach ($dirs as $dir) {
        if (!file_exists($dir) && !@mkdir($dir, 0755, true)) {
            throw new Exception("Não foi possível criar o diretório: $dir");
        }
    }

    // Verificar upload do arquivo
    if (!isset($_FILES['video'])) {
        throw new Exception('Nenhum arquivo enviado');
    }

    if ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'O arquivo excede o limite do php.ini',
            UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o limite do formulário',
            UPLOAD_ERR_PARTIAL => 'O upload foi feito parcialmente',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado',
            UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever o arquivo',
            UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
        ];
        throw new Exception($errors[$_FILES['video']['error']] ?? 'Erro desconhecido');
    }

    // Validar tipo do arquivo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $_FILES['video']['tmp_name']);
    finfo_close($finfo);

    if (!str_starts_with($mimeType, 'video/')) {
        throw new Exception('Tipo de arquivo inválido: ' . $mimeType);
    }

    // Gerar nome único para o arquivo
    $extension = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['mp4', 'avi', 'mov', 'webm'])) {
        throw new Exception('Extensão de arquivo não suportada');
    }

    $filename = uniqid('video_', true) . '.' . $extension;
    $uploadPath = 'uploads/' . $filename;

    // Mover o arquivo
    if (!move_uploaded_file($_FILES['video']['tmp_name'], $uploadPath)) {
        throw new Exception('Falha ao mover o arquivo');
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Arquivo recebido com sucesso',
        'filename' => $filename
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
