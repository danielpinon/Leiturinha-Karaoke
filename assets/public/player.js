document.addEventListener('DOMContentLoaded', () => {

    document.querySelectorAll('.lk-karaoke-wrapper').forEach(wrapper => {

        const audio = wrapper.querySelector('.lk-karaoke-audio');
        const wordEls = Array.from(wrapper.querySelectorAll('.lk-word'));

        if (!audio || !wordEls.length) return;

        /* =====================================================
         * PRÉ-PROCESSAMENTO (PERFORMANCE)
         * ===================================================== */
        const timeline = wordEls.map(w => {
            let start = parseInt(w.dataset.start, 10) || 0;
            let end   = parseInt(w.dataset.end, 10) || start;

            // 🔒 tempo mínimo por palavra (evita “sumir”)
            if (end - start < 80) {
                end = start + 80;
            }

            return {
                el: w,
                start,
                end
            };
        });

        /* =====================================================
         * CLIQUE → SEEK
         * ===================================================== */
        timeline.forEach(item => {
            item.el.addEventListener('click', () => {
                audio.currentTime = item.start / 1000;
                audio.play();
            });
        });

        /* =====================================================
         * KARAOKE LOOP (RAF)
         * ===================================================== */
        let rafId = null;
        let activeIndex = -1;

        function sync() {
            const currentMs = audio.currentTime * 1000;

            // Busca incremental (não varre tudo)
            let i = activeIndex;

            // Avança
            while (i + 1 < timeline.length && currentMs >= timeline[i + 1].start) {
                i++;
            }

            // Recuo (seek manual)
            while (i > 0 && currentMs < timeline[i].start) {
                i--;
            }

            if (i !== activeIndex) {
                // remove anterior
                if (activeIndex >= 0) {
                    timeline[activeIndex].el.classList.remove('active');
                }

                // ativa nova
                if (timeline[i] && currentMs < timeline[i].end) {
                    const el = timeline[i].el;
                    el.classList.add('active');

                    // Scroll só quando muda
                    el.scrollIntoView({
                        behavior: 'auto',
                        block: 'center'
                    });

                    activeIndex = i;
                }
            }

            rafId = requestAnimationFrame(sync);
        }

        /* =====================================================
         * CONTROLES DE PLAYBACK
         * ===================================================== */
        audio.addEventListener('play', () => {
            cancelAnimationFrame(rafId);
            rafId = requestAnimationFrame(sync);
        });

        audio.addEventListener('pause', () => {
            cancelAnimationFrame(rafId);
        });

        audio.addEventListener('ended', () => {
            cancelAnimationFrame(rafId);

            if (activeIndex >= 0) {
                timeline[activeIndex].el.classList.remove('active');
            }

            activeIndex = -1;
        });

    });

});
