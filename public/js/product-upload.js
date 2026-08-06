document.addEventListener('DOMContentLoaded', function () {
    const dropZone = document.getElementById('dropZone');
    const editDropZone = document.getElementById('editDropZone');
    const fileInput = document.getElementById('files');
    const filePreview = document.getElementById('filePreview');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    const acceptedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'video/mp4', 'video/webm', 'video/ogg', 'audio/mpeg', 'audio/wav', 'audio/mp3', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'text/plain'];
    const maxSize = 10 * 1024 * 1024;

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function getFileIcon(file) {
        if (file.type.startsWith('image/')) {
            return `<svg class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`;
        } else if (file.type.startsWith('video/')) {
            return `<svg class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>`;
        } else if (file.type.startsWith('audio/')) {
            return `<svg class="h-8 w-8 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>`;
        } else {
            return `<svg class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`;
        }
    }

    function renderPreview(files) {
        filePreview.innerHTML = '';
        Array.from(files).forEach(file => {
            const div = document.createElement('div');
            div.className = 'relative rounded-lg overflow-hidden bg-gray-100 border border-gray-200 aspect-square';

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover" alt="${file.name}">`;
                };
                reader.readAsDataURL(file);
            } else {
                div.innerHTML = `
                    <div class="w-full h-full flex flex-col items-center justify-center p-2">
                        ${getFileIcon(file)}
                        <p class="mt-2 text-xs text-gray-500 text-center truncate w-full">${file.name}</p>
                        <p class="text-xs text-gray-400">${formatFileSize(file.size)}</p>
                    </div>
                `;
            }
            filePreview.appendChild(div);
        });
        filePreview.classList.remove('hidden');
    }

    function validateFiles(files) {
        const errors = [];
        Array.from(files).forEach(file => {
            if (!acceptedTypes.includes(file.type) && !file.name.match(/\.(jpg|jpeg|png|gif|webp|svg|mp4|webm|ogg|mp3|wav|pdf|doc|docx|zip|txt)$/i)) {
                errors.push(`${file.name}: Unsupported file type`);
            }
            if (file.size > maxSize) {
                errors.push(`${file.name}: Exceeds 10MB limit (${formatFileSize(file.size)})`);
            }
        });
        return errors;
    }

    function setupDropZone(zone, input) {
        if (!zone) return;

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            zone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            zone.addEventListener(eventName, () => zone.classList.add('border-indigo-500', 'bg-indigo-50'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            zone.addEventListener(eventName, () => zone.classList.remove('border-indigo-500', 'bg-indigo-50'), false);
        });

        zone.addEventListener('drop', function (e) {
            const files = e.dataTransfer.files;
            if (input) {
                input.files = files;
            }
            const errors = validateFiles(files);
            if (errors.length > 0) {
                alert(errors.join('\n'));
            }
            renderPreview(files);
        }, false);

        if (input) {
            input.addEventListener('change', function () {
                const errors = validateFiles(this.files);
                if (errors.length > 0) {
                    alert(errors.join('\n'));
                }
                renderPreview(this.files);
            });
        }
    }

    setupDropZone(dropZone, fileInput);
    setupDropZone(editDropZone, null);

    window.showUploadProgress = function (show) {
        if (show) {
            uploadProgress.classList.remove('hidden');
        } else {
            uploadProgress.classList.add('hidden');
        }
    };

    window.updateProgress = function (percent) {
        progressBar.style.width = percent + '%';
        progressText.textContent = Math.round(percent) + '%';
    };
});
