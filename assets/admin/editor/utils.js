// assets/admin/editor/utils.js

window.LKEditorUtils = {

    extractPlainText() {
        const { editor } = window.LKEditorState;
        const words = [...editor.querySelectorAll('.word')];

        // TEXTO NÃO ESTRUTURADO
        if (words.length === 0) {
            window.LKEditorState.needsRebuild = true;

            return editor.innerText
                .replace(/[ \t]+\n/g, '\n')
                .replace(/\n{3,}/g, '\n\n')
                .trim();
        }

        let text = '';

        words.forEach((word, index) => {
            const current = word.innerText.trim();
            if (!current) return;

            text += current;

            const next = words[index + 1];
            if (!next) return;

            const blockNow = word.closest('div');
            const blockNext = next.closest('div');

            text += blockNow !== blockNext ? '\n' : ' ';
        });

        return text
            .replace(/[ \t]+\n/g, '\n')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    },

    /**
     * Intercepta ENTER e insere <br />
     */
    handleEnterAsBr(editor) {
        if (!editor) return;

        editor.addEventListener('keydown', e => {
            if (e.key !== 'Enter') return;

            e.preventDefault();

            const selection = window.getSelection();
            if (!selection || selection.rangeCount === 0) return;

            const range = selection.getRangeAt(0);

            const br = document.createElement('br');

            range.deleteContents();
            range.insertNode(br);

            // move o cursor para depois do <br>
            range.setStartAfter(br);
            range.setEndAfter(br);

            selection.removeAllRanges();
            selection.addRange(range);
        });
    },

    hasStructuredWords() {
        const words = document.querySelectorAll('.word');
        if (words.length === 0) return false;

        // Verifica se TODAS têm start e end válidos
        return [...words].every(w =>
            w.dataset.start !== undefined &&
            w.dataset.end !== undefined &&
            w.dataset.start !== '' &&
            w.dataset.end !== ''
        );
    },

    hasLineBreaks() {
        const editor = window.LKEditorState.editor;
        return editor.querySelectorAll('br').length > 0;
    }
};

window.LKEditorUtils.toggleCase = function () {
    const { editor } = window.LKEditorState;
    const words = editor.querySelectorAll('.word');

    if (!words.length) return;

    // Detecta se a maioria está em caixa alta
    let upperCount = 0;

    words.forEach(w => {
        const text = w.innerText.trim();
        if (text && text === text.toUpperCase()) {
            upperCount++;
        }
    });

    const toUpper = upperCount < words.length / 2;

    words.forEach(word => {
        const original = word.innerText;
        const transformed = toUpper
            ? original.toUpperCase()
            : original.toLowerCase();

        word.innerText = transformed;

        // mantém consistência do dataset
        word.dataset.word = transformed;
    });

    // ⚠️ marca como alteração estrutural
    window.LKEditorState.needsRebuild = true;
};

