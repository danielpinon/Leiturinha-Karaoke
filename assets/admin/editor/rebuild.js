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
            window.LKEditorState.needsRebuild = true;
        });
    },

    check() {
        const current =
            window.LKEditorUtils.extractPlainText();

        if (current !== window.LKEditorState.lastText) {
            window.LKEditorState.needsRebuild = true;
            window.LKEditorState.lastText = current;
        }
    }

};
