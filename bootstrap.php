<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;

// Configurar logger
$logger = new Logger('videocutter');
$logger->pushHandler(new StreamHandler(__DIR__ . '/logs/app.log', Logger::DEBUG));

// Carregar variáveis de ambiente
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configurar caminhos
$binPath = __DIR__ . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR;
$ffmpegPath = $binPath . 'ffmpeg.exe';
$ffprobePath = $binPath . 'ffprobe.exe';

// Verificar existência dos executáveis
if (!file_exists($ffmpegPath)) {
    throw new RuntimeException("FFmpeg não encontrado em: $ffmpegPath");
}

if (!file_exists($ffprobePath)) {
    throw new RuntimeException("FFprobe não encontrado em: $ffprobePath");
}

// Verificar DLLs necessárias
$requiredDlls = [
    'avcodec*.dll',
    'avdevice*.dll',
    'avfilter*.dll',
    'avformat*.dll',
    'avutil*.dll',
    'swresample*.dll',
    'swscale*.dll'
];

$missingDlls = [];
foreach ($requiredDlls as $dllPattern) {
    if (empty(glob($binPath . $dllPattern))) {
        $missingDlls[] = $dllPattern;
    }
}

if (!empty($missingDlls)) {
    throw new RuntimeException(
        "DLLs necessárias não encontradas: " . implode(', ', $missingDlls)
    );
}

// Testar execução do FFmpeg
$command = escapeshellcmd($ffmpegPath) . ' -version';
exec($command . ' 2>&1', $output, $returnValue);

if ($returnValue !== 0) {
    $logger->error('Erro ao executar FFmpeg', [
        'command' => $command,
        'output' => $output,
        'return' => $returnValue
    ]);
    throw new RuntimeException('Erro ao executar FFmpeg. Verifique o log para mais detalhes.');
}

try {
    // Configurar FFmpeg
    $config = [
        'ffmpeg.binaries'  => $ffmpegPath,
        'ffprobe.binaries' => $ffprobePath,
        'timeout'          => 3600,
        'ffmpeg.threads'   => 12,
    ];

    // Adicionar o diretório bin ao PATH do sistema
    $path = getenv('PATH');
    if (strpos($path, $binPath) === false) {
        putenv("PATH=$binPath;$path");
    }

    $ffmpeg = FFMpeg::create($config);
    $ffprobe = FFProbe::create($config);

    // Testar a inicialização
    $version = $ffmpeg->getFFMpegDriver()->getVersion();
    $logger->info('FFmpeg inicializado com sucesso', ['version' => $version]);

    return [
        'logger' => $logger,
        'ffmpeg' => $ffmpeg,
        'ffprobe' => $ffprobe
    ];

} catch (Exception $e) {
    $logger->error('Erro ao inicializar FFmpeg', ['error' => $e->getMessage()]);
    throw $e;
}