/**
 * Atualiza "aguardando há X" dos chamados ainda não assumidos, sem recarregar a página.
 * Marca em vermelho quando passa da meta de resposta da categoria.
 */
export function initWaitingTimers() {
    const timers = document.querySelectorAll('[data-waiting-since]');
    if (!timers.length) return;

    const tick = () => {
        const now = Date.now();
        timers.forEach((el) => {
            const minutes = Math.max(0, Math.floor((now - Date.parse(el.dataset.waitingSince)) / 60000));
            const limit = Number(el.dataset.limit) || 15;
            el.textContent = formatMinutes(minutes);
            el.classList.toggle('is-late', minutes > limit);
            el.closest('.queue__item, tr')?.classList.toggle('is-late', minutes > limit);
        });
    };

    tick();
    setInterval(tick, 30000);
}

export function formatMinutes(minutes) {
    if (minutes < 60) return `${minutes} min`;
    if (minutes < 1440) return `${Math.floor(minutes / 60)}h${String(minutes % 60).padStart(2, '0')}`;
    const days = Math.floor(minutes / 1440);
    return `${days} ${days === 1 ? 'dia' : 'dias'} ${Math.floor((minutes % 1440) / 60)}h`;
}
