// assets/admin/editor/context-menu.js

window.LKEditorContextMenu = {

    init() {
        const { editor, menu } = window.LKEditorState;

        // Clique direito em palavra
        editor.addEventListener('contextmenu', e => {
            const word = e.target.closest('.word');
            if (!word) return;

            e.preventDefault();

            window.LKEditorState.activeWord = word;

            // ===== POSICIONAMENTO COLADO NA PALAVRA =====
            const rect = word.getBoundingClientRect();

            // força cálculo real do tamanho
            menu.style.display = 'block';
            menu.style.visibility = 'hidden';

            const menuWidth = menu.offsetWidth;
            const menuHeight = menu.offsetHeight;

            // Centraliza horizontalmente na palavra
            let left =
                rect.left +
                window.scrollX +
                rect.width / 2 -
                menuWidth / 2;

            // Prioriza aparecer ACIMA da palavra
            let top =
                rect.top +
                window.scrollY -
                menuHeight -
                4;

            // Se não couber acima, joga para baixo
            if (top < window.scrollY + 8) {
                top =
                    rect.bottom +
                    window.scrollY +
                    4;
            }

            // Evita sair da tela lateralmente
            if (left < 8) left = 8;
            if (left + menuWidth > window.innerWidth - 8) {
                left = window.innerWidth - menuWidth - 8;
            }

            menu.style.left = `${left}px`;
            menu.style.top = `${top}px`;
            menu.style.visibility = 'visible';

            // Mostrar "Agrupar" apenas se múltiplas selecionadas
            const groupBtn = menu.querySelector('[data-action="group"]');
            if (groupBtn) {
                groupBtn.style.display =
                    window.LKEditorState.selectedWords.length > 1
                        ? 'block'
                        : 'none';
            }
        });

        // Fecha menu ao clicar fora
        document.addEventListener('click', e => {
            if (!e.target.closest('#context-menu')) {
                menu.style.display = 'none';
            }
        });

        // Ações do menu
        menu.addEventListener('click', e => {
            const action = e.target.dataset.action;
            if (!action) return;

            if (action === 'edit-time') {
                this.openTimeModal();
            }

            if (action === 'group') {
                this.groupSelectedWords();
            }

            menu.style.display = 'none';
        });

        // Modal salvar tempo
        const saveBtn = document.getElementById('save-time');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveTime());
        }
    },

    openTimeModal() {
        const { activeWord, modal } = window.LKEditorState;
        if (!activeWord || !modal) return;

        document.getElementById('start-time').value =
            activeWord.dataset.start || '';

        document.getElementById('end-time').value =
            activeWord.dataset.end || '';

        modal.style.display = 'block';
    },

    saveTime() {
        const { activeWord, modal } = window.LKEditorState;
        if (!activeWord) return;

        activeWord.dataset.start =
            document.getElementById('start-time').value;

        activeWord.dataset.end =
            document.getElementById('end-time').value;

        modal.style.display = 'none';
    },

    groupSelectedWords() {
        const words = window.LKEditorState.selectedWords;
        if (!words || words.length < 2) return;

        const start = Math.min(...words.map(w => Number(w.dataset.start)));
        const end = Math.max(...words.map(w => Number(w.dataset.end)));

        words.forEach(w => {
            w.dataset.start = start;
            w.dataset.end = end;
            w.classList.add('grouped');
        });
    }

};
