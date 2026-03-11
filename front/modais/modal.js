export class Modal {
    constructor() {
        this.overlay = document.getElementById('modalOverlay');
        this.icon = document.getElementById('modalIcon');
        this.title = document.getElementById('modalTitle');
        this.message = document.getElementById('modalMessage');
        this.buttons = document.getElementById('modalButtons');
    }

    show(options) {
        this.icon.textContent = options.icon || '💬';
        this.title.textContent = options.title || 'Mensagem';
        this.message.textContent = options.message || '';
        this.buttons.innerHTML = '';

        // Adicionar botões
        options.buttons.forEach(btn => {
            const button = document.createElement('button');
            button.className = `modal-btn ${btn.class}`;
            button.textContent = btn.text;
            button.onclick = () => {
                this.hide();
                if (btn.callback) btn.callback();
            };
            this.buttons.appendChild(button);
        });

        this.overlay.classList.add('show');

        // Fechar ao clicar fora (apenas se tiver botão de cancelar)
        if (options.buttons.length > 1) {
            this.overlay.onclick = (e) => {
                if (e.target === this.overlay) {
                    this.hide();
                    if (options.onCancel) options.onCancel();
                }
            };
        }
    }

    hide() {
        this.overlay.classList.remove('show');
        this.overlay.onclick = null;
    }
}