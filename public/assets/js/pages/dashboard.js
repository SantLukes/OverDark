import { renderLineChart } from '../components/line-chart.js';

renderLineChart(document.getElementById('financeChart'), {
    label: 'Saldo',
    tension: 0.4,
    fillOpacity: 0.1,
    yTickPadding: 10,
    currencyTicks: true,
});
