// assets/admin/editor.js

document.addEventListener('DOMContentLoaded', () => {

    window.LKEditorState.init({
        editor: document.getElementById('editor-content'),
        audio: document.getElementById('lk-audio'),
        menu: document.getElementById('context-menu'),
        modal: document.getElementById('time-modal')
    });

    LKEditorSelection.init();
    LKEditorStylePanel.init();
    LKEditorContextMenu.init(); // ← NOVO
    LKEditorRebuild.init();     // ← NOVO
    LKEditorKaraoke.init();
    LKEditorSave.init();

});
