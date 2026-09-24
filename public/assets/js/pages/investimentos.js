import '../components/modal.js';
import '../components/money-mask.js';
import '../components/form-validation.js';
import { renderLineChart } from '../components/line-chart.js';

renderLineChart(document.getElementById('investmentChart'), {
    label: 'Patrimônio',
    tension: 0.35,
    fillOpacity: 0.12,
    pointRadius: 4,
    currencyTicks: true,
});

// Cadastro de investimentos ainda não tem backend: após validar, apenas avisa.
document.getElementById('investmentForm')?.addEventListener('form:valid', (event) => {
    event.currentTarget.querySelector('[data-demo-notice]')?.removeAttribute('hidden');
});
