import './bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import '../css/app.css';

document.querySelectorAll('[data-copy-live-link]').forEach((button) => {
    button.addEventListener('click', async () => {
        const status = button.parentElement.querySelector('[data-copy-live-status]');
        try {
            if (!navigator.clipboard || !navigator.clipboard.writeText) throw new Error('Clipboard access is unavailable.');
            await navigator.clipboard.writeText(button.dataset.copyLiveLink);
            status.textContent = 'Live link copied.';
        } catch {
            status.textContent = 'Could not copy the link. Open the preview and copy its address.';
        }
    });
});

const shareDialog = document.getElementById('share-live-dialog');
document.querySelectorAll('[data-open-share-dialog]').forEach((button) => {
    button.addEventListener('click', () => shareDialog?.showModal());
});
document.querySelectorAll('[data-close-share-dialog]').forEach((button) => {
    button.addEventListener('click', () => shareDialog?.close());
});
shareDialog?.addEventListener('click', (event) => {
    if (event.target === shareDialog) shareDialog.close();
});

const playerHistoryDialog = document.querySelector('[data-player-history-dialog]');
if (playerHistoryDialog?.open) {
    playerHistoryDialog.close();
    playerHistoryDialog.showModal();
}
document.querySelectorAll('[data-close-player-history]').forEach((button) => {
    button.addEventListener('click', () => playerHistoryDialog?.close());
});

const rosterSearch = document.querySelector('[data-roster-search]');
const rosterFilter = document.querySelector('[data-roster-filter]');
const rosterRows = [...document.querySelectorAll('[data-roster-row]')];
const rosterVisibleCount = document.querySelector('[data-roster-visible-count]');
const filterRoster = () => {
    const query = rosterSearch?.value.trim().toLocaleLowerCase() ?? '';
    const status = rosterFilter?.value ?? '';
    let visible = 0;
    rosterRows.forEach((row) => {
        row.hidden = !row.dataset.playerName.includes(query)
            || (status !== '' && row.dataset.playerStatus !== status);
        if (!row.hidden) visible++;
    });
    if (rosterVisibleCount) rosterVisibleCount.textContent = String(visible);
};
rosterSearch?.addEventListener('input', filterRoster);
rosterFilter?.addEventListener('change', filterRoster);

document.querySelectorAll('[data-live-timer]').forEach((timer) => {
    const update = () => {
        const seconds = Math.max(0, Math.floor(Date.now() / 1000 - Number(timer.dataset.liveTimer)));
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        timer.textContent = hours
            ? `${String(hours).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`
            : `${String(minutes).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
    };
    update();
    setInterval(update, 1000);
});
