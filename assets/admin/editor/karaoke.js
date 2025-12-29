// assets/admin/editor/karaoke.js

window.LKEditorKaraoke = {

    init() {
        const state = window.LKEditorState;

        if (!state.editor) return;

        this.buildTimeline();
        this.bind();
        this.loop(); // 🔥 sempre ativo
    },

    timeline: [],
    rafId: null,
    activeIndex: -1,

    buildTimeline() {
        const editor = window.LKEditorState.editor;

        this.timeline = [...editor.querySelectorAll('.word')].map(w => {
            let start = parseInt(w.dataset.start, 10) || 0;
            let end   = parseInt(w.dataset.end, 10) || start;

            if (end - start < 80) {
                end = start + 80;
            }

            return { el: w, start, end };
        });
    },

    bind() {
        const state = window.LKEditorState;

        // 🔊 áudio atualiza tempo virtual
        if (state.audio) {
            state.audio.addEventListener('timeupdate', () => {
                state.currentTimeMs = state.audio.currentTime * 1000;
            });
        }

        // ✍️ edição manual (qualquer mudança de tempo)
        document.addEventListener('lk:timechange', e => {
            state.currentTimeMs = e.detail.timeMs;
        });
    },

    loop() {
        const time = window.LKEditorState.currentTimeMs || 0;

        let i = this.activeIndex;

        while (i + 1 < this.timeline.length && time >= this.timeline[i + 1].start) {
            i++;
        }

        while (i > 0 && time < this.timeline[i].start) {
            i--;
        }

        if (i !== this.activeIndex) {
            this.setActive(i);
        }

        this.rafId = requestAnimationFrame(this.loop.bind(this));
    },

    setActive(index) {
        if (this.activeIndex >= 0) {
            this.timeline[this.activeIndex].el.classList.remove('active');
        }

        if (this.timeline[index]) {
            this.timeline[index].el.classList.add('active');
        }

        this.activeIndex = index;
    }
};
