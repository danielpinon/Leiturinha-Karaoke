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
    }

};
