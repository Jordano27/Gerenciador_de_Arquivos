import { Modal } from './modais/modal.js';

// Criar instância global do modal
const modal = new Modal();

// Funções auxiliares para facilitar o uso
function showAlert(message, type = 'info', callback = null) {
    const types = {
        success: { icon: '✅', title: 'Sucesso!' },
        error: { icon: '❌', title: 'Erro' },
        warning: { icon: '⚠️', title: 'Atenção' },
        info: { icon: 'ℹ️', title: 'Informação' }
    };

    const config = types[type] || types.info;

    modal.show({
        icon: config.icon,
        title: config.title,
        message: message,
        buttons: [
            {
                text: 'OK',
                class: 'modal-btn-primary',
                callback: callback
            }
        ]
    });
}

function showConfirm(message, onConfirm, onCancel) {
    modal.show({
        icon: '❓',
        title: 'Confirmação',
        message: message,
        buttons: [
            {
                text: 'Cancelar',
                class: 'modal-btn-secondary',
                callback: onCancel
            },
            {
                text: 'Confirmar',
                class: 'modal-btn-danger',
                callback: onConfirm
            }
        ],
        onCancel: onCancel
    });
}

class FileManager {
    constructor() {
        this.apiUrl = '/backend/routes.php';
        this.dropZone = document.getElementById('dropZone');
        this.fileInput = document.getElementById('fileInput');
        this.uploadBtn = document.getElementById('uploadBtn');
        this.filesGrid = document.getElementById('filesGrid');
        this.selectedFiles = []; // Array para armazenar arquivos selecionados

        this.initEventListeners();
        this.loadFiles();
    }

    initEventListeners() {
        // Upload via clique
        this.dropZone.addEventListener('click', () => this.fileInput.click());
        this.fileInput.addEventListener('change', (e) => this.handleFileSelect(e));

        // Upload via drag and drop
        this.dropZone.addEventListener('dragover', (e) => this.handleDragOver(e));
        this.dropZone.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        this.dropZone.addEventListener('drop', (e) => this.handleDrop(e));

        // Upload button
        this.uploadBtn.addEventListener('click', () => this.uploadFiles());
    }

    handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        this.dropZone.classList.add('drag-over');
    }

    handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        this.dropZone.classList.remove('drag-over');
    }

    handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        this.dropZone.classList.remove('drag-over');

        const files = e.dataTransfer.files;
        this.selectedFiles = Array.from(files);
        this.updateDropZoneText();
    }

    handleFileSelect(e) {
        // Armazenar arquivos selecionados via clique
        this.selectedFiles = Array.from(e.target.files);
        this.updateDropZoneText();
    }

    updateDropZoneText() {
        if (this.selectedFiles.length > 0) {
            const totalSize = this.selectedFiles.reduce((sum, file) => sum + file.size, 0);
            this.dropZone.querySelector('p').textContent =
                `${this.selectedFiles.length} arquivo(s) selecionado(s) - ${this.formatFileSize(totalSize)}`;
        } else {
            this.dropZone.querySelector('p').textContent =
                'Clique para selecionar ou arraste arquivos aqui';
        }
    }

    uploadFiles() {
        const files = this.selectedFiles;

        if (files.length === 0) {
            showAlert('Selecione arquivos para enviar', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'upload');

        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        this.uploadBtn.disabled = true;
        const previousText = this.dropZone.querySelector('p').textContent;
        this.dropZone.querySelector('p').textContent = `📤 Enviando ${files.length} arquivo(s)...`;

        console.log('Enviando para:', this.apiUrl);
        console.log('Arquivos:', files);

        fetch(this.apiUrl, {
            method: 'POST',
            body: formData
        })
            .then(response => {
                console.log('Status da resposta:', response.status);
                console.log('Content-Type:', response.headers.get('content-type'));

                if (!response.ok) {
                    throw new Error(`Erro HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text(); // Primeiro obter como texto
            })
            .then(text => {
                console.log('Resposta bruta:', text);

                // Tentar fazer parse do JSON
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showAlert(`${files.length} arquivo(s) enviado(s) com sucesso!`, 'success', () => {
                            location.reload();
                        });
                    } else {
                        showAlert(`Erro: ${data.message}`, 'error');
                    }
                } catch (e) {
                    console.error('Erro ao fazer parse do JSON:', e);
                    console.error('Resposta recebida:', text);
                    showAlert(`Erro ao processar resposta do servidor: ${text.substring(0, 100)}`, 'error');
                }
            })
            .catch(error => {
                console.error('Erro completo:', error);
                showAlert(`Erro ao enviar arquivos: ${error.message}`, 'error');
            })
            .finally(() => {
                this.uploadBtn.disabled = false;
                this.dropZone.querySelector('p').textContent = previousText;
            });
    }

    loadFiles() {
        console.log('Carregando arquivos de:', `${this.apiUrl}?action=list`);

        fetch(`${this.apiUrl}?action=list`)
            .then(response => {
                console.log('Status loadFiles:', response.status);
                if (!response.ok) {
                    throw new Error(`Erro HTTP ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('Resposta loadFiles:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        this.renderFiles(data.files);
                    } else {
                        console.error('Erro ao carregar:', data.message);
                        this.filesGrid.innerHTML = '<div class="empty-state"><p>Erro ao carregar arquivos</p></div>';
                    }
                } catch (e) {
                    console.error('Erro ao fazer parse:', e);
                    this.filesGrid.innerHTML = '<div class="empty-state"><p>Erro ao processar resposta</p></div>';
                }
            })
            .catch(error => {
                console.error('Erro ao carregar arquivos:', error);
                this.filesGrid.innerHTML = '<div class="empty-state"><p>Erro ao carregar arquivos: ' + error.message + '</p></div>';
            });
    }

    renderFiles(files) {
        if (files.length === 0) {
            this.filesGrid.innerHTML = '<div class="empty-state"><p>Nenhum arquivo enviado ainda</p></div>';
            return;
        }

        this.filesGrid.innerHTML = '';

        files.forEach(file => {
            const fileCard = document.createElement('div');
            fileCard.className = 'file-card';
            fileCard.innerHTML = `
                <span class="file-icon">${this.getFileIcon(file.name)}</span>
                <div class="file-name">${this.escapeHtml(file.name)}</div>
                <div class="file-size">${this.formatFileSize(file.size)}</div>
                <div class="file-actions">
                    <button class="btn-small btn-download" onclick="fileManager.downloadFile('${this.escapeHtml(file.name)}')">
                        ⬇️ Baixar
                    </button>
                    <button class="btn-small btn-delete" onclick="fileManager.deleteFile('${this.escapeHtml(file.name)}')">
                        🗑️ Deletar
                    </button>
                </div>
            `;
            this.filesGrid.appendChild(fileCard);
        });
    }

    downloadFile(filename) {
        const encodedFilename = encodeURIComponent(filename);
        window.location.href = `${this.apiUrl}?action=download&file=${encodedFilename}`;
    }

    deleteFile(filename) {
        showConfirm(
            `Tem certeza que deseja deletar "${filename}"?`,
            () => {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('file', filename);

                console.log('Deletando arquivo:', filename);

                fetch(this.apiUrl, {
                    method: 'POST',
                    body: formData
                })
                    .then(response => {
                        console.log('Status delete:', response.status);
                        if (!response.ok) {
                            throw new Error(`Erro HTTP ${response.status}`);
                        }
                        return response.text();
                    })
                    .then(text => {
                        console.log('Resposta delete:', text);
                        try {
                            const data = JSON.parse(text);
                            if (data.success) {
                                showAlert('Arquivo deletado com sucesso!', 'success', () => {
                                    location.reload();
                                });
                            } else {
                                showAlert(`Erro: ${data.message}`, 'error');
                            }
                        } catch (e) {
                            console.error('Erro ao fazer parse:', e);
                            showAlert(`Erro ao processar resposta: ${text.substring(0, 100)}`, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Erro completo:', error);
                        showAlert(`Erro ao deletar arquivo: ${error.message}`, 'error');
                    });
            }
        );
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    }

    getFileIcon(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        const icons = {
            'pdf': '📄',
            'doc': '📝', 'docx': '📝',
            'xls': '📊', 'xlsx': '📊',
            'ppt': '📈', 'pptx': '📈',
            'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️',
            'mp4': '🎥', 'avi': '🎥', 'mkv': '🎥',
            'mp3': '🎵', 'wav': '🎵', 'flac': '🎵',
            'zip': '🗜️', 'rar': '🗜️', '7z': '🗜️',
            'txt': '📄',
            'js': '📜', 'css': '📜', 'html': '📜',
            'php': '⚙️', 'py': '🐍', 'java': '☕'
        };
        return icons[ext] || '📁';
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Inicializa o gerenciador quando a página carrega
document.addEventListener('DOMContentLoaded', () => {
    window.fileManager = new FileManager();
});
