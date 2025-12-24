document.addEventListener('DOMContentLoaded', () => {

    const editor = document.getElementById('editor-content');
    const audio = document.getElementById('lk-audio');
    const menu = document.getElementById('context-menu');
    const modal = document.getElementById('time-modal');

    const stylePanel = document.getElementById('word-style-panel');

    let selectedWords = [];
    let activeWord = null;
    let needsRebuild = false;

    /* ============================================================
       UTIL – EXTRAI TEXTO PURO (SEM HTML)
    ============================================================ */
    function extractPlainText() {
        const words = [...editor.querySelectorAll('.word')];

        // 🚨 TEXTO EDITADO (não estruturado)
        if (words.length === 0) {
            needsRebuild = true;

            return editor.innerText
                .replace(/[ \t]+\n/g, '\n')
                .replace(/\n{3,}/g, '\n\n')
                .trim();
        }

        // ✅ TEXTO ORDENADO
        let text = '';

        words.forEach((word, index) => {
            const currentText = word.innerText.trim();
            if (!currentText) return;

            text += currentText;

            const next = words[index + 1];
            if (!next) return;

            const blockNow = word.closest('div');
            const blockNext = next.closest('div');

            text += blockNow !== blockNext ? '\n' : ' ';
        });

        return text
            .replace(/[ \t]+\n/g, '\n')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    }




    /* ============================================================
       TOOLBAR VISUAL (NÃO PERSISTE)
    ============================================================ */
    document.querySelectorAll('.editor-toolbar button').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            document.execCommand(btn.dataset.cmd, false, null);
        });
    });

    /* ============================================================
       DETECÇÃO DE ALTERAÇÃO REAL (REBUILD)
    ============================================================ */
    let lastText = extractPlainText();

    editor.addEventListener('input', () => {
        const current = extractPlainText();
        if (current !== lastText) {
            needsRebuild = true;
            lastText = current;
        }
    });

    editor.addEventListener('paste', () => {
        needsRebuild = true;
    });

    /* ============================================================
       SELEÇÃO DE PALAVRAS
    ============================================================ */
    editor.addEventListener('click', e => {
        const word = e.target.closest('.word');
        if (!word) return;

        if (e.ctrlKey) {
            word.classList.toggle('selected');
        } else {
            editor.querySelectorAll('.word').forEach(w => w.classList.remove('selected'));
            word.classList.add('selected');
        }

        selectedWords = [...editor.querySelectorAll('.word.selected')];
        activeWord = word;

        syncStylePanel(word);
    });

    /* ============================================================
       MENU CONTEXTUAL
    ============================================================ */
    editor.addEventListener('contextmenu', e => {
        const word = e.target.closest('.word');
        if (!word) return;

        e.preventDefault();
        activeWord = word;

        menu.style.display = 'block';
        menu.style.left = `${e.pageX}px`;
        menu.style.top = `${e.pageY}px`;

        const groupBtn = menu.querySelector('[data-action="group"]');
        groupBtn.style.display = selectedWords.length > 1 ? 'block' : 'none';
    });

    document.addEventListener('click', () => {
        menu.style.display = 'none';
    });

    /* ============================================================
       MENU – AÇÕES
    ============================================================ */
    menu.addEventListener('click', e => {
        const action = e.target.dataset.action;
        if (!action || !activeWord) return;

        if (action === 'edit-time') {
            modal.style.display = 'block';
            document.getElementById('start-time').value = activeWord.dataset.start || '';
            document.getElementById('end-time').value = activeWord.dataset.end || '';
        }

        if (action === 'group' && selectedWords.length > 1) {
            const start = Math.min(...selectedWords.map(w => Number(w.dataset.start)));
            const end = Math.max(...selectedWords.map(w => Number(w.dataset.end)));

            selectedWords.forEach(w => {
                w.dataset.start = start;
                w.dataset.end = end;
            });
        }
    });

    /* ============================================================
       MODAL – SALVAR TEMPO
    ============================================================ */
    document.getElementById('save-time').addEventListener('click', () => {
        if (!activeWord) return;

        activeWord.dataset.start = document.getElementById('start-time').value;
        activeWord.dataset.end = document.getElementById('end-time').value;
        modal.style.display = 'none';
    });

    /* ============================================================
       KARAOKE
    ============================================================ */
    audio.addEventListener('timeupdate', () => {
        const currentMs = audio.currentTime * 1000;

        editor.querySelectorAll('.word').forEach(word => {
            const start = Number(word.dataset.start);
            const end = Number(word.dataset.end);

            if (Number.isNaN(start) || Number.isNaN(end)) {
                word.classList.remove('active');
                return;
            }

            word.classList.toggle(
                'active',
                currentMs >= start && currentMs <= end
            );
        });
    });

    /* ============================================================
       PAINEL DE ESTILO – SINCRONIZA COM PALAVRA
    ============================================================ */
    function syncStylePanel(word) {
        if (!word || !stylePanel) return;

        document.getElementById('font-family').value = word.dataset.fontFamily || '';
        document.getElementById('font-size').value = word.dataset.fontSize || '';
        document.getElementById('letter-spacing').value = word.dataset.letterSpacing || '';
        document.getElementById('line-height').value = word.dataset.lineHeight || '';
        document.getElementById('style-color').value = word.dataset.color || '#000000';
        document.getElementById('style-background').value = word.dataset.background || '#ffffff';
    }

    /* ============================================================
       PAINEL DE ESTILO – APLICAÇÕES
    ============================================================ */
    document.getElementById('font-family').addEventListener('change', e => {
        if (!activeWord) return;
        activeWord.style.fontFamily = e.target.value;
        activeWord.dataset.fontFamily = e.target.value;
    });

    document.getElementById('font-size').addEventListener('change', e => {
        if (!activeWord) return;
        activeWord.style.fontSize = `${e.target.value}px`;
        activeWord.dataset.fontSize = e.target.value;
    });

    document.getElementById('letter-spacing').addEventListener('input', e => {
        if (!activeWord) return;
        activeWord.style.letterSpacing = `${e.target.value}em`;
        activeWord.dataset.letterSpacing = `${e.target.value}em`;
    });

    document.getElementById('line-height').addEventListener('input', e => {
        if (!activeWord) return;
        activeWord.style.lineHeight = e.target.value;
        activeWord.dataset.lineHeight = e.target.value;
    });

    document.getElementById('style-color').addEventListener('input', e => {
        if (!activeWord) return;
        activeWord.style.color = e.target.value;
        activeWord.dataset.color = e.target.value;
    });

    document.getElementById('style-background').addEventListener('input', e => {
        if (!activeWord) return;
        activeWord.style.backgroundColor = e.target.value;
        activeWord.dataset.background = e.target.value;
    });

    document.querySelectorAll('#word-style-panel button').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!activeWord) return;

            const style = btn.dataset.style;

            if (style === 'bold') {
                const v = activeWord.style.fontWeight === 'bold' ? 'normal' : 'bold';
                activeWord.style.fontWeight = v;
                activeWord.dataset.fontWeight = v;
            }

            if (style === 'italic') {
                const v = activeWord.style.fontStyle === 'italic' ? 'normal' : 'italic';
                activeWord.style.fontStyle = v;
                activeWord.dataset.fontStyle = v;
            }

            if (style === 'underline') {
                const v = activeWord.style.textDecoration === 'underline' ? 'none' : 'underline';
                activeWord.style.textDecoration = v;
                activeWord.dataset.underline = v === 'underline' ? 1 : 0;
            }
        });
    });

    /* ============================================================
       SALVAR TRANSCRIÇÃO
    ============================================================ */
    document.getElementById('save-editor').addEventListener('click', async () => {

        console.log(extractPlainText());

        if (needsRebuild) {
            const ok = confirm(
                'O texto foi alterado. A transcrição será reorganizada automaticamente. Continuar?'
            );
            if (!ok) return;

            await fetch(
                `${LK_EDITOR.rest_url}/transcript/${LK_EDITOR.transcript_id}/rebuild`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': LK_EDITOR.nonce
                    },
                    body: JSON.stringify({ text: extractPlainText() })
                }
            );

            location.reload();
            return;
        }

        const words = [...editor.querySelectorAll('.word')].map(w => ({
            id: w.dataset.id,
            word: w.innerText.trim(),
            start_ms: w.dataset.start,
            end_ms: w.dataset.end,

            font_family: w.dataset.fontFamily || null,
            font_size: w.dataset.fontSize || null,
            font_weight: w.dataset.fontWeight || null,
            font_style: w.dataset.fontStyle || null,
            underline: w.dataset.underline == 1 ? 1 : 0,
            color: w.dataset.color || null,
            background: w.dataset.background || null,
            letter_spacing: w.dataset.letterSpacing || null,
            line_height: w.dataset.lineHeight || null
        }));

        await fetch(
            `${LK_EDITOR.rest_url}/transcript/${LK_EDITOR.transcript_id}/words`,
            {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': LK_EDITOR.nonce
                },
                body: JSON.stringify({ words })
            }
        );

        alert('Salvo com sucesso');
    });

});
