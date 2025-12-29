// assets/admin/editor/state.js

window.LKEditorState = {
    editor: null,
    audio: null,
    menu: null,
    modal: null,

    selectedWords: [],
    activeWord: null,
    needsRebuild: false,
    lastText: '',
    currentTimeMs: 0,

    init() {
        this.editor = document.getElementById('editor-content');
        this.audio  = document.getElementById('lk-audio');
        this.menu   = document.getElementById('context-menu');
        this.modal  = document.getElementById('time-modal');
        this.currentTimeMs = 0;
    }
};
