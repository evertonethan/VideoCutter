<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Verificando instalação do FFmpeg no Windows...\n\n";

$binPath = __DIR__ . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR;
$ffmpegExe = $binPath . 'ffmpeg.exe';
$ffprobeExe = $binPath . 'ffprobe.exe';

echo "Verificando arquivos:\n";
echo "FFmpeg: " . ($ffmpegExe) . " - " . (file_exists($ffmpegExe) ? "ENCONTRADO" : "NAO ENCONTRADO") . "\n";
echo "FFprobe: " . ($ffprobeExe) . " - " . (file_exists($ffprobeExe) ? "ENCONTRADO" : "NAO ENCONTRADO") . "\n\n";

if (file_exists($ffmpegExe)) {
    echo "Testando FFmpeg:\n";
    system('"' . $ffmpegExe . '" -version');
}

echo "\n\nVerificando diretórios:\n";
$dirs = ['bin', 'uploads', 'outputs', 'logs'];
foreach ($dirs as $dir) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $dir;
    echo "$dir: " . (file_exists($path) ? "EXISTE" : "NAO EXISTE") . "\n";
}

echo "\n\nConfiguração do PHP:\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";