// assets/admin/editor/time-organizer.js

window.LKEditorTimeOrganizer = {

    organize(options = {}) {

        const {
            maxAudioTime = null,
            ignorePunctuation = false,
            manualWordTimes = {}
        } = options;

        const { editor } = window.LKEditorState;
        if (!editor) return;

        let cursor = 0;
        let tempoFinal = 0;

        const nodes = [...editor.childNodes];

        /* ===============================
         * HELPERS
         * =============================== */

        const isTimedWord = word =>
            /[\p{L}\p{N}]/u.test(word.innerText);

        const baseWordTime = word => {

            const text = word.innerText.trim();
            if (!text) return 0;

            // números
            if (/^\d+$/.test(text)) {
                const digits = text.length;
                return Math.min(250 + (digits * 90), 1200);
            }

            let letters = text.replace(/[^\p{L}]/gu, '').length;
            let time = 250 + (letters * 32);

            if (text === text.toUpperCase()) {
                time *= 1.12;
            }

            if (/[áéíóúâêôãõà]/i.test(text)) {
                time += 60;
            }

            return Math.min(Math.floor(time), 2000);
        };

        const semanticPauseAfter = word => {
            if (ignorePunctuation) return 0;

            const t = word.innerText.trim();

            if (t === '—') return 400;
            if (t.endsWith('?')) return 450;
            if (t.endsWith('!')) return 420;
            if (t.endsWith('.')) return 350;
            if (t.endsWith(':')) return 280;
            if (t.endsWith(',')) return 180;

            const upper = t.toUpperCase();
            if (['AÍ', 'ENTÃO'].includes(upper)) return 200;
            if (['DISSE', 'RESPONDEU'].includes(upper)) return 180;

            return 0;
        };

        /* ===============================
         * TIMELINE
         * =============================== */

        nodes.forEach(node => {

            // <br>
            if (node.nodeName === 'BR') {
                cursor += 250;
                tempoFinal = cursor;
                return;
            }

            if (!(node instanceof HTMLElement)) return;
            if (!node.classList.contains('word')) return;

            const id = node.dataset.id ? Number(node.dataset.id) : null;

            if (!isTimedWord(node)) {
                node.dataset.start = cursor;
                node.dataset.end = cursor;
                return;
            }

            const start = cursor;

            const manual = id && manualWordTimes[id]
                ? Math.max(1, manualWordTimes[id])
                : null;

            const duration = manual !== null
                ? manual
                : baseWordTime(node);

            const end = start + duration;

            node.dataset.start = start;
            node.dataset.end = end;

            cursor = end + semanticPauseAfter(node);
            tempoFinal = cursor;
        });

        /* ===============================
         * LIMITA AO TEMPO DO ÁUDIO
         * =============================== */

        if (maxAudioTime && tempoFinal > maxAudioTime) {

            const factor = maxAudioTime / tempoFinal;
            let prevEnd = 0;

            editor.querySelectorAll('.word').forEach(word => {

                const s = Number(word.dataset.start || 0);
                const e = Number(word.dataset.end || 0);

                let ns = Math.max(prevEnd, Math.floor(s * factor));
                let ne = Math.max(ns + 1, Math.floor(e * factor));

                word.dataset.start = ns;
                word.dataset.end = ne;

                prevEnd = ne;
            });

            tempoFinal = maxAudioTime;
        }

        /* ===============================
         * FINALIZA
         * =============================== */

        window.LKEditorState.needsRebuild = true;

        console.info(
            `⏱ Tempos reorganizados — final: ${tempoFinal} ms`,
            { ignorePunctuation, manualWordTimes }
        );
    }
};
