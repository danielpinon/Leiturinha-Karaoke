// assets/admin/editor/save.js

window.LKEditorSave = {

    init() {
        const btn = document.getElementById('save-editor');
        if (!btn) return;

        btn.addEventListener('click', () => this.save());
    },

    async save() {
        const state = window.LKEditorState;
        const editor = state.editor;

        const text = window.LKEditorUtils.extractPlainText();
        const hasStructure = window.LKEditorUtils.hasStructuredWords();
        const hasBr = window.LKEditorUtils.hasLineBreaks();

        /* =========================================================
           CASO 1 — TEXTO NÃO ESTRUTURADO (colado externo)
           → rebuild total (recalcula tempos)
        ========================================================= */
        if (state.needsRebuild && !hasStructure) {
            const ok = confirm(
                'O texto foi alterado e não possui tempos. Reorganizar automaticamente?'
            );
            if (!ok) return;

            await this.rebuild({
                text,
                preserve_times: false
            });

            location.reload();
            return;
        }

        /* =========================================================
           CASO 2 — TEXTO ESTRUTURADO, MAS COM <br>
           → rebuild estrutural (mantém tempos)
        ========================================================= */
        if (hasBr) {
            const ok = confirm(
                'A estrutura do texto mudou (quebra de linha). Reorganizar a transcrição mantendo os tempos?'
            );
            if (!ok) return;

            const wordsPayload = [...editor.querySelectorAll('.word, br')].map(node => {

                // LINE BREAK
                if (node.tagName === 'BR') {
                    return {
                        type: 'linebreak'
                    };
                }

                // WORD
                return {
                    id: node.dataset.id,
                    word: node.innerText.trim(),
                    start_ms: node.dataset.start,
                    end_ms: node.dataset.end,

                    font_family: node.dataset.fontFamily || null,
                    font_size: node.dataset.fontSize || null,
                    font_weight: node.dataset.fontWeight || null,
                    font_style: node.dataset.fontStyle || null,
                    underline: node.dataset.underline == 1 ? 1 : 0,
                    color: node.dataset.color || null,
                    background: node.dataset.background || null,
                    letter_spacing: node.dataset.letterSpacing || null,
                    line_height: node.dataset.lineHeight || null,

                    type: 'word'
                };
            });

            await this.rebuild({
                preserve_times: true,
                words: wordsPayload
            });

            location.reload();
            return;
        }

        /* =========================================================
           CASO 3 — TEXTO ESTRUTURADO SEM MUDANÇA ESTRUTURAL
           → salvar palavras normalmente
        ========================================================= */
        const words = [...editor.querySelectorAll('.word')].map(w => ({
            id: w.dataset.id,
            word: w.innerText.trim(),

            start_ms: w.dataset.start,
            end_ms: w.dataset.end,

            font_family: w.dataset.fontFamily || null,
            font_size: w.dataset.fontSize || null,
            font_weight: w.dataset.fontWeight || null,
            font_style: w.dataset.fontStyle || null,
            underline: w.dataset.underline == 1 ? 1 : 0,
            color: w.dataset.color || null,
            background: w.dataset.background || null,
            letter_spacing: w.dataset.letterSpacing || null,
            line_height: w.dataset.lineHeight || null
        }));

        await fetch(
            `${LK_EDITOR.rest_url}/transcript/${LK_EDITOR.transcript_id}/words`,
            {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': LK_EDITOR.nonce
                },
                body: JSON.stringify({ words })
            }
        );

        alert('Salvo com sucesso');
    },

    /* =========================================================
       REBUILD (centralizado)
    ========================================================= */
    async rebuild(payload) {
        await fetch(
            `${LK_EDITOR.rest_url}/transcript/${LK_EDITOR.transcript_id}/rebuild`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': LK_EDITOR.nonce
                },
                body: JSON.stringify(payload)
            }
        );
    }

};
