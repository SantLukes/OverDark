/**
 * Modal de confirmação para ações destrutivas (substitui o window.confirm).
 *
 *   <form method="post" action="/movimentacoes/excluir"
 *         data-confirm="A movimentação "Mercado" será removida."   ← mensagem (obrigatório)
 *         data-confirm-title="Excluir movimentação?"             ← título
 *         data-confirm-button="Excluir">                          ← texto do botão de confirmar
 *
 * O foco começa em "Cancelar" (a opção segura). Esc, "Cancelar" e clique
 * fora do card fecham sem enviar. Confirmar envia o formulário original.
 */

const TRANSITION_MS = 180;
let dialog = null;

function criarDialogo() {
    const backdrop = document.createElement('div');
    backdrop.className = 'app-modal-backdrop';
    backdrop.hidden = true;

    const modal = document.createElement('div');
    modal.className = 'app-modal confirm-modal';
    modal.setAttribute('role', 'alertdialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'confirmTitle');
    modal.setAttribute('aria-describedby', 'confirmMessage');
    modal.hidden = true;
    modal.innerHTML = `
        <div class="app-modal-card confirm-card">
            <div class="confirm-icon" aria-hidden="true">!</div>
            <h3 id="confirmTitle"></h3>
            <p id="confirmMessage"></p>
            <div class="modal-actions">
                <button type="button" class="ghost-button" data-confirm-cancel>Cancelar</button>
                <button type="button" class="danger-button" data-confirm-accept></button>
            </div>
        </div>`;

    document.body.append(backdrop, modal);

    return {
        backdrop,
        modal,
        card: modal.querySelector('.confirm-card'),
        title: modal.querySelector('#confirmTitle'),
        message: modal.querySelector('#confirmMessage'),
        cancel: modal.querySelector('[data-confirm-cancel]'),
        accept: modal.querySelector('[data-confirm-accept]'),
    };
}

function abrir(form, gatilho) {
    dialog ??= criarDialogo();
    const { backdrop, modal, card, title, message, cancel, accept } = dialog;

    title.textContent = form.dataset.confirmTitle || 'Tem certeza?';
    message.textContent = form.dataset.confirm;
    accept.textContent = form.dataset.confirmButton || 'Confirmar';
    accept.disabled = false;

    const fechar = () => {
        modal.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        document.removeEventListener('keydown', aoTeclar);
        window.setTimeout(() => {
            modal.hidden = true;
            backdrop.hidden = true;
            gatilho?.focus();
        }, TRANSITION_MS);
    };

    const aoTeclar = (event) => {
        if (event.key === 'Escape') {
            fechar();
        }
    };

    cancel.onclick = fechar;
    modal.onclick = (event) => {
        if (!card.contains(event.target)) {
            fechar();
        }
    };
    accept.onclick = () => {
        accept.disabled = true;
        accept.textContent = 'Excluindo...';
        form.submit(); // submit() nativo: não dispara o listener de novo
    };

    document.addEventListener('keydown', aoTeclar);
    modal.hidden = false;
    backdrop.hidden = false;

    requestAnimationFrame(() => {
        modal.classList.add('is-open');
        backdrop.classList.add('is-open');
        cancel.focus();
    });
}

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        abrir(form, event.submitter ?? form.querySelector('button[type="submit"]'));
    });
});
