import {
    Chart, LineController, LineElement, PointElement, BarController, BarElement,
    CategoryScale, LinearScale, Legend, Tooltip,
} from 'chart.js';

Chart.register(LineController, LineElement, PointElement, BarController, BarElement,
    CategoryScale, LinearScale, Legend, Tooltip);

const colors = ['#2584c5', '#8998ab', '#8a7fa8', '#b79a70', '#63a6a0', '#b18eab'];
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const charts = [];

document.querySelectorAll('[data-report-chart]').forEach((section) => {
    const canvas = section.querySelector('canvas');
    if (!canvas) return;

    const config = JSON.parse(section.dataset.reportChart);
    const formatter = new Intl.NumberFormat(document.documentElement.lang, config.currency
        ? { style: 'currency', currency: config.currency }
        : { maximumFractionDigits: 0 });
    section.querySelector('[data-chart-visual]').hidden = false;

    const chart = new Chart(canvas, {
        type: config.type ?? 'line',
        data: {
            labels: config.labels,
            datasets: config.datasets.map((dataset, index) => ({
                ...dataset,
                borderColor: colors[index % colors.length],
                backgroundColor: colors[index % colors.length],
                borderWidth: config.type === 'bar' ? 0 : 2,
                pointRadius: 2,
                pointHoverRadius: 4,
                tension: 0.15,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: reducedMotion.matches ? false : { duration: 300 },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: config.datasets.length > 1, position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: ({ dataset, parsed }) => `${dataset.label}: ${formatter.format(parsed.y)}`,
                    },
                },
            },
            scales: {
                x: { stacked: config.stacked ?? false, grid: { display: false }, ticks: { maxTicksLimit: 8 } },
                y: {
                    stacked: config.stacked ?? false,
                    beginAtZero: true,
                    ticks: { precision: config.integer ? 0 : undefined },
                    title: { display: Boolean(config.currency), text: config.currency ?? '' },
                },
            },
        },
    });
    charts.push({ chart, section });
});

function updateTheme() {
    charts.forEach(({ chart, section }) => {
        const style = getComputedStyle(section);
        chart.options.plugins.legend.labels.color = style.color;
        Object.values(chart.options.scales).forEach((scale) => {
            scale.ticks.color = style.color;
            scale.title.color = style.color;
            scale.border.color = style.borderTopColor;
            scale.grid.color = style.borderTopColor;
        });
        chart.update('none');
    });
}

updateTheme();
new MutationObserver(updateTheme).observe(document.documentElement, {
    attributes: true, attributeFilter: ['data-bs-theme'],
});
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', updateTheme);
