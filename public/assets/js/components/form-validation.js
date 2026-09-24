/**
 * Validação de formulários no navegador, declarativa por atributos.
 *
 * O servidor continua sendo a fonte da verdade (valida tudo de novo). Aqui o
 * objetivo é UX: resposta imediata, com as MESMAS mensagens do backend.
 *
 * Marcação:
 *   <form data-validate [data-error-class="field-error"] [data-no-submit]>
 *     <input name="valor"
 *            required                          campo obrigatório
 *            data-rule="money|email|date"      regra de formato
 *            maxlength="160"                   tamanho máximo
 *            data-msg-required="..."           mensagem se vazio
 *            data-msg-invalid="..."            mensagem se formato inválido
 *            data-msg-min="...">               mensagem se valor ≤ 0 (money)
 *
 * Comportamento:
 *  - No envio: valida tudo, mostra os erros ao lado de cada campo, foca o
 *    primeiro inválido e bloqueia o envio. Se tudo ok, trava o botão
 *    ("Salvando...") para evitar cadastro duplicado por duplo clique.
 *  - Ao sair de um campo preenchido: valida aquele campo.
 *  - Depois que um campo mostrou erro: revalida enquanto o usuário digita.
 *  - Campos de dinheiro são formatados ao sair do campo: "1234.5" → "1.234,50".
 *  - Campos dentro de um bloco [hidden] (ex.: parcelas fora do cartão) são ignorados.
 *  - data-no-submit: valida, mas não envia; dispara o evento "form:valid".
 */

const DEFAULTS = {
    required: 'Preencha este campo.',
    email: 'Informe um e-mail válido.',
    date: 'Informe uma data válida.',
    money: 'Informe um valor válido (ex.: 1.234,56).',
    min: 'O valor deve ser maior que zero.',
    maxlength: (max) => `Use no máximo ${max} caracteres.`,
};

/**
 * Mesmas regras do MoneyParser (PHP): "1.234,56", "1234,56", "R$ 1.234,56",
 * "1234.56", "1.234" (milhar). Retorna centavos ou null se inválido.
 */
export function parseMoney(input) {
    let valor = String(input).replace(/R\$|\s| /g, '').trim();

    if (valor === '') {
        return null;
    }

    if (valor.includes(',')) {
        if (!/^\d{1,3}(\.\d{3})*,\d{1,2}$|^\d+,\d{1,2}$/.test(valor)) {
            return null;
        }
        valor = valor.replace(/\./g, '').replace(',', '.');
    } else if (/^\d{1,3}(\.\d{3})+$/.test(valor)) {
        valor = valor.replace(/\./g, '');
    }

    if (!/^\d+(\.\d{1,2})?$/.test(valor)) {
        return null;
    }

    const [inteiro, decimal = ''] = valor.split('.');

    return Number(inteiro) * 100 + Number(decimal.padEnd(2, '0'));
}

export function formatMoney(centavos) {
    return (centavos / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function isValidDate(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return false;
    }
    const [ano, mes, dia] = value.split('-').map(Number);
    const data = new Date(Date.UTC(ano, mes - 1, dia));

    return data.getUTCFullYear() === ano && data.getUTCMonth() === mes - 1 && data.getUTCDate() === dia;
}

const isEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value);

/** Campo participa da validação? (visível e habilitado) */
function isActive(field) {
    return !field.disabled && !field.closest('[hidden]') && field.type !== 'hidden';
}

/**
 * @returns {string|null} mensagem de erro ou null se válido
 */
export function validateField(field) {
    if (!isActive(field)) {
        return null;
    }

    const value = field.value.trim();
    const msg = (key, fallback) => field.dataset[key] || fallback;

    if (value === '') {
        return field.required ? msg('msgRequired', DEFAULTS.required) : null;
    }

    const max = Number(field.getAttribute('maxlength'));
    if (max > 0 && value.length > max) {
        return msg('msgMaxlength', DEFAULTS.maxlength(max));
    }

    switch (field.dataset.rule) {
        case 'email':
            return isEmail(value) ? null : msg('msgInvalid', DEFAULTS.email);
        case 'date':
            return isValidDate(value) ? null : msg('msgInvalid', DEFAULTS.date);
        case 'money': {
            const centavos = parseMoney(value);
            if (centavos === null) {
                return msg('msgInvalid', DEFAULTS.money);
            }
            return centavos > 0 ? null : msg('msgMin', DEFAULTS.min);
        }
        default:
            return null;
    }
}

function errorElementFor(form, field) {
    return form.querySelector(`[data-error-for="${CSS.escape(field.name)}"]`);
}

function showError(form, field, message) {
    const wrapper = field.closest('.form-field, .login-field') ?? field.parentElement;
    let error = errorElementFor(form, field);

    if (!message) {
        field.removeAttribute('aria-invalid');
        field.classList.remove('is-invalid');
        error?.remove();
        return;
    }

    if (!error) {
        error = document.createElement('small');
        error.className = form.dataset.errorClass || 'field-error';
        error.dataset.errorFor = field.name;
        error.id = `erro-${form.id || 'form'}-${field.name}`;
        wrapper.appendChild(error);
    }

    error.textContent = message;
    field.setAttribute('aria-invalid', 'true');
    field.setAttribute('aria-describedby', error.id);
    field.classList.add('is-invalid');
}

function fieldsOf(form) {
    return [...form.querySelectorAll('input[name], select[name], textarea[name]')]
        .filter((field) => field.type !== 'hidden' && field.type !== 'submit');
}

function formatMoneyField(field) {
    if (field.dataset.rule !== 'money') {
        return;
    }
    const centavos = parseMoney(field.value);
    if (centavos !== null && centavos > 0) {
        field.value = formatMoney(centavos);
    }
}

export function setupForm(form) {
    const touched = new WeakSet();

    const check = (field) => {
        const message = validateField(field);
        showError(form, field, message);
        return message === null;
    };

    fieldsOf(form).forEach((field) => {
        // Erros vindos do servidor já contam como "tocados": revalidam ao digitar.
        if (field.getAttribute('aria-invalid') === 'true' || field.classList.contains('is-invalid')) {
            touched.add(field);
        }

        field.addEventListener('blur', () => {
            if (field.value.trim() !== '') {
                touched.add(field);
                if (check(field)) {
                    formatMoneyField(field);
                }
            }
        });

        const revalidate = () => {
            if (touched.has(field)) {
                check(field);
            }
        };
        field.addEventListener('input', revalidate);
        field.addEventListener('change', revalidate);
    });

    form.addEventListener('submit', (event) => {
        const fields = fieldsOf(form);
        const invalid = fields.filter((field) => !check(field));

        fields.forEach((field) => touched.add(field));

        if (invalid.length > 0) {
            event.preventDefault();
            invalid[0].focus();
            return;
        }

        fields.forEach(formatMoneyField);

        if (form.hasAttribute('data-no-submit')) {
            event.preventDefault();
            form.dispatchEvent(new CustomEvent('form:valid', { bubbles: true }));
            return;
        }

        // Evita envio duplicado (duplo clique = duas movimentações).
        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.dataset.originalText = button.textContent;
            button.textContent = button.dataset.loadingText || 'Salvando...';
        });
    });

    // Voltar pelo histórico (bfcache) não pode deixar o botão travado.
    window.addEventListener('pageshow', () => {
        form.querySelectorAll('button[type="submit"][disabled]').forEach((button) => {
            button.disabled = false;
            button.textContent = button.dataset.originalText ?? button.textContent;
        });
    });
}

document.querySelectorAll('form[data-validate]').forEach(setupForm);
