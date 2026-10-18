import {
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    ArcElement,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
    Filler,
} from 'chart.js';
import { formatMinutes } from './waiting-timers';

Chart.register(
    BarController, BarElement, CategoryScale, LinearScale, LineController, LineElement,
    PointElement, DoughnutController, ArcElement, Legend, Tooltip, Filler,
);

const css = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
const ATTENTION = '#f59e0b';
const compactMinutes = (v) => (v < 60 ? `${v}min` : `${Math.round(v / 60)}h`);
const minutesLabel = (v) => (v === null || v === undefined ? '—' : formatMinutes(v));

export function initDashboardCharts(m) {
    const primary = css('--primary');
    const muted = css('--text-subtle');

    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.font.size = 12;
    Chart.defaults.color = css('--text-muted');
    Chart.defaults.borderColor = css('--border');
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.backgroundColor = '#0b1a1d';

    const minutesAxis = { beginAtZero: true, grid: { drawTicks: false }, ticks: { callback: (v) => compactMinutes(v) } };

    // Percepção x hora de abertura
    const hours = Object.keys(m.by_hour);
    new Chart(document.getElementById('chart-hour'), {
        data: {
            labels: hours,
            datasets: [
                {
                    type: 'bar',
                    label: 'Tempo até percepção (mediana)',
                    data: hours.map((h) => m.by_hour[h].median),
                    backgroundColor: hours.map((h) => (m.by_hour[h].median > 60 ? ATTENTION : primary)),
                    borderRadius: 5,
                    maxBarThickness: 18,
                    yAxisID: 'y',
                },
                {
                    type: 'line',
                    label: 'Chamados abertos',
                    data: hours.map((h) => m.by_hour[h].count),
                    borderColor: muted,
                    borderDash: [4, 4],
                    borderWidth: 1.5,
                    pointRadius: 0,
                    cubicInterpolationMode: 'monotone',
                    yAxisID: 'y1',
                },
            ],
        },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: (ctx) => (ctx.datasetIndex === 0 ? ` Percepção: ${minutesLabel(ctx.raw)}` : ` Chamados: ${ctx.raw}`),
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 8 } },
                y: minutesAxis,
                y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 } },
            },
        },
    });

    // Volume diário
    const days = Object.keys(m.by_day);
    const ctxDay = document.getElementById('chart-day');
    const gradient = ctxDay.getContext('2d').createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0, primary + '40');
    gradient.addColorStop(1, primary + '00');
    new Chart(ctxDay, {
        type: 'line',
        data: {
            labels: days.map((d) => `${d.slice(8, 10)}/${d.slice(5, 7)}`),
            datasets: [{
                label: 'Chamados abertos',
                data: Object.values(m.by_day),
                borderColor: primary,
                borderWidth: 2,
                backgroundColor: gradient,
                fill: true,
                cubicInterpolationMode: 'monotone',
                pointRadius: 0,
                pointHoverRadius: 4,
            }],
        },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 14 } },
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { drawTicks: false } },
            },
        },
    });

    // Por categoria
    new Chart(document.getElementById('chart-category'), {
        type: 'doughnut',
        data: {
            labels: m.by_category.map((c) => c.name),
            datasets: [{
                data: m.by_category.map((c) => c.count),
                backgroundColor: m.by_category.map((c) => c.color),
                borderColor: css('--surface'),
                borderWidth: 3,
                hoverOffset: 6,
            }],
        },
        options: {
            maintainAspectRatio: false,
            cutout: '66%',
            plugins: { legend: { position: 'right', labels: { padding: 12 } } },
        },
    });

    // Percepção por dia da semana
    const weekdays = Object.keys(m.by_weekday);
    const weekdayValues = Object.values(m.by_weekday);
    new Chart(document.getElementById('chart-weekday'), {
        type: 'bar',
        data: {
            labels: weekdays,
            datasets: [{
                label: 'Tempo até percepção (mediana)',
                data: weekdayValues,
                backgroundColor: weekdayValues.map((v) => (v > 60 ? ATTENTION : primary)),
                borderRadius: 6,
                maxBarThickness: 36,
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => ` ${minutesLabel(ctx.raw)}` } },
            },
            scales: { x: { grid: { display: false } }, y: minutesAxis },
        },
    });
}
