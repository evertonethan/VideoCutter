<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Teste do Ambiente FFmpeg ===\n\n";

$binPath = __DIR__ . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR;
$ffmpegExe = $binPath . 'ffmpeg.exe';
$ffprobeExe = $binPath . 'ffprobe.exe';

echo "Caminhos:\n";
echo "Diretório bin: " . $binPath . "\n";
echo "FFmpeg: " . $ffmpegExe . "\n";
echo "FFprobe: " . $ffprobeExe . "\n\n";

echo "Verificando arquivos necessários:\n";
echo "FFmpeg existe: " . (file_exists($ffmpegExe) ? "SIM" : "NÃO") . "\n";
echo "FFprobe existe: " . (file_exists($ffprobeExe) ? "SIM" : "NÃO") . "\n\n";

// Verificar DLLs necessárias
$requiredDlls = [
    'avcodec*.dll',
    'avdevice*.dll',
    'avfilter*.dll',
    'avformat*.dll',
    'avutil*.dll',
    'postproc*.dll',
    'swresample*.dll',
    'swscale*.dll'
];

echo "Verificando DLLs:\n";
foreach ($requiredDlls as $dllPattern) {
    $matches = glob($binPath . $dllPattern);
    echo "$dllPattern: " . (!empty($matches) ? "ENCONTRADO" : "NÃO ENCONTRADO") . "\n";
    if (!empty($matches)) {
        foreach ($matches as $dll) {
            echo "  - " . basename($dll) . "\n";
        }
    }
}

echo "\nTestando execução do FFmpeg:\n";
$command = '"' . $ffmpegExe . '" -version';
$output = [];
$returnValue = -1;

echo "Comando: $command\n";
exec($command . " 2>&1", $output, $returnValue);
echo "Código de retorno: $returnValue\n\n";
echo "Saída:\n" . implode("\n", $output) . "\n\n";

echo "Verificando permissões do diretório:\n";
$dirs = ['bin', 'uploads', 'outputs', 'logs'];
foreach ($dirs as $dir) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $dir;
    echo "$dir/: " . (is_writable($path) ? "Gravável" : "Não gravável") . "\n";
}

echo "\nVariáveis de ambiente:\n";
echo "PATH: " . getenv('PATH') . "\n";