/**
 * Máscara de moeda (pt-BR) para campos de valor: aceita somente dígitos e
 * preenche da direita para a esquerda, como em apps de banco.
 *
 *   <input name="valor" inputmode="numeric" data-mask="money">
 *
 *   digita 3 → "0,03"   ·   3999 → "39,99"   ·   399999 → "3.999,99"
 *
 * Letras e símbolos são ignorados (inclusive ao colar). O valor enviado fica
 * no formato "1.234,56", aceito pelo MoneyParser do backend.
 */

const MAX_DIGITOS = 13; // até R$ 99.999.999.999,99

export function formatarCentavos(digitos) {
    const limpos = digitos.replace(/\D/g, '').replace(/^0+/, '').slice(0, MAX_DIGITOS);

    if (limpos === '') {
        return '';
    }

    const centavos = limpos.padStart(3, '0');
    const inteiro = centavos.slice(0, -2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    return `${inteiro},${centavos.slice(-2)}`;
}

function aplicar(field) {
    const formatado = formatarCentavos(field.value);

    if (field.value !== formatado) {
        field.value = formatado;
    }

    // Numa máscara que cresce da direita para a esquerda, o cursor fica sempre no fim.
    const fim = field.value.length;
    field.setSelectionRange?.(fim, fim);
}

export function setupMoneyMask(field) {
    field.setAttribute('inputmode', 'numeric');
    field.setAttribute('autocomplete', 'off');

    // Bloqueia letras e símbolos já na digitação (atalhos como Ctrl+V continuam).
    field.addEventListener('beforeinput', (event) => {
        if (event.inputType === 'insertText' && event.data !== null && /\D/.test(event.data)) {
            event.preventDefault();
        }
    });

    field.addEventListener('input', () => aplicar(field));

    // Valor vindo do servidor (ex.: "1234.5" após erro de validação) já entra formatado.
    if (field.value.trim() !== '') {
        const [inteiro, decimal = ''] = field.value.replace(/[^\d.,]/g, '').split(/[.,](?=\d{1,2}$)/);
        field.value = formatarCentavos(inteiro.replace(/\D/g, '') + decimal.padEnd(2, '0'));
    }
}

document.querySelectorAll('input[data-mask="money"]').forEach(setupMoneyMask);
