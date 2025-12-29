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
    LKEditorKaraoke.buildTimeline(); // ✅ garantia extra
    LKEditorSave.init();
    LKEditorTimeOrganizerUI.init();

    document.getElementById('toggle-case')
        ?.addEventListener('click', () => {
            window.LKEditorUtils.toggleCase();
        });

    document.getElementById('open-time-organizer')
        ?.addEventListener('click', () => {
            LKEditorTimeOrganizerUI.open();
        });

    document.addEventListener('DOMContentLoaded', () => {
        if (window.LKEditorToolbar) {
            LKEditorToolbar.init();
        }
    });

});
