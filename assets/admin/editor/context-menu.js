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

            // ===== POSICIONAMENTO ANCORADO NA PALAVRA =====
            const rect = word.getBoundingClientRect();

            // Garante que o menu tenha dimensão antes de calcular
            menu.style.display = 'block';
            menu.style.visibility = 'hidden';

            const menuWidth = menu.offsetWidth || 200;
            const menuHeight = menu.offsetHeight || 50;

            let left = rect.left + window.scrollX;
            let top = rect.bottom + window.scrollY + 6;

            // Evita sair da tela pela direita
            if (left + menuWidth > window.innerWidth) {
                left = window.innerWidth - menuWidth - 12;
            }

            // Evita sair da tela por baixo
            if (top + menuHeight > window.scrollY + window.innerHeight) {
                top = rect.top + window.scrollY - menuHeight - 6;
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
