/**
 * Acompanhamento de novos chamados.
 *
 * Consulta /fila/novidades periodicamente e, quando chega chamado novo:
 *  - mostra um aviso na tela (toast) e toca um som curto;
 *  - dispara notificação do navegador (se o técnico permitiu), útil com a aba em segundo plano;
 *  - atualiza o contador de "aguardando percepção" no menu e no título da aba.
 */
const STORAGE_SOUND = 'atendly:sound';

let latestId = 0;
let baseTitle = document.title;
let audioCtx = null;

export function initQueueNotifier() {
    const { pollUrl, latestId: initialId, pollSeconds } = document.body.dataset;
    if (!pollUrl) return;

    latestId = Number(initialId) || 0;
    baseTitle = document.title.replace(/^\(\d+\)\s*/, '');

    setupControls();
    poll(pollUrl);
    setInterval(() => poll(pollUrl), (Number(pollSeconds) || 15) * 1000);
}

async function poll(url) {
    try {
        const response = await fetch(`${url}?desde=${latestId}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!response.ok) return;

        const data = await response.json();
        updateCounters(data);

        if (data.new.length && latestId > 0) {
            data.new.forEach(announce);
            showRefreshNotice(data.new.length);
            playChime();
        }
        latestId = Math.max(latestId, data.latest_id);
    } catch {
        // Sem conexão: tenta de novo no próximo ciclo.
    }
}

function updateCounters({ unacknowledged, late }) {
    document.title = unacknowledged > 0 ? `(${unacknowledged}) ${baseTitle}` : baseTitle;

    document.querySelectorAll('[data-unack-count]').forEach((el) => {
        el.textContent = unacknowledged;
        el.hidden = unacknowledged === 0;
        el.classList.toggle('is-alert', late > 0);
        el.title = late > 0 ? `${late} acima do tempo esperado` : '';
    });
}

function announce(ticket) {
    toast(ticket);

    if ('Notification' in window && Notification.permission === 'granted') {
        const n = new Notification(`Novo chamado ${ticket.protocol}`, {
            body: `${ticket.title}${ticket.category ? ` · ${ticket.category}` : ''}`,
            tag: `ticket-${ticket.id}`,
        });
        n.onclick = () => {
            window.focus();
            window.location.href = ticket.url;
        };
    }
}

function toast(ticket) {
    let container = document.querySelector('.toasts');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toasts';
        container.setAttribute('aria-live', 'polite');
        document.body.append(container);
    }

    const el = document.createElement('div');
    el.className = 'toast';
    el.innerHTML = `
        <div class="toast__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
        </div>
        <div class="toast__body">
            <div class="toast__title">Novo chamado <span class="mono"></span></div>
            <div class="toast__text"></div>
            <a class="btn btn--primary btn--sm" href="">Abrir chamado</a>
        </div>
        <button type="button" class="toast__close" aria-label="Fechar">×</button>`;
    // textContent evita injeção de HTML vindo do título do chamado.
    el.querySelector('.mono').textContent = ticket.protocol;
    el.querySelector('.toast__text').textContent = `${ticket.title} · ${ticket.category ?? 'Sem categoria'} · ${ticket.priority}`;
    el.querySelector('a').href = ticket.url;
    el.querySelector('.toast__close').addEventListener('click', () => el.remove());

    container.append(el);
    setTimeout(() => el.remove(), 30000);
}

function showRefreshNotice(count) {
    const slot = document.querySelector('[data-refresh-notice]');
    if (!slot) return;

    const current = Number(slot.dataset.count || 0) + count;
    slot.dataset.count = current;
    slot.hidden = false;
    slot.querySelector('[data-refresh-text]').textContent =
        current === 1 ? '1 novo chamado chegou.' : `${current} novos chamados chegaram.`;
}

function soundEnabled() {
    try {
        return localStorage.getItem(STORAGE_SOUND) !== 'off';
    } catch {
        return true;
    }
}

function playChime() {
    if (!soundEnabled()) return;
    try {
        audioCtx ??= new AudioContext();
        const now = audioCtx.currentTime;
        [880, 1320].forEach((freq, i) => {
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.0001, now + i * 0.18);
            gain.gain.exponentialRampToValueAtTime(0.2, now + i * 0.18 + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + i * 0.18 + 0.3);
            osc.connect(gain).connect(audioCtx.destination);
            osc.start(now + i * 0.18);
            osc.stop(now + i * 0.18 + 0.32);
        });
    } catch {
        // Navegador bloqueou áudio antes de alguma interação do usuário.
    }
}

function setupControls() {
    const notifyBtn = document.querySelector('[data-enable-notifications]');
    if (notifyBtn) {
        const refresh = () => {
            const supported = 'Notification' in window;
            const granted = supported && Notification.permission === 'granted';
            notifyBtn.hidden = !supported || granted;
        };
        refresh();
        notifyBtn.addEventListener('click', async () => {
            await Notification.requestPermission();
            refresh();
        });
    }

    const soundBtn = document.querySelector('[data-toggle-sound]');
    if (soundBtn) {
        const label = () => {
            const on = soundEnabled();
            soundBtn.setAttribute('aria-pressed', String(on));
            soundBtn.title = on ? 'Som de novos chamados: ligado' : 'Som de novos chamados: desligado';
            soundBtn.querySelector('[data-sound-label]').textContent = on ? 'Som ligado' : 'Som desligado';
            soundBtn.querySelector('[data-sound-on]').hidden = !on;
            soundBtn.querySelector('[data-sound-off]').hidden = on;
        };
        label();
        soundBtn.addEventListener('click', () => {
            try {
                localStorage.setItem(STORAGE_SOUND, soundEnabled() ? 'off' : 'on');
            } catch {
                /* armazenamento indisponível */
            }
            label();
            if (soundEnabled()) playChime();
        });
    }
}
