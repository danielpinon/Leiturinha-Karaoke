// calculatempo.js
(function () {

    function isTimedWord(text) {
        return /[\p{L}\p{N}]/u.test(text);
    }

    function baseWordTime(text) {
        if (!isTimedWord(text)) return 0;

        const t = text.trim();

        // números
        if (/^\d+$/.test(t)) {
            return Math.min(250 + (t.length * 90), 1200);
        }

        const letters = t.replace(/[^\p{L}]/gu, '').length;
        let time = 250 + (letters * 32);

        if (t === t.toUpperCase()) time *= 1.12;
        if (/[áéíóúâêôãõà]/iu.test(t)) time += 60;

        return Math.min(Math.round(time), 2000);
    }

    function semanticPauseAfter(text, ignorePunctuation) {
        if (ignorePunctuation) return 0;

        const t = text.trim();

        if (t === '—') return 400;
        if (t.endsWith('?')) return 450;
        if (t.endsWith('!')) return 420;
        if (t.endsWith('.')) return 350;
        if (t.endsWith(':')) return 280;
        if (t.endsWith(',')) return 180;

        const u = t.toUpperCase();
        if (['AÍ', 'ENTÃO'].includes(u)) return 200;
        if (['DISSE', 'RESPONDEU'].includes(u)) return 180;

        return 0;
    }

    function structuralPause(node) {
        return node.nodeName === 'BR' ? 250 : 0;
    }

    /* ============================================================
       FUNÇÃO PRINCIPAL (ESPELHO DO PHP)
    ============================================================ */
    window.corrigirTempo = function corrigirTempo(editorEl, options = {}) {

        if (!editorEl) {
            throw new Error('editorEl não informado');
        }

        const tempoAudioMax     = Number.isFinite(options.tempoAudioMax) && options.tempoAudioMax > 0
            ? options.tempoAudioMax
            : null;

        const ignorePunctuation = !!options.ignorePunctuation;
        const manualWordTimes   = options.manualWordTimes || {};

        let cursor = 0;
        let tempoFinal = 0;

        // ---------------- TIMELINE ----------------
        editorEl.childNodes.forEach(node => {

            // <br>
            if (node.nodeName === 'BR') {
                cursor += structuralPause(node);
                tempoFinal = cursor;
                return;
            }

            // palavra
            if (node.nodeType === 1 && node.classList.contains('word')) {

                const text = node.innerText || '';

                if (!isTimedWord(text)) {
                    node.dataset.start = cursor;
                    node.dataset.end = cursor;
                    return;
                }

                const start = cursor;

                const id = node.dataset.id ? Number(node.dataset.id) : null;
                const manual = (id !== null && manualWordTimes[id])
                    ? Math.max(1, Number(manualWordTimes[id]))
                    : null;

                const duration = manual !== null
                    ? manual
                    : baseWordTime(text);

                const end = start + duration;

                node.dataset.start = start;
                node.dataset.end = end;

                cursor = end + semanticPauseAfter(text, ignorePunctuation);
                tempoFinal = cursor;
            }
        });

        // ---------------- LIMITA AO TEMPO DO ÁUDIO ----------------
        if (tempoAudioMax !== null && tempoFinal > tempoAudioMax) {

            const factor = tempoAudioMax / tempoFinal;
            let prevEnd = 0;

            editorEl.querySelectorAll('.word').forEach(w => {
                const s = Number(w.dataset.start) || 0;
                const e = Number(w.dataset.end) || 0;

                const ns = Math.max(prevEnd, Math.floor(s * factor));
                const ne = Math.max(ns + 1, Math.floor(e * factor));

                w.dataset.start = ns;
                w.dataset.end = ne;

                prevEnd = ne;
            });

            tempoFinal = tempoAudioMax;
        }

        // ---------------- RETORNO ----------------
        return {
            tempoFinal,
            ignorePunctuation,
            manualCount: Object.keys(manualWordTimes).length
        };
    };

})();
