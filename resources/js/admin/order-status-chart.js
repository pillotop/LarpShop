import { Chart, DoughnutController, ArcElement, Tooltip } from 'chart.js';

Chart.register(DoughnutController, ArcElement, Tooltip);

const colors = {
    pending: '#8998ab',
    paid: '#2584c5',
    cancelled: '#8a7fa8',
    expired: '#b79a70',
};

document.querySelectorAll('[data-order-status-chart]').forEach((section) => {
    const canvas = section.querySelector('canvas');
    if (!canvas) return;

    const rows = [...section.querySelectorAll('[data-chart-status]')];
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    section.querySelector('[data-chart-visual]').hidden = false;

    const chart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: rows.map((row) => row.dataset.label),
            datasets: [{
                data: rows.map((row) => Number(row.dataset.count)),
                backgroundColor: rows.map((row) => colors[row.dataset.chartStatus]),
                borderWidth: 0,
                hoverOffset: 6,
                spacing: 3,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            layout: { padding: 10 },
            animation: reducedMotion.matches ? false : { duration: 300 },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ({ dataIndex }) => {
                            const { label, count, percent } = rows[dataIndex].dataset;
                            return `${label}: ${count} (${percent}%)`;
                        },
                    },
                },
            },
            onHover: (_event, elements) => {
                canvas.style.cursor = elements.length ? 'pointer' : 'default';
            },
            onClick: (_event, elements) => {
                if (elements.length) {
                    window.location.assign(rows[elements[0].index].querySelector('[data-chart-link]').href);
                }
            },
        },
    });

    rows.forEach((row, index) => {
        const button = row.querySelector('[data-chart-toggle]');
        button.hidden = false;
        row.querySelector('[data-chart-fallback]').hidden = true;
        row.querySelector('.admin-report-swatch').style.backgroundColor = colors[row.dataset.chartStatus];
        button.addEventListener('click', () => {
            chart.toggleDataVisibility(index);
            button.setAttribute('aria-pressed', String(chart.getDataVisibility(index)));
            chart.update();
        });
    });
    section.querySelector('[data-chart-help]').hidden = false;
});
