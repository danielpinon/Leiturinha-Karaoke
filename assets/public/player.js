document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.lk-karaoke-wrapper').forEach(wrapper => {

        const audio = wrapper.querySelector('.lk-karaoke-audio');
        const words = Array.from(wrapper.querySelectorAll('.lk-word'));

        if (!audio || !words.length) return;

        /* =====================================================
         * CLIQUE NA PALAVRA → SEEK NO ÁUDIO
         * ===================================================== */
        words.forEach(word => {
            word.addEventListener('click', () => {
                const start = parseInt(word.dataset.start, 10);
                if (!isNaN(start)) {
                    audio.currentTime = start / 1000;
                    audio.play();
                }
            });
        });

        /* =====================================================
         * SYNC + SCROLL
         * ===================================================== */
        let lastActive = null;

        audio.addEventListener('timeupdate', () => {
            const currentMs = audio.currentTime * 1000;

            words.forEach(word => {
                const start = parseInt(word.dataset.start, 10);
                const end   = parseInt(word.dataset.end, 10);

                if (currentMs >= start && currentMs <= end) {
                    if (!word.classList.contains('active')) {
                        word.classList.add('active');

                        // Scroll automático
                        if (lastActive !== word) {
                            word.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                            lastActive = word;
                        }
                    }
                } else {
                    word.classList.remove('active');
                }
            });
        });

        /* =====================================================
         * RESET AO FINAL
         * ===================================================== */
        audio.addEventListener('ended', () => {
            words.forEach(word => word.classList.remove('active'));
            lastActive = null;
        });
    });
});
