# VideoCutter

Um aplicativo web para cortar vídeos de forma simples e intuitiva, com interface gráfica e preview em tempo real.

## 📝 Índice

- [Sobre](#sobre)
- [Tecnologias](#tecnologias)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Como Usar](#como-usar)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [Resolução de Problemas](#resolução-de-problemas)

## 📖 Sobre

O VideoCutter é uma aplicação web desenvolvida para facilitar o processo de corte de vídeos. Com uma interface intuitiva, permite aos usuários:
- Upload de vídeos
- Preview em tempo real
- Seleção precisa dos pontos de corte
- Download do vídeo cortado

## 🚀 Tecnologias

### Backend
- PHP 7.4+
- FFmpeg/FFprobe
- Composer

### Frontend
- HTML5
- CSS3 (Custom properties, Flexbox)
- JavaScript (ES6+)
- Server-Sent Events (SSE) para progresso em tempo real

### Bibliotecas PHP
- php-ffmpeg/php-ffmpeg: ^1.1
- monolog/monolog: ^2.9
- vlucas/phpdotenv: ^5.5
- symfony/process: ^5.4

## ⚡ Requisitos

### Sistema
- PHP 7.4 ou superior
- FFmpeg e FFprobe instalados
- Composer
- Servidor web (Apache/Nginx)

### Extensões PHP Necessárias
- fileinfo
- mbstring
- xml
- json

### Requisitos de Armazenamento
- Espaço em disco suficiente para processar vídeos
- Permissões de escrita nas pastas uploads, outputs e logs

## 🔧 Instalação

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/videocutter.git
cd videocutter
```

2. Instale as dependências via Composer:
```bash
composer install
```

3. Configuração do FFmpeg:

Para Windows:
- Baixe o FFmpeg de https://github.com/BtbN/FFmpeg-Builds/releases
- Baixe o arquivo `ffmpeg-master-latest-win64-gpl-shared.zip`
- Extraia e copie da pasta bin:
  - ffmpeg.exe
  - ffprobe.exe
  - Todas as DLLs (*.dll)
- Cole na pasta `bin` do projeto

Para Linux/Mac:
```bash
# Instalar FFmpeg
sudo apt update && sudo apt install ffmpeg # Ubuntu/Debian
brew install ffmpeg # macOS

# Criar links simbólicos
ln -s $(which ffmpeg) bin/ffmpeg
ln -s $(which ffprobe) bin/ffprobe
```

4. Crie e configure o arquivo .env:
```ini
FFMPEG_PATH=bin/ffmpeg
FFPROBE_PATH=bin/ffprobe
APP_DEBUG=true
```

5. Configure as permissões:
```bash
mkdir -p uploads outputs logs
chmod 755 uploads outputs logs bin
chmod 644 .env
```

## ⚙️ Configuração

### Configuração do PHP (php.ini)
```ini
upload_max_filesize = 1G
post_max_size = 1G
max_execution_time = 3600
memory_limit = 1024M
```

### Configuração do Apache (.htaccess)
```apache
<IfModule mod_headers.c>
    Header set Cache-Control "no-cache, no-store, must-revalidate"
    Header set Pragma "no-cache"
    Header set Expires 0
</IfModule>
```

## 📦 Estrutura do Projeto
```
videocutter/
├── bin/           # Binários FFmpeg
├── uploads/       # Uploads temporários
├── outputs/       # Vídeos processados
├── logs/          # Arquivos de log
├── src/           # Código fonte PHP
├── vendor/        # Dependências
├── .env           # Configurações
├── bootstrap.php  # Inicialização
├── composer.json  # Dependências
├── index.html     # Interface principal
├── process.php    # Processamento
├── script.js      # JavaScript frontend
└── styles.css     # Estilos CSS
```

## 🎯 Como Usar

1. Acesse a interface web
2. Arraste um vídeo ou clique para selecionar
3. Use o timeline para selecionar os pontos de corte
4. Clique em "Cortar Vídeo"
5. Aguarde o processamento
6. Faça o download do vídeo cortado

## 🔍 Resolução de Problemas

### FFmpeg não encontrado
Verifique se os arquivos estão na pasta bin:
```
bin/
├── ffmpeg.exe
├── ffprobe.exe
└── *.dll (todas as DLLs necessárias)
```

### Erro de permissão
```bash
# Linux/Mac
chmod 755 bin/ffmpeg bin/ffprobe
chmod -R 755 uploads outputs logs
```

### Erro de DLL no Windows
Certifique-se de que todas as DLLs necessárias estão presentes:
- avcodec-*.dll
- avdevice-*.dll
- avfilter-*.dll
- avformat-*.dll
- avutil-*.dll
- swresample-*.dll
- swscale-*.dll

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 👥 Contribuição

Contribuições são bem-vindas! Por favor, leia as [diretrizes de contribuição](CONTRIBUTING.md) antes de enviar um Pull Request.