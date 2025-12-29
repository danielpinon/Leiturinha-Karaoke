// assets/admin/editor/karaoke.js

window.LKEditorKaraoke = {

    init() {
        const state = window.LKEditorState;

        if (!state.audio || !state.editor) return;

        this.buildTimeline();
        this.bind();
    },

    timeline: [],
    rafId: null,
    activeIndex: -1,

    buildTimeline() {
        const state = window.LKEditorState;

        this.timeline = [...state.editor.querySelectorAll('.word')]
            .map(w => {
                let start = parseInt(w.dataset.start, 10) || 0;
                let end   = parseInt(w.dataset.end, 10) || start;

                // tempo mínimo no editor
                if (end - start < 80) {
                    end = start + 80;
                }

                return {
                    el: w,
                    start,
                    end
                };
            });
    },

    bind() {
        const audio = window.LKEditorState.audio;

        audio.addEventListener('play', () => {
            cancelAnimationFrame(this.rafId);
            this.rafId = requestAnimationFrame(this.sync.bind(this));
        });

        audio.addEventListener('pause', () => {
            cancelAnimationFrame(this.rafId);
        });

        audio.addEventListener('ended', () => {
            cancelAnimationFrame(this.rafId);
            this.clear();
        });
    },

    sync() {
        const audio = window.LKEditorState.audio;
        const currentMs = audio.currentTime * 1000;

        let i = this.activeIndex;

        while (i + 1 < this.timeline.length && currentMs >= this.timeline[i + 1].start) {
            i++;
        }

        while (i > 0 && currentMs < this.timeline[i].start) {
            i--;
        }

        if (i !== this.activeIndex) {
            this.setActive(i);
        }

        this.rafId = requestAnimationFrame(this.sync.bind(this));
    },

    setActive(index) {
        if (this.activeIndex >= 0) {
            this.timeline[this.activeIndex].el.classList.remove('active');
        }

        if (this.timeline[index]) {
            const el = this.timeline[index].el;
            el.classList.add('active');

            el.scrollIntoView({
                behavior: 'auto',
                block: 'center'
            });
        }

        this.activeIndex = index;
    },

    clear() {
        if (this.activeIndex >= 0) {
            this.timeline[this.activeIndex].el.classList.remove('active');
        }
        this.activeIndex = -1;
    }
};
