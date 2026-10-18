import '@fontsource-variable/plus-jakarta-sans';
import { initQueueNotifier } from './modules/queue-notifier';
import { initWaitingTimers } from './modules/waiting-timers';
import { initAudioRecorder } from './modules/audio-recorder';
import { initUi } from './modules/ui';

document.addEventListener('DOMContentLoaded', () => {
    initUi();
    initWaitingTimers();
    initAudioRecorder();
    initQueueNotifier();

    // Chart.js só é carregado na página do painel.
    const metrics = document.getElementById('metrics-data');
    if (metrics) {
        import('./modules/dashboard-charts').then(({ initDashboardCharts }) =>
            initDashboardCharts(JSON.parse(metrics.textContent)),
        );
    }
});
