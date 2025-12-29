// assets/admin/editor/karaoke.js

window.LKEditorKaraoke = {

    init() {
        const { audio, editor } = window.LKEditorState;

        audio.addEventListener('timeupdate', () => {
            const currentMs = audio.currentTime * 1000;

            editor.querySelectorAll('.word').forEach(word => {
                const start = Number(word.dataset.start);
                const end = Number(word.dataset.end);

                word.classList.toggle(
                    'active',
                    !isNaN(start) && !isNaN(end) &&
                    currentMs >= start && currentMs <= end
                );
            });
        });
    }

};
