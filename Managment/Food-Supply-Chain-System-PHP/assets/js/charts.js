// Dashboard chart rendering for SahanFresh FSCMS

function renderBarChart(containerId, data, options = {}) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const chartBody = container.querySelector('.chart-body');
    if (!chartBody) return;
    
    const maxValue = Math.max(...data.map(d => d.value), 1);
    
    chartBody.innerHTML = '';
    
    data.forEach(item => {
        const barHeight = ((item.value / maxValue) * 100);
        const barDiv = document.createElement('div');
        barDiv.className = 'chart-bar-container';
        barDiv.innerHTML = `
            <div class="chart-bar" style="height: 0%;" data-target="${barHeight}" title="${item.label}: ${item.value}">
                <span class="chart-tooltip">${item.value}</span>
            </div>
            <span class="chart-label">${item.label}</span>
        `;
        chartBody.appendChild(barDiv);
    });
    
    // Animate bars in
    requestAnimationFrame(() => {
        const bars = chartBody.querySelectorAll('.chart-bar');
        bars.forEach((bar) => {
            const target = bar.getAttribute('data-target');
            setTimeout(() => {
                bar.style.height = target + '%';
            }, 100);
        });
    });
}

// Initialize charts on page load
document.addEventListener('DOMContentLoaded', () => {
    // Sales chart - data injected via PHP into window.chartData
    if (typeof window.salesChartData !== 'undefined') {
        renderBarChart('sales-chart', window.salesChartData);
    }
    
    // Category chart
    if (typeof window.categoryChartData !== 'undefined') {
        renderBarChart('category-chart', window.categoryChartData);
    }
});
