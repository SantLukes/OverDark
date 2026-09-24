/**
 * Modais controlados por data-attributes.
 *
 * Marcação esperada:
 *   <button data-modal-open="meuModal">            abre
 *   <div data-modal-backdrop="meuModal" hidden>    fundo escurecido
 *   <div class="app-modal" id="meuModal" hidden>   o modal
 *     ... <button data-modal-close>                fecha
 *
 * Também fecha com Esc e com clique fora do card.
 * Com o atributo data-modal-autoopen, o modal já abre ao carregar a página
 * (ex.: formulário que voltou do servidor com erros de validação).
 */
const TRANSITION_MS = 180;

function setup(modal) {
    const backdrop = document.querySelector(`[data-modal-backdrop="${modal.id}"]`);
    const card = modal.querySelector('.app-modal-card');
    let lastTrigger = null;

    const open = (trigger) => {
        lastTrigger = trigger ?? null;
        modal.hidden = false;
        if (backdrop) backdrop.hidden = false;

        requestAnimationFrame(() => {
            modal.classList.add('is-open');
            backdrop?.classList.add('is-open');
            modal.querySelector('input, select, textarea')?.focus();
        });
    };

    const close = () => {
        modal.classList.remove('is-open');
        backdrop?.classList.remove('is-open');

        window.setTimeout(() => {
            modal.hidden = true;
            if (backdrop) backdrop.hidden = true;
            lastTrigger?.focus();
        }, TRANSITION_MS);
    };

    document.querySelectorAll(`[data-modal-open="${modal.id}"]`).forEach((trigger) => {
        trigger.addEventListener('click', () => open(trigger));
    });

    modal.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', close);
    });

    // O modal cobre a tela inteira; clique fora do card = clique "no fundo".
    modal.addEventListener('click', (event) => {
        if (!card.contains(event.target)) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            close();
        }
    });

    if (modal.hasAttribute('data-modal-autoopen')) {
        open();
    }
}

document.querySelectorAll('.app-modal[id]').forEach(setup);
