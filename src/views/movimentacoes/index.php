<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdark - Movimentações</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/movimentacoes.css">
</head>
<body>

<header class="page-header">
    <div class="header-bar">
        <div class="header-left">
            <div class="brand">
                <img src="/assets/logo2.png" alt="Overdark">
                <span>OverDark</span>
            </div>

            <nav class="main-nav" aria-label="Navegacao principal">
                <a href="index.php?url=/dashboard" class="main-nav-link">Dashboard</a>
                <a href="index.php?url=/movimentacoes" class="main-nav-link active">Movimentações</a>
                <a href="index.php?url=/investimentos" class="main-nav-link">Investimentos</a>
            </nav>
        </div>

        <div class="user-menu">
            <button class="user-menu-toggle" type="button" aria-expanded="false" aria-label="Abrir menu do usuario">
                <span class="user-avatar">L</span>
            </button>

            <div class="user-dropdown" hidden>
                <a href="#" class="user-dropdown-item">Configuracoes</a>
                <a href="#" class="user-dropdown-item">Sair</a>
            </div>
        </div>
    </div>
</header>

<main class="page-shell">
    <section class="page-toolbar">
        <div class="page-heading">
            <p class="eyebrow">Controle financeiro</p>
            <h1>Movimentações</h1>
        </div>

        <div class="page-actions">
            <label class="month-filter">
                <span class="visually-hidden">Selecionar mês</span>
                <select>
                    <option>Março 2026</option>
                    <option>Fevereiro 2026</option>
                    <option>Janeiro 2026</option>
                </select>
            </label>

            <button class="primary-button" type="button" id="openMovementModal">+ Nova movimentação</button>
        </div>
    </section>

    <section class="summary-grid">
        <article class="summary-card">
            <small>Receita total</small>
            <h2>R$ 8.450</h2>
            <p class="card-note positive">+12,4% em relação ao mês anterior</p>
        </article>

        <article class="summary-card">
            <small>Gastos totais</small>
            <h2>R$ 5.920</h2>
            <p class="card-note">48 lançamentos registrados</p>
        </article>

        <article class="summary-card">
            <small>Saldo do mês</small>
            <h2>R$ 2.530</h2>
            <p class="card-note positive">Fechamento acima da meta</p>
        </article>

        <article class="summary-card status-card">
            <small>Status</small>
            <div class="status-row positive">
                <span class="status-icon">↑</span>
                <div>
                    <h2>Positivo</h2>
                    <p class="card-note">Fluxo saudável neste mês</p>
                </div>
            </div>
        </article>
    </section>

    <section class="panel table-panel">
        <div class="panel-header">
            <div>
                <p class="panel-kicker">Lançamentos do período</p>
                <h3>Lista de movimentações</h3>
            </div>
        </div>

        <div class="table-wrap">
            <table class="movement-table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th class="text-end">Valor</th>
                        <th>Data</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="type-badge receita">Receita</span></td>
                        <td>Salário mensal</td>
                        <td class="text-end value positive">R$ 5.200</td>
                        <td>05/03/2026</td>
                        <td class="text-end">
                            <button class="table-action">Editar</button>
                            <button class="table-action danger">Excluir</button>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="type-badge gasto">Gasto</span></td>
                        <td>Supermercado</td>
                        <td class="text-end value negative">R$ 420</td>
                        <td>08/03/2026</td>
                        <td class="text-end">
                            <button class="table-action">Editar</button>
                            <button class="table-action danger">Excluir</button>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="type-badge cartao">Cartão</span></td>
                        <td>Notebook parcelado</td>
                        <td class="text-end value negative">R$ 399</td>
                        <td>11/03/2026</td>
                        <td class="text-end">
                            <button class="table-action">Editar</button>
                            <button class="table-action danger">Excluir</button>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="type-badge gasto">Gasto</span></td>
                        <td>Academia</td>
                        <td class="text-end value negative">R$ 119</td>
                        <td>13/03/2026</td>
                        <td class="text-end">
                            <button class="table-action">Editar</button>
                            <button class="table-action danger">Excluir</button>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="type-badge receita">Receita</span></td>
                        <td>Freelance landing page</td>
                        <td class="text-end value positive">R$ 1.800</td>
                        <td>18/03/2026</td>
                        <td class="text-end">
                            <button class="table-action">Editar</button>
                            <button class="table-action danger">Excluir</button>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="type-badge cartao">Cartão</span></td>
                        <td>Passagem aérea</td>
                        <td class="text-end value negative">R$ 287</td>
                        <td>22/03/2026</td>
                        <td class="text-end">
                            <button class="table-action">Editar</button>
                            <button class="table-action danger">Excluir</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</main>

<div class="modal-backdrop" id="movementModalBackdrop" hidden></div>

<div class="movement-modal" id="movementModal" hidden>
    <div class="movement-modal-card">
        <div class="modal-header-row">
            <div>
                <p class="panel-kicker">Novo lançamento</p>
                <h3>Nova movimentação</h3>
            </div>

            <button class="modal-close" type="button" id="closeMovementModal" aria-label="Fechar modal">×</button>
        </div>

        <form class="movement-form">
            <div class="form-grid">
                <label class="form-field">
                    <span>Tipo</span>
                    <select id="movementType">
                        <option>Receita</option>
                        <option>Gasto</option>
                        <option>Cartão de crédito</option>
                    </select>
                </label>

                <label class="form-field">
                    <span>Data</span>
                    <input type="date" value="2026-03-22">
                </label>

                <label class="form-field form-field-wide">
                    <span>Descrição</span>
                    <input type="text" placeholder="Ex: Mercado, salário, parcela do notebook">
                </label>

                <label class="form-field">
                    <span>Valor</span>
                    <input type="text" placeholder="R$ 0,00">
                </label>
            </div>

            <div class="installments-grid" id="installmentsFields" hidden>
                <label class="form-field">
                    <span>Número de parcelas</span>
                    <select>
                        <option>2 parcelas</option>
                        <option>3 parcelas</option>
                        <option>4 parcelas</option>
                        <option>6 parcelas</option>
                        <option>10 parcelas</option>
                    </select>
                </label>

                <label class="form-field">
                    <span>Valor por parcela</span>
                    <input type="text" placeholder="Opcional">
                </label>
            </div>

            <div class="modal-actions">
                <button class="ghost-button" type="button" id="cancelMovementModal">Cancelar</button>
                <button class="primary-button" type="button">Salvar movimentação</button>
            </div>
        </form>
    </div>
</div>

<script>
    const userMenu = document.querySelector('.user-menu');
    const userMenuToggle = document.querySelector('.user-menu-toggle');
    const userDropdown = document.querySelector('.user-dropdown');
    const movementModal = document.getElementById('movementModal');
    const movementModalBackdrop = document.getElementById('movementModalBackdrop');
    const openMovementModalButton = document.getElementById('openMovementModal');
    const closeMovementModalButton = document.getElementById('closeMovementModal');
    const cancelMovementModalButton = document.getElementById('cancelMovementModal');
    const movementType = document.getElementById('movementType');
    const installmentsFields = document.getElementById('installmentsFields');

    function closeUserMenu() {
        userMenu.classList.remove('is-open');
        userMenuToggle.setAttribute('aria-expanded', 'false');
        userDropdown.hidden = true;
    }

    function openUserMenu() {
        userDropdown.hidden = false;

        requestAnimationFrame(() => {
            userMenu.classList.add('is-open');
            userMenuToggle.setAttribute('aria-expanded', 'true');
        });
    }

    function openMovementModal() {
        movementModal.hidden = false;
        movementModalBackdrop.hidden = false;

        requestAnimationFrame(() => {
            movementModal.classList.add('is-open');
            movementModalBackdrop.classList.add('is-open');
        });
    }

    function closeMovementModal() {
        movementModal.classList.remove('is-open');
        movementModalBackdrop.classList.remove('is-open');

        window.setTimeout(() => {
            movementModal.hidden = true;
            movementModalBackdrop.hidden = true;
        }, 180);
    }

    function syncInstallmentsFields() {
        installmentsFields.hidden = movementType.value !== 'Cartão de crédito';
    }

    userMenuToggle.addEventListener('click', (event) => {
        event.stopPropagation();

        if (userMenu.classList.contains('is-open')) {
            closeUserMenu();
            return;
        }

        openUserMenu();
    });

    document.addEventListener('click', (event) => {
        if (!userMenu.contains(event.target)) {
            closeUserMenu();
        }
    });

    openMovementModalButton.addEventListener('click', openMovementModal);
    closeMovementModalButton.addEventListener('click', closeMovementModal);
    cancelMovementModalButton.addEventListener('click', closeMovementModal);
    movementModalBackdrop.addEventListener('click', closeMovementModal);
    movementType.addEventListener('change', syncInstallmentsFields);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !movementModal.hidden) {
            closeMovementModal();
        }
    });

    syncInstallmentsFields();
</script>

</body>
</html>
