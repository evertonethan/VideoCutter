<?php
session_write_close();
set_time_limit(0);
ini_set('memory_limit', '10240M');
ignore_user_abort(true);

require __DIR__ . '/vendor/autoload.php';

use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Format\Video\X264;

// Verificar se é uma requisição POST (upload) ou GET (streaming de eventos)
$isEventStream = $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'process';

if ($isEventStream) {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('Access-Control-Allow-Origin: *');
    header('X-Accel-Buffering: no');
} else {
    header('Content-Type: application/json');
}

function send_message($status, $progress = 0, $step = '', $message = '', $outputFile = '')
{
    $data = [
        'status' => $status,
        'progress' => $progress,
        'step' => $step,
        'message' => $message
    ];

    if ($status === 'complete') {
        $data['outputFile'] = $outputFile;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'process') {
        echo "data: " . json_encode($data) . "\n\n";
        ob_flush();
        flush();
    } else {
        echo json_encode($data);
        exit;
    }
}

try {
    $ffmpeg = FFMpeg::create([
        'ffmpeg.binaries'  => __DIR__ . '/bin/ffmpeg.exe',
        'ffprobe.binaries' => __DIR__ . '/bin/ffprobe.exe',
        'timeout'          => 3600,
        'ffmpeg.threads'   => 12,
    ]);

    $ffprobe = FFProbe::create([
        'ffmpeg.binaries'  => __DIR__ . '/bin/ffmpeg.exe',
        'ffprobe.binaries' => __DIR__ . '/bin/ffprobe.exe',
        'timeout'          => 3600,
    ]);

    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    $outputDir = __DIR__ . DIRECTORY_SEPARATOR . 'outputs' . DIRECTORY_SEPARATOR;
    $maxFileSize = 10 * 1024 * 1024 * 1024; // 10GB

    // Criar diretórios se não existirem
    foreach ([$uploadDir, $outputDir] as $dir) {
        if (!file_exists($dir) && !@mkdir($dir, 0755, true)) {
            throw new Exception("Não foi possível criar o diretório: $dir");
        }
    }

    // Se for uma requisição POST, processar o upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload do arquivo: ' .
                (isset($_FILES['video']) ? $_FILES['video']['error'] : 'Arquivo não enviado'));
        }

        if ($_FILES['video']['size'] > $maxFileSize) {
            throw new Exception('Arquivo muito grande. Limite: 10GB');
        }

        $allowedTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['video']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Tipo de arquivo não suportado');
        }

        $tempPath = $_FILES['video']['tmp_name'];
        $newVideoName = uniqid('video_', true) . '.mp4';
        $uploadPath = $uploadDir . $newVideoName;

        if (!move_uploaded_file($tempPath, $uploadPath)) {
            throw new Exception('Falha ao mover o arquivo uploadado');
        }

        echo json_encode(['status' => 'success', 'filename' => $newVideoName]);
        exit;
    }

    // Se for uma requisição GET, processar o vídeo
    if ($isEventStream) {
        $videoName = $_GET['video'] ?? null;
        $inicio = $_GET['inicio'] ?? null;
        $fim = $_GET['fim'] ?? null;

        if (!$videoName || !$inicio || !$fim) {
            throw new Exception('Parâmetros ausentes');
        }

        $uploadPath = $uploadDir . $videoName;
        if (!file_exists($uploadPath)) {
            throw new Exception('Arquivo não encontrado');
        }

        send_message('progress', 30, 'processing');

        // Resto do código de processamento...
        $duration = $ffprobe->format($uploadPath)->get('duration');

        if (
            !preg_match('/^([0-1]?\d|2[0-3]):[0-5]\d:[0-5]\d$/', $inicio) ||
            !preg_match('/^([0-1]?\d|2[0-3]):[0-5]\d:[0-5]\d$/', $fim)
        ) {
            throw new Exception('Formato de tempo inválido');
        }

        function timeToSeconds($time)
        {
            list($h, $m, $s) = explode(':', $time);
            return $h * 3600 + $m * 60 + $s;
        }

        $startSeconds = timeToSeconds($inicio);
        $endSeconds = timeToSeconds($fim);
        $clipDuration = $endSeconds - $startSeconds;

        if ($clipDuration <= 0) {
            throw new Exception('O tempo final deve ser maior que o inicial');
        }

        if ($endSeconds > floatval($duration)) {
            throw new Exception('O tempo de corte excede a duração do vídeo');
        }

        send_message('progress', 50, 'processing');

        $outputPath = $outputDir . 'cut_' . $videoName;
        $video = $ffmpeg->open($uploadPath);

        $video->filters()->clip(
            TimeCode::fromSeconds($startSeconds),
            TimeCode::fromSeconds($clipDuration)
        );

        $format = new X264();
        $format->setAudioCodec('aac');
        $format->setVideoCodec('libx264');
        $format->setKiloBitrate(2000);
        $format->setAudioChannels(2);
        $format->setAudioKiloBitrate(160);

        $video->save($format, $outputPath);

        send_message('progress', 95, 'finalizing');

        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new Exception('Erro ao gerar o vídeo cortado');
        }

        @unlink($uploadPath);

        $relativePath = 'outputs/' . basename($outputPath);
        send_message('complete', 100, 'done', 'Vídeo processado com sucesso', $relativePath);
    }
} catch (Exception $e) {
    send_message('error', 0, 'error', $e->getMessage());

    if (isset($uploadPath) && file_exists($uploadPath)) {
        @unlink($uploadPath);
    }
    if (isset($outputPath) && file_exists($outputPath)) {
        @unlink($outputPath);
    }
}
