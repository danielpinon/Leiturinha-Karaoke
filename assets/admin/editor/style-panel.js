// assets/admin/editor/style-panel.js

window.LKEditorStylePanel = {

    init() {
        this.bindInputs();
    },

    sync(word) {
        if (!word) return;

        document.getElementById('font-family').value = word.dataset.fontFamily || '';
        document.getElementById('font-size').value = word.dataset.fontSize || '';
        document.getElementById('letter-spacing').value = word.dataset.letterSpacing || '';
        document.getElementById('line-height').value = word.dataset.lineHeight || '';
        document.getElementById('style-color').value = word.dataset.color || '#000000';
        document.getElementById('style-background').value = word.dataset.background || '#ffffff';
    },

    bindInputs() {
        const map = {
            'font-family': (w, v) => { w.style.fontFamily = v; w.dataset.fontFamily = v; },
            'font-size': (w, v) => { w.style.fontSize = `${v}px`; w.dataset.fontSize = v; },
            'letter-spacing': (w, v) => { w.style.letterSpacing = `${v}em`; w.dataset.letterSpacing = `${v}em`; },
            'line-height': (w, v) => { w.style.lineHeight = v; w.dataset.lineHeight = v; },
            'style-color': (w, v) => { w.style.color = v; w.dataset.color = v; },
            'style-background': (w, v) => { w.style.backgroundColor = v; w.dataset.background = v; }
        };

        Object.keys(map).forEach(id => {
            document.getElementById(id).addEventListener('input', e => {
                const w = window.LKEditorState.activeWord;
                if (!w) return;
                map[id](w, e.target.value);
            });
        });
    }

};
