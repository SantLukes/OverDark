/**
 * Gráfico de linha no tema do OverDark (Chart.js carregado via CDN no layout).
 *
 * Os dados vêm do servidor no atributo data-chart do <canvas>:
 *   { "labels": ["Jan", ...], "values": [1500, ...] }
 */
// Mesma paleta de base.css: linha em lavanda (cor da logo), eixos discretos.
const COLORS = {
    line: '#cbb6fa',
    point: '#0c0a10',
    fill: [177, 150, 240],
    legend: '#948ca3',
    ticks: '#6b6478',
    grid: 'rgba(255, 255, 255, 0.04)',
    tooltipBg: '#1c1824',
    tooltipBorder: 'rgba(177, 150, 240, 0.28)',
};

const currency = (value) => 'R$ ' + Number(value).toLocaleString('pt-BR');

/**
 * @param {HTMLCanvasElement|null} canvas
 * @param {object} options
 * @param {string} options.label           legenda da série
 * @param {number} [options.tension]       curvatura da linha
 * @param {number} [options.fillOpacity]   opacidade da área preenchida
 * @param {number} [options.pointRadius]   raio dos pontos (undefined = padrão do Chart.js)
 * @param {boolean} [options.currencyTicks] formata o eixo Y como moeda
 * @param {number} [options.yTickPadding]  espaço entre rótulos do eixo Y e o gráfico
 */
export function renderLineChart(canvas, {
    label,
    tension = 0.4,
    fillOpacity = 0.1,
    pointRadius,
    currencyTicks = false,
    yTickPadding,
}) {
    if (!canvas || typeof window.Chart === 'undefined') {
        return null;
    }

    const { labels, values } = JSON.parse(canvas.dataset.chart ?? '{"labels":[],"values":[]}');

    const [r, g, b] = COLORS.fill;
    const dataset = {
        label,
        data: values,
        borderColor: COLORS.line,
        borderWidth: 2,
        pointBackgroundColor: COLORS.point,
        pointBorderColor: COLORS.line,
        pointBorderWidth: 2,
        // Degradê: a área some em direção ao eixo, reforçando o fundo escuro.
        backgroundColor: (context) => {
            const { ctx, chartArea } = context.chart;
            if (!chartArea) {
                return `rgba(${r}, ${g}, ${b}, ${fillOpacity})`;
            }
            const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            gradient.addColorStop(0, `rgba(${r}, ${g}, ${b}, ${fillOpacity * 2.2})`);
            gradient.addColorStop(1, `rgba(${r}, ${g}, ${b}, 0)`);
            return gradient;
        },
        tension,
        fill: true,
    };

    if (pointRadius !== undefined) {
        dataset.pointRadius = pointRadius;
        dataset.pointHoverRadius = pointRadius + 1;
    }

    return new window.Chart(canvas, {
        type: 'line',
        data: { labels, datasets: [dataset] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 8, right: 12, bottom: 12, left: 8 } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: COLORS.tooltipBg,
                    borderColor: COLORS.tooltipBorder,
                    borderWidth: 1,
                    titleColor: '#ece8f2',
                    bodyColor: '#ece8f2',
                    padding: 10,
                    displayColors: false,
                    callbacks: { label: (item) => `${label}: ${currency(item.parsed.y)}` },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: COLORS.ticks, padding: 12, maxRotation: 0, minRotation: 0 },
                },
                y: {
                    grid: { color: COLORS.grid },
                    ticks: {
                        color: COLORS.ticks,
                        ...(yTickPadding !== undefined ? { padding: yTickPadding } : {}),
                        ...(currencyTicks ? { callback: currency } : {}),
                    },
                },
            },
        },
    });
}
