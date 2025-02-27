<?php
echo "=== Verificação de Configurações PHP ===\n\n";

$configs = [
    'upload_max_filesize',
    'post_max_size',
    'memory_limit',
    'max_execution_time',
    'max_input_time',
    'default_socket_timeout'
];

foreach ($configs as $config) {
    echo $config . ": " . ini_get($config) . "\n";
}

echo "\n=== Diretórios e Permissões ===\n";
$dirs = ['uploads', 'logs', 'uploads/chunks'];

foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "Diretório '$dir' criado\n";
    }
    echo "$dir: " . substr(sprintf('%o', fileperms($dir)), -4) . "\n";
}

echo "\n=== INI File ===\n";
echo "Loaded php.ini: " . php_ini_loaded_file() . "\n";
echo "Additional .ini files:\n";
print_r(php_ini_scanned_files());