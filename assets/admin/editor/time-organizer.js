window.LKEditorTimeOrganizer = {

    organize(options = {}) {

        const {
            maxAudioTime = null,
            ignorePunctuation = false,
            manualWordTimes = {}
        } = options;

        const { editor } = window.LKEditorState;
        if (!editor) return;

        const MIN_WORD_TIME = 200;

        let cursor = 0;
        let tempoFinal = 0;

        const nodes = [...editor.childNodes];

        /* ===============================
         * HELPERS
         * =============================== */

        const cleanText = text => {
            // Remove pontuação e caracteres especiais para contar apenas letras/números
            return text.replace(/[.,!?;:"“"”()]/g, '').trim();
        };

        const isTimedWord = word =>
            /[\p{L}\p{N}]/u.test(word.innerText);

        const isTemporalToken = text =>
            /^(\d{1,2}\/|\d{2,4}|às|\d{1,2}h\d{2}|\d{1,2}:\d{2})$/iu
                .test(text.trim());

        const baseWordTime = word => {
            const rawText = word.innerText.trim();
            const text = cleanText(rawText);
            
            if (!text) return MIN_WORD_TIME;

            /* ===============================
             * NÚMEROS E TOKENS NUMÉRICOS
             * =============================== */
            if (/^\d+$/.test(text)) {
                return Math.max(MIN_WORD_TIME, Math.min(250 + (text.length * 90), 1200));
            }

            /* ===============================
             * BASE LINGUÍSTICA
             * =============================== */
            const letters = (text.match(/[\p{L}]/gu) || []).length;
            // Base mais leve: 180ms + 28ms por letra
            let time = 180 + (letters * 28);

            /* ===============================
             * ONOMATOPEIAS E REPETIÇÕES
             * =============================== */
            if (/(.)\1{1,}/i.test(text) || (text.includes('-') && text.split('-')[0] === text.split('-')[1])) {
                time *= 0.8; 
            }

            /* ===============================
             * UPPERCASE (Apenas se for palavra real)
             * =============================== */
            if (text === text.toUpperCase() && letters > 1) {
                time *= 1.1;
            }

            /* ===============================
             * ACENTOS E COMPLEXIDADE
             * =============================== */
            if (/[áéíóúâêôãõà]/i.test(text)) time += 40;
            if (/(ão|õe|ães|am|em|im|om|um)$/i.test(text)) time += 50;
            if (/(lh|nh|ch|rr|ss|br|cr|dr|fr|gr|pr|tr|vr)/i.test(text)) time += 50;

            /* ===============================
             * PALAVRAS COMPOSTAS
             * =============================== */
            if (rawText.includes('-') || rawText.includes('/')) {
                const parts = rawText.split(/[-/]/).filter(Boolean);
                time += parts.length * 100;
            }

            return Math.max(MIN_WORD_TIME, Math.min(Math.floor(time), 2200));
        };

        const semanticPauseAfter = word => {
            if (ignorePunctuation) return 0;
            const t = word.innerText.trim();

            // Suporte a aspas curvas e múltiplas pontuações
            if (/[?!.]$|[?!.]["”]$/.test(t)) return 350;
            if (/[,;:]$|[,;:]["”]$/.test(t)) return 120;

            return 0;
        };

        /* ===============================
         * TIMELINE
         * =============================== */

        for (let i = 0; i < nodes.length; i++) {
            const node = nodes[i];

            if (node.nodeName === 'BR') {
                cursor += 150;
                tempoFinal = cursor;
                continue;
            }

            if (!(node instanceof HTMLElement)) continue;
            if (!node.classList.contains('word')) continue;

            if (!isTimedWord(node)) {
                node.dataset.start = cursor;
                node.dataset.end = cursor;
                continue;
            }

            const text = node.innerText.trim();

            if (isTemporalToken(text)) {
                const group = [node];
                let j = i + 1;
                while (j < nodes.length && nodes[j] instanceof HTMLElement && nodes[j].classList.contains('word') && isTemporalToken(nodes[j].innerText.trim())) {
                    group.push(nodes[j]);
                    j++;
                }
                const groupDuration = 800 + (group.length * 70);
                const perWord = Math.max(MIN_WORD_TIME, Math.floor(groupDuration / group.length));
                group.forEach(w => {
                    w.dataset.start = cursor;
                    w.dataset.end = cursor + perWord;
                    cursor += perWord;
                });
                cursor += 50;
                tempoFinal = cursor;
                i = j - 1;
                continue;
            }

            const start = cursor;
            const idStr = node.dataset.id ? String(node.dataset.id) : null;
            const manualVal = idStr !== null && Object.prototype.hasOwnProperty.call(manualWordTimes, idStr) ? Number(manualWordTimes[idStr]) : null;

            let duration = Number.isFinite(manualVal) && manualVal > 0 ? manualVal : baseWordTime(node);
            duration = Math.max(MIN_WORD_TIME, Math.floor(duration));

            node.dataset.start = start;
            node.dataset.end = start + duration;

            cursor = node.dataset.end * 1 + semanticPauseAfter(node);
            tempoFinal = cursor;
        }

        /* ===============================
         * NORMALIZAÇÃO FINAL (Obrigatória)
         * =============================== */
        if (maxAudioTime) {
            const factor = maxAudioTime / tempoFinal;
            let prevEnd = 0;

            editor.querySelectorAll('.word').forEach(word => {
                const s = Number(word.dataset.start || 0);
                const e = Number(word.dataset.end || 0);

                // Garante que os tempos sejam inteiros e sequenciais
                const ns = Math.max(prevEnd, Math.floor(s * factor));
                const ne = Math.max(ns + MIN_WORD_TIME, Math.floor(e * factor));

                word.dataset.start = ns;
                word.dataset.end = ne;
                prevEnd = ne;
            });
            tempoFinal = maxAudioTime;
        }

        window.LKEditorState.needsRebuild = true;
        if (window.LKEditorKaraoke) window.LKEditorKaraoke.buildTimeline();

        console.info(`⏱ Reorganizado: ${tempoFinal}ms`);
    }
};
