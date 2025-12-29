// assets/admin/editor/selection.js

window.LKEditorSelection = {

    init() {
        const { editor } = window.LKEditorState;

        editor.addEventListener('click', e => {
            const word = e.target.closest('.word');
            if (!word) return;

            if (e.ctrlKey) {
                word.classList.toggle('selected');
            } else {
                editor.querySelectorAll('.word')
                    .forEach(w => w.classList.remove('selected'));
                word.classList.add('selected');
            }

            window.LKEditorState.selectedWords =
                [...editor.querySelectorAll('.word.selected')];

            window.LKEditorState.activeWord = word;

            if (window.LKEditorStylePanel) {
                window.LKEditorStylePanel.sync(word);
            }
        });
    }

};
