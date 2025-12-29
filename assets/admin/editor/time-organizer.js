window.LKEditorTimeOrganizer = {

    organize(options = {}) {

        const {
            maxAudioTime = null,
            ignorePunctuation = false,
            manualWordTimes = {}
        } = options;

        const { editor } = window.LKEditorState;
        if (!editor) return;

        const MIN_WORD_TIME = 250;

        let cursor = 0;
        let tempoFinal = 0;

        const nodes = [...editor.childNodes];

        /* ===============================
         * HELPERS
         * =============================== */

        const isTimedWord = word =>
            /[\p{L}\p{N}]/u.test(word.innerText);

        const isTemporalToken = text =>
            /^(\d{1,2}\/|\d{2,4}|às|\d{1,2}h\d{2}|\d{1,2}:\d{2})$/iu
                .test(text.trim());

        const baseWordTime = word => {

            const text = word.innerText.trim();
            if (!text) return MIN_WORD_TIME;

            /* ===============================
             * NÚMEROS E TOKENS NUMÉRICOS
             * =============================== */

            // número puro
            if (/^\d+$/.test(text)) {
                return Math.max(
                    MIN_WORD_TIME,
                    Math.min(280 + (text.length * 95), 1400)
                );
            }

            // ordinal: 1º, 2ª
            if (/^\d+[ºª]$/.test(text)) {
                return 520;
            }

            /* ===============================
             * BASE LINGUÍSTICA
             * =============================== */

            const letters = (text.match(/[\p{L}]/gu) || []).length;
            let time = 260 + (letters * 34);

            /* ===============================
             * UPPERCASE (ênfase / leitura pausada)
             * =============================== */
            if (text === text.toUpperCase()) {
                time *= 1.15;
            }

            /* ===============================
             * ACENTOS E NASALIZAÇÃO
             * =============================== */
            if (/[áéíóúâêôãõà]/i.test(text)) {
                time += 70;
            }

            if (/(ão|õe|ães|am|em|im|om|um)$/i.test(text)) {
                time += 90;
            }

            /* ===============================
             * ENCONTROS CONSONANTAIS
             * =============================== */
            if (/(lh|nh|ch|rr|ss|br|cr|dr|fr|gr|pr|tr|vr)/i.test(text)) {
                time += 80;
            }

            /* ===============================
             * HIATOS (sa-ú-de)
             * =============================== */
            if (/[aeiouáéíóúâêôãõà]-?[aeiouáéíóúâêôãõà]/i.test(text)) {
                time += 70;
            }

            /* ===============================
             * PALAVRAS COMPOSTAS / CLÍTICOS
             * =============================== */
            if (text.includes('-') || text.includes('/')) {
                const parts = text.split(/[-/]/).filter(Boolean);

                // cada bloco semântico
                time += parts.length * 160;

                // clíticos verbais: entregou-me, levá-lo
                if (/(me|te|se|lhe|lhes|lo|la|los|las|nos|vos)$/i.test(text)) {
                    time += 120;
                }
            }

            /* ===============================
             * SIGLAS
             * =============================== */
            if (/^[A-Z]{2,}$/.test(text)) {
                time += letters * 120;
            }

            /* ===============================
             * LIMITES FINAIS
             * =============================== */
            return Math.max(
                MIN_WORD_TIME,
                Math.min(Math.floor(time), 2800)
            );
        };


        const semanticPauseAfter = word => {
            if (ignorePunctuation) return 0;

            const t = word.innerText.trim();

            if (t.endsWith('?')) return 450;
            if (t.endsWith('!')) return 420;
            if (t.endsWith('.')) return 350;
            if (t.endsWith(',')) return 180;
            if (t.endsWith(':')) return 280;

            return 0;
        };

        /* ===============================
         * TIMELINE
         * =============================== */

        for (let i = 0; i < nodes.length; i++) {

            const node = nodes[i];

            // <br>
            if (node.nodeName === 'BR') {
                cursor += 250;
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

            /* ===== AGRUPAMENTO TEMPORAL (DATA / HORA) ===== */
            if (isTemporalToken(text)) {

                const group = [node];
                let j = i + 1;

                while (
                    j < nodes.length &&
                    nodes[j] instanceof HTMLElement &&
                    nodes[j].classList.contains('word') &&
                    isTemporalToken(nodes[j].innerText.trim())
                ) {
                    group.push(nodes[j]);
                    j++;
                }

                const groupDuration =
                    1050 + (group.length * 90);

                const perWord = Math.max(
                    MIN_WORD_TIME,
                    Math.floor(groupDuration / group.length)
                );

                group.forEach(w => {
                    const start = cursor;
                    const end = start + perWord;

                    w.dataset.start = start;
                    w.dataset.end = end;

                    cursor = end;
                });

                cursor += 90; // pausa curta natural
                tempoFinal = cursor;

                i = j - 1;
                continue;
            }

            /* ===== FLUXO NORMAL ===== */

            const start = cursor;
            const idStr = node.dataset.id ? String(node.dataset.id) : null;

            const manualVal =
                idStr !== null &&
                    Object.prototype.hasOwnProperty.call(manualWordTimes, idStr)
                    ? Number(manualWordTimes[idStr])
                    : null;

            let duration =
                Number.isFinite(manualVal) && manualVal > 0
                    ? manualVal
                    : baseWordTime(node);

            duration = Math.max(MIN_WORD_TIME, Math.floor(duration));

            const end = start + duration;

            node.dataset.start = start;
            node.dataset.end = end;

            cursor = end + semanticPauseAfter(node);
            tempoFinal = cursor;
        }

        /* ===============================
         * LIMITA AO TEMPO DO ÁUDIO
         * =============================== */

        if (maxAudioTime && tempoFinal > maxAudioTime) {

            const factor = maxAudioTime / tempoFinal;
            let prevEnd = 0;

            editor.querySelectorAll('.word').forEach(word => {

                const s = Number(word.dataset.start || 0);
                const e = Number(word.dataset.end || 0);

                const ns = Math.max(
                    prevEnd,
                    Math.floor(s * factor)
                );

                const ne = Math.max(
                    ns + MIN_WORD_TIME,
                    Math.floor(e * factor)
                );

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

        if (window.LKEditorKaraoke) {
            window.LKEditorKaraoke.buildTimeline();
        }

        console.info(
            `⏱ Tempos reorganizados — final: ${tempoFinal} ms`,
            { ignorePunctuation, manualWordTimes }
        );
    }
};
