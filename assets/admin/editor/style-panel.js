// assets/admin/editor/style-panel.js

window.LKEditorStylePanel = {

    init() {
        this.bindInputs();
        this.bindStyleButtons();
        this.bindToggleCase();
    },

    /* =====================================================
     * SINCRONIZA PAINEL COM PALAVRA ATIVA
     * ===================================================== */
    sync(word) {
        if (!word) return;

        document.getElementById('font-family').value = word.dataset.fontFamily || '';
        document.getElementById('font-size').value = word.dataset.fontSize || '';
        document.getElementById('letter-spacing').value = word.dataset.letterSpacing || '';
        document.getElementById('line-height').value = word.dataset.lineHeight || '';
        document.getElementById('style-color').value = word.dataset.color || '#000000';
        document.getElementById('style-background').value = word.dataset.background || '#ffffff';
    },

    /* =====================================================
     * INPUTS (SELECT / COLOR / NUMBER)
     * ===================================================== */
    bindInputs() {
        const map = {
            'font-family': (w, v) => {
                w.style.fontFamily = v;
                w.dataset.fontFamily = v;
            },
            'font-size': (w, v) => {
                w.style.fontSize = `${v}px`;
                w.dataset.fontSize = v;
            },
            'letter-spacing': (w, v) => {
                w.style.letterSpacing = `${v}em`;
                w.dataset.letterSpacing = `${v}em`;
            },
            'line-height': (w, v) => {
                w.style.lineHeight = v;
                w.dataset.lineHeight = v;
            },
            'style-color': (w, v) => {
                w.style.color = v;
                w.dataset.color = v;
            },
            'style-background': (w, v) => {
                w.style.backgroundColor = v;
                w.dataset.background = v;
            }
        };

        Object.keys(map).forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;

            el.addEventListener('input', e => {
                const w = window.LKEditorState.activeWord;
                if (!w) return;
                map[id](w, e.target.value);
            });
        });
    },

    /* =====================================================
     * BOTÕES B / I / U (POR PALAVRA)
     * ===================================================== */
    bindStyleButtons() {
        document.querySelectorAll('.style-buttons button[data-style]')
            .forEach(btn => {

                btn.addEventListener('click', () => {
                    const w = window.LKEditorState.activeWord;
                    if (!w) return;

                    const style = btn.dataset.style;

                    if (style === 'bold') {
                        const v = w.style.fontWeight === 'bold' ? 'normal' : 'bold';
                        w.style.fontWeight = v;
                        w.dataset.fontWeight = v;
                    }

                    if (style === 'italic') {
                        const v = w.style.fontStyle === 'italic' ? 'normal' : 'italic';
                        w.style.fontStyle = v;
                        w.dataset.fontStyle = v;
                    }

                    if (style === 'underline') {
                        const isOn = w.style.textDecoration === 'underline';
                        w.style.textDecoration = isOn ? 'none' : 'underline';
                        w.dataset.underline = isOn ? 0 : 1;
                    }
                });

            });
    },

    /* =====================================================
     * BOTÃO Aa (CAIXA ALTA / BAIXA)
     * ===================================================== */
    bindToggleCase() {
        const btn = document.getElementById('toggle-case');
        if (!btn) return;

        btn.addEventListener('click', () => {
            const state = window.LKEditorState;
            const w = state.activeWord;
            if (!w) return;

            const text = w.innerText;

            const isUpper =
                text === text.toLocaleUpperCase('pt-BR');

            const newText = isUpper
                ? text.toLocaleLowerCase('pt-BR')
                : text.toLocaleUpperCase('pt-BR');

            // Atualiza DOM
            w.innerText = newText;

            // Atualiza dataset (persistência)
            w.dataset.word = newText;

            // NÃO é alteração estrutural
            state.needsRebuild = false;
        });
    }

};
