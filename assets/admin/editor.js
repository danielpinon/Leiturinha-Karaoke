document.addEventListener('DOMContentLoaded', () => {

    const editor = document.getElementById('editor-content');
    const audio = document.getElementById('lk-audio');
    const menu = document.getElementById('context-menu');
    const modal = document.getElementById('time-modal');

    let selectedWords = [];
    let activeWord = null;
    let needsRebuild = false;

    /* ============================================================
       TOOLBAR (formatação visual apenas)
    ============================================================ */
    document.querySelectorAll('.editor-toolbar button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.execCommand(btn.dataset.cmd, false, null);
        });
    });

    /* ============================================================
       DETECÇÃO DE ALTERAÇÃO ESTRUTURAL
       (gatilho para rebuild)
    ============================================================ */
    editor.addEventListener('paste', () => {
        needsRebuild = true;
    });

    editor.addEventListener('input', e => {
        if (!e.target.closest('.word')) {
            needsRebuild = true;
        }
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
            editor.querySelectorAll('.word')
                .forEach(w => w.classList.remove('selected'));
            word.classList.add('selected');
        }

        selectedWords = [...editor.querySelectorAll('.word.selected')];
    });

    /* ============================================================
       MENU DE CONTEXTO (botão direito)
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
       AÇÕES DO MENU
    ============================================================ */
    menu.addEventListener('click', e => {
        const action = e.target.dataset.action;
        if (!action) return;

        if (action === 'edit-time') {
            modal.style.display = 'block';
            document.getElementById('start-time').value = activeWord.dataset.start || '';
            document.getElementById('end-time').value = activeWord.dataset.end || '';
        }

        if (action === 'group' && selectedWords.length > 1) {
            const start = Math.min(...selectedWords.map(w => +w.dataset.start));
            const end = Math.max(...selectedWords.map(w => +w.dataset.end));

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
        activeWord.dataset.start = document.getElementById('start-time').value;
        activeWord.dataset.end = document.getElementById('end-time').value;
        modal.style.display = 'none';
    });

    /* ============================================================
       KARAOKE – SINCRONIZAÇÃO COM ÁUDIO
    ============================================================ */
    audio.addEventListener('timeupdate', () => {
        const currentMs = audio.currentTime * 1000;

        editor.querySelectorAll('.word').forEach(word => {
            const start = Number(word.dataset.start);
            const end = Number(word.dataset.end);

            // 🔥 ignora palavras sem timing
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
       SALVAR TRANSCRIÇÃO
    ============================================================ */
    document.getElementById('save-editor').addEventListener('click', async () => {

        // 🔴 TEXTO MODIFICADO → REBUILD
        if (needsRebuild) {
            const ok = confirm(
                'O texto foi alterado. A transcrição será reorganizada automaticamente. Continuar?'
            );

            if (!ok) return;

            await rebuildTranscript();
            return;
        }

        // 🟢 ALTERAÇÃO NORMAL (tempo, grupo, ordem)
        const words = [...editor.querySelectorAll('.word')].map(w => ({
            id: w.dataset.id,
            word: w.innerText.trim(),
            start_ms: w.dataset.start,
            end_ms: w.dataset.end
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

    /* ============================================================
       REBUILD TRANSCRIPT (FONTE DA VERDADE = BANCO)
    ============================================================ */
    async function rebuildTranscript() {
        const text = editor.innerText.trim();

        await fetch(
            `${LK_EDITOR.rest_url}/transcript/${LK_EDITOR.transcript_id}/rebuild`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': LK_EDITOR.nonce
                },
                body: JSON.stringify({ text })
            }
        );

        location.reload(); // seguro e consistente
    }

});
