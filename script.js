document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const form = document.getElementById('videoForm');
    const videoInput = document.getElementById('video');
    const preview = document.getElementById('preview');
    const previewContainer = document.querySelector('.preview-container');
    const dropZone = document.getElementById('dropZone');
    const fileNameSpan = document.querySelector('.file-name');
    const timelineProgress = document.querySelector('.timeline-progress');
    const timelineSlider = document.querySelector('.timeline-slider');
    const startHandle = document.querySelector('.timeline-handle.start');
    const endHandle = document.querySelector('.timeline-handle.end');
    const timelineSelection = document.querySelector('.timeline-selection');
    const timelineTime = document.querySelector('.timeline-time');
    const progressContainer = document.querySelector('.progress-container');
    const progressFill = document.querySelector('.progress-fill');
    const progressText = document.querySelector('.progress-text');
    const progressStatus = document.querySelector('.progress-status');
    const submitBtn = document.querySelector('.submit-btn');
    const btnSpinner = document.querySelector('.btn-spinner');
    const btnText = document.querySelector('.btn-text');

    // State variables
    let isDragging = false;
    let activeHandle = null;
    let startTime = 0;
    let endTime = 0;
    let uploadedFileName = null;

    // Form Submit Handler
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!videoInput.files.length) {
            alert('Por favor, selecione um vídeo primeiro.');
            return;
        }

        // Preparar UI para processamento
        progressContainer.style.display = 'block';
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnSpinner.style.display = 'inline-block';
        progressStatus.textContent = 'Iniciando upload...';
        progressFill.style.width = '0%';
        progressText.textContent = '0%';

        try {
            // Fase 1: Upload do arquivo
            const formData = new FormData();
            formData.append('video', videoInput.files[0]);
            formData.append('inicio', document.getElementById('inicio').value);
            formData.append('fim', document.getElementById('fim').value);

            const uploadResponse = await fetch('process.php', {
                method: 'POST',
                body: formData
            });

            if (!uploadResponse.ok) {
                throw new Error('Falha no upload do arquivo');
            }

            const uploadResult = await uploadResponse.json();
            if (uploadResult.status !== 'success') {
                throw new Error(uploadResult.message || 'Erro no upload do arquivo');
            }

            uploadedFileName = uploadResult.filename;
            progressStatus.textContent = 'Upload concluído. Iniciando processamento...';

            // Fase 2: Processamento do vídeo
            const eventSource = new EventSource('process.php?' + new URLSearchParams({
                action: 'process',
                video: uploadedFileName,
                inicio: document.getElementById('inicio').value,
                fim: document.getElementById('fim').value
            }));

            eventSource.onmessage = function(event) {
                const data = JSON.parse(event.data);
                
                progressFill.style.width = data.progress + '%';
                progressText.textContent = data.progress + '%';
                
                if (data.step) {
                    progressStatus.textContent = data.step;
                }

                if (data.status === 'complete') {
                    eventSource.close();
                    handleProcessingComplete(data.outputFile);
                }
                
                if (data.status === 'error') {
                    eventSource.close();
                    handleProcessingError(data.message);
                }
            };

            eventSource.onerror = function(error) {
                eventSource.close();
                handleProcessingError('Erro na conexão com o servidor');
            };

        } catch (error) {
            handleProcessingError(error.message);
        }
    });

    function handleProcessingComplete(outputFile) {
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        btnSpinner.style.display = 'none';
        progressStatus.textContent = 'Processamento concluído!';
        
        // Iniciar download automático
        const link = document.createElement('a');
        link.href = outputFile;
        link.download = 'video_cortado.mp4';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        setTimeout(() => {
            progressContainer.style.display = 'none';
            progressFill.style.width = '0%';
            progressText.textContent = '0%';
            progressStatus.textContent = '';
        }, 3000);
    }

    function handleProcessingError(message) {
        alert('Erro: ' + message);
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        btnSpinner.style.display = 'none';
        progressContainer.style.display = 'none';
        progressStatus.textContent = '';
    }

    // Drag and Drop Handlers
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(event => {
        dropZone.addEventListener(event, preventDefaults);
    });

    ['dragenter', 'dragover'].forEach(event => {
        dropZone.addEventListener(event, highlight);
    });

    ['dragleave', 'drop'].forEach(event => {
        dropZone.addEventListener(event, unhighlight);
    });

    dropZone.addEventListener('drop', handleDrop);

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight() {
        dropZone.classList.add('highlight');
    }

    function unhighlight() {
        dropZone.classList.remove('highlight');
    }

    function handleDrop(e) {
        const dt = e.dataTransfer;
        videoInput.files = dt.files;
        handleFileSelect();
    }

    // File Input Handler
    videoInput.addEventListener('change', handleFileSelect);

    function handleFileSelect() {
        const file = videoInput.files[0];
        if (!file) return;

        if (!file.type.startsWith('video/')) {
            alert('Por favor, selecione um arquivo de vídeo válido.');
            videoInput.value = '';
            return;
        }

        // Verificar tamanho máximo (10GB)
        const maxSize = 10 * 1024 * 1024 * 1024; // 10GB em bytes
        if (file.size > maxSize) {
            alert('O arquivo é muito grande. O tamanho máximo permitido é 10GB.');
            videoInput.value = '';
            return;
        }

        fileNameSpan.textContent = file.name;
        const url = URL.createObjectURL(file);
        preview.src = url;
        previewContainer.style.display = 'block';

        preview.onloadedmetadata = () => {
            initializeTimeline();
            updateTimeInputs(0, preview.duration);
        };
    }

    // Timeline Control
    function initializeTimeline() {
        startTime = 0;
        endTime = preview.duration;
        updateTimelineUI();
    }

    [startHandle, endHandle].forEach(handle => {
        handle.addEventListener('mousedown', e => {
            isDragging = true;
            activeHandle = handle;
            document.addEventListener('mousemove', handleDrag);
            document.addEventListener('mouseup', stopDragging);
        });

        // Touch support
        handle.addEventListener('touchstart', e => {
            isDragging = true;
            activeHandle = handle;
            document.addEventListener('touchmove', handleDragTouch);
            document.addEventListener('touchend', stopDragging);
        });
    });

    function handleDrag(e) {
        if (!isDragging) return;
        updateHandlePosition(e.clientX);
    }

    function handleDragTouch(e) {
        if (!isDragging) return;
        updateHandlePosition(e.touches[0].clientX);
    }

    function updateHandlePosition(clientX) {
        const timeline = timelineSlider.getBoundingClientRect();
        let position = (clientX - timeline.left) / timeline.width;
        position = Math.max(0, Math.min(position, 1));

        if (activeHandle === startHandle) {
            startTime = position * preview.duration;
            if (startTime >= endTime) startTime = endTime - 1;
        } else {
            endTime = position * preview.duration;
            if (endTime <= startTime) endTime = startTime + 1;
        }

        updateTimelineUI();
        updateTimeInputs(startTime, endTime);
    }

    function stopDragging() {
        isDragging = false;
        document.removeEventListener('mousemove', handleDrag);
        document.removeEventListener('touchmove', handleDragTouch);
        document.removeEventListener('mouseup', stopDragging);
        document.removeEventListener('touchend', stopDragging);
    }

    function updateTimelineUI() {
        const duration = preview.duration;
        const startPercent = (startTime / duration) * 100;
        const endPercent = (endTime / duration) * 100;

        timelineSelection.style.left = `${startPercent}%`;
        timelineSelection.style.width = `${endPercent - startPercent}%`;
        startHandle.style.left = `${startPercent}%`;
        endHandle.style.left = `${endPercent}%`;
    }

    // Video Preview Controls
    preview.addEventListener('timeupdate', () => {
        const progress = (preview.currentTime / preview.duration) * 100;
        timelineProgress.style.width = `${progress}%`;
        timelineTime.textContent = formatTime(preview.currentTime);
    });

    timelineSlider.addEventListener('click', function(e) {
        if (isDragging) return;
        const timeline = timelineSlider.getBoundingClientRect();
        const clickPosition = (e.clientX - timeline.left) / timeline.width;
        preview.currentTime = clickPosition * preview.duration;
    });

    // Time Input Controls
    const inicioInput = document.getElementById('inicio');
    const fimInput = document.getElementById('fim');

    [inicioInput, fimInput].forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9:]/g, '');
        });

        input.addEventListener('change', function() {
            if (!this.value.match(/^([0-1]?\d|2[0-3]):[0-5]\d:[0-5]\d$/)) {
                alert('Formato de tempo inválido. Use o formato HH:MM:SS');
                return;
            }

            const time = parseTime(this.value);
            if (this === inicioInput) {
                startTime = time;
                if (startTime >= endTime) {
                    startTime = endTime - 1;
                    this.value = formatTime(startTime);
                }
            } else {
                endTime = time;
                if (endTime <= startTime) {
                    endTime = startTime + 1;
                    this.value = formatTime(endTime);
                }
            }
            updateTimelineUI();
        });
    });

    function updateTimeInputs(start, end) {
        inicioInput.value = formatTime(start);
        fimInput.value = formatTime(end);
    }

    // Time Formatting Utilities
    function formatTime(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = Math.floor(seconds % 60);
        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }

    function parseTime(timeStr) {
        const [h, m, s] = timeStr.split(':').map(Number);
        return h * 3600 + m * 60 + s;
    }

    // Prevent accidental page close during processing
    window.addEventListener('beforeunload', function(e) {
        if (progressContainer.style.display === 'block' && 
            progressText.textContent !== '100%') {
            e.preventDefault();
            e.returnValue = 'O vídeo ainda está sendo processado. Deseja sair?';
            return e.returnValue;
        }
    });

    // Cleanup on page unload
    window.addEventListener('unload', function() {
        if (preview.src) {
            URL.revokeObjectURL(preview.src);
        }
    });
});