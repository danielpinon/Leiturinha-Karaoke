// assets/admin/editor/rebuild.js

window.LKEditorRebuild = {

    init() {
        const { editor } = window.LKEditorState;

        // Texto inicial
        window.LKEditorState.lastText =
            window.LKEditorUtils.extractPlainText();

        // Digitação
        editor.addEventListener('input', () => {
            this.check();
        });

        // Colagem
        editor.addEventListener('paste', () => {
            this.invalidate();
        });
    },

    check() {
        const current =
            window.LKEditorUtils.extractPlainText();

        if (current !== window.LKEditorState.lastText) {
            window.LKEditorState.lastText = current;
            this.invalidate();
        }
    },

    invalidate() {
        window.LKEditorState.needsRebuild = true;

        // 🔥 invalida karaoke imediatamente
        if (window.LKEditorKaraoke) {
            window.LKEditorKaraoke.reset?.();
        }
    }
};
