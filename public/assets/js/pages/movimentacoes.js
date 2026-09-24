import '../components/modal.js';
import '../components/confirm.js';
import '../components/money-mask.js';
import '../components/form-validation.js';

// Troca de competência no filtro recarrega a página com ?competencia=AAAA-MM.
document.querySelectorAll('select[data-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => select.form?.submit());
});

// Campos de parcelamento (e dicas marcadas com data-installments-only) só
// aparecem para tipos parceláveis — hoje, cartão de crédito.
document.querySelectorAll('select[data-installments-toggle]').forEach((select) => {
    const fields = document.getElementById(select.dataset.installmentsToggle);
    const hints = select.form?.querySelectorAll('[data-installments-only]') ?? [];

    const sync = () => {
        const parcelavel = select.selectedOptions[0]?.hasAttribute('data-parcelavel') ?? false;
        fields.hidden = !parcelavel;
        hints.forEach((hint) => { hint.hidden = !parcelavel; });
    };

    select.addEventListener('change', sync);
    sync();
});
