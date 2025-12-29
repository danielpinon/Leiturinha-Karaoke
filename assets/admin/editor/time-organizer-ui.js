window.LKEditorTimeOrganizerUI = {

    init() {
        this.modal = document.getElementById('time-organizer-modal');
        this.table = document.getElementById('organizer-words-table');

        document
            .getElementById('organizer-add-row')
            .addEventListener('click', () => this.addRow());

        document
            .getElementById('organizer-cancel')
            .addEventListener('click', () => this.close());

        document
            .getElementById('organizer-apply')
            .addEventListener('click', () => this.apply());

        this.fillDefaultAudioTime();
    },

    open() {
        this.fillDefaultAudioTime();
        this.modal.style.display = 'block';
    },

    close() {
        this.modal.style.display = 'none';
    },

    fillDefaultAudioTime() {
        const audio = document.getElementById('lk-audio');
        if (!audio || isNaN(audio.duration)) return;

        document.getElementById('organizer-total-time').value =
            Math.floor(audio.duration * 1000);
    },

    addRow() {
        const row = document.createElement('tr');

        row.innerHTML = `
            <td>
                <select class="organizer-word-select"></select>
            </td>
            <td>
                <input type="number" class="organizer-word-time" min="1">
            </td>
            <td>
                <button class="button organizer-remove">−</button>
            </td>
        `;

        this.populateWordSelect(row.querySelector('.organizer-word-select'));

        row.querySelector('.organizer-remove')
            .addEventListener('click', () => row.remove());

        this.table.appendChild(row);
    },

    populateWordSelect(select) {
        const words = document.querySelectorAll('.word');

        select.innerHTML = '<option value="">Selecione…</option>';

        words.forEach(w => {
            const id = w.dataset.id;
            if (!id) return;

            const opt = document.createElement('option');
            opt.value = id;
            opt.textContent = `${w.innerText} (#${id})`;
            select.appendChild(opt);
        });
    },

    apply() {
        const totalTime = Number(
            document.getElementById('organizer-total-time').value
        );

        const ignorePunctuation =
            document.getElementById('organizer-ignore-punctuation').value === '1';

        const manualWordTimes = {};

        this.table.querySelectorAll('tr').forEach(row => {
            const id = row.querySelector('.organizer-word-select').value;
            const time = row.querySelector('.organizer-word-time').value;

            if (id && time) {
                manualWordTimes[Number(id)] = Number(time);
            }
        });

        window.LKEditorTimeOrganizer.organize({
            maxAudioTime: totalTime || null,
            ignorePunctuation,
            manualWordTimes
        });

        this.close();
    }
};
