/**
 * Página de erro: registra o código no Console (para quem abre o DevTools)
 * e permite copiá-lo para enviar ao suporte.
 */
const page = document.querySelector('[data-error]');

if (page) {
    const { status, correlationId, rota } = page.dataset;

    // O mesmo código está no header X-Correlation-ID desta resposta (aba Network).
    console.error(
        `%c OverDark %c Erro ${status} em ${rota}\n` +
        `correlation_id: ${correlationId}\n` +
        'Informe este código ao suporte. Ele também está no header X-Correlation-ID (aba Network).',
        'background:#b196f0;color:#0c0a10;border-radius:4px;padding:2px 6px;font-weight:bold',
        'color:inherit',
    );

    document.querySelectorAll('[data-copy-target]').forEach((button) => {
        button.addEventListener('click', async () => {
            const texto = document.getElementById(button.dataset.copyTarget)?.textContent?.trim() ?? '';
            try {
                await navigator.clipboard.writeText(texto);
                button.textContent = 'Copiado!';
            } catch {
                window.prompt('Copie o código:', texto);
            }
            window.setTimeout(() => { button.textContent = 'Copiar'; }, 2000);
        });
    });

    document.querySelectorAll('[data-history-back]').forEach((button) => {
        button.addEventListener('click', () => {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '/dashboard';
            }
        });
    });
}
