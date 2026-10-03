import Chart from 'chart.js/auto';

const palette = {
    Pending: '#F59E0B',
    Ongoing: '#0EA5E9',
    Completed: '#10B981',
    Archived: '#CBD5E1',
};

const centerTextPlugin = {
    id: 'monitoringCenterText',
    afterDraw(chart) {
        const text = chart.canvas.dataset.centerText;
        if (!text) return;

        const { ctx, chartArea } = chart;
        if (!chartArea) return;

        ctx.save();
        ctx.font = '700 24px ui-sans-serif, system-ui, sans-serif';
        ctx.fillStyle = '#0f172a';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, (chartArea.left + chartArea.right) / 2, (chartArea.top + chartArea.bottom) / 2);
        ctx.restore();
    },
};

function readChartData(canvas) {
    return JSON.parse(canvas.dataset.chartData || '{}');
}

function initDoughnut(canvas) {
    const data = readChartData(canvas);
    const labels = data.labels || [];
    const values = data.values || [];
    const hasValues = values.some(value => Number(value) > 0);
    const total = values.reduce((sum, value) => sum + Number(value || 0), 0);

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: hasValues ? values : [1],
                backgroundColor: hasValues ? labels.map(label => palette[label] || '#CBD5E1') : ['#e2e8f0'],
                borderColor: '#FFFFFF',
                borderWidth: 2,
                hoverOffset: 4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '76%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: hasValues,
                    callbacks: {
                        label: context => {
                            const count = Number(context.raw || 0);
                            const percentage = total > 0 ? (count / total * 100).toFixed(1) : '0.0';
                            return `${context.label}: ${count} (${percentage}%)`;
                        },
                    },
                },
            },
        },
        plugins: [centerTextPlugin],
    });
}

function initOrganizationProgress(canvas) {
    const data = readChartData(canvas);

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'Completion',
                data: data.values || [],
                backgroundColor: '#10b981',
                borderRadius: 5,
                barThickness: 14,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { beginAtZero: true, max: 100, ticks: { callback: value => `${value}%` }, grid: { color: '#e2e8f0' } },
                y: { grid: { display: false } },
            },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: context => `${context.raw}% complete` } } },
        },
    });
}

function initializeMonitoringCharts() {
    document.querySelectorAll('canvas[data-chart-type="doughnut"]').forEach(initDoughnut);
    document.querySelectorAll('canvas[data-chart-type="organization-progress"]').forEach(initOrganizationProgress);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeMonitoringCharts, { once: true });
} else {
    initializeMonitoringCharts();
}