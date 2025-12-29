// assets/admin/editor/save.js

window.LKEditorSave = {

    init() {
        document.getElementById('save-editor')
            .addEventListener('click', () => this.save());
    },

    async save() {
        const state = window.LKEditorState;
        const text = window.LKEditorUtils.extractPlainText();

        if (state.needsRebuild) {
            if (!confirm('O texto foi alterado. Reorganizar automaticamente?')) return;

            await fetch(`${LK_EDITOR.rest_url}/transcript/${LK_EDITOR.transcript_id}/rebuild`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': LK_EDITOR.nonce
                },
                body: JSON.stringify({ text })
            });

            location.reload();
            return;
        }

        const words = [...state.editor.querySelectorAll('.word')].map(w => ({
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

        await fetch(`${LK_EDITOR.rest_url}/transcript/${LK_EDITOR.transcript_id}/words`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': LK_EDITOR.nonce
            },
            body: JSON.stringify({ words })
        });

        alert('Salvo com sucesso');
    }

};
