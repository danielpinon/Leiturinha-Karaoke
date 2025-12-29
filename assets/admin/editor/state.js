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

    init(elements) {
        Object.assign(this, elements);
    }
};
