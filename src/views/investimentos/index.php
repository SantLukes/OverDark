<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdark - Investimentos</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/investimentos.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="index.php?url=/movimentacoes" class="main-nav-link">Movimentações</a>
                <a href="index.php?url=/investimentos" class="main-nav-link active">Investimentos</a>
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
            <p class="eyebrow">Carteira patrimonial</p>
            <h1>Investimentos</h1>
        </div>

        <div class="page-actions">
            <button class="primary-button" type="button" id="openInvestmentModal">+ Novo investimento</button>
        </div>
    </section>

    <section class="summary-grid">
        <article class="summary-card">
            <small>Total investido</small>
            <h2>R$ 62.300</h2>
            <p class="card-note">Aportes distribuídos em 5 classes</p>
        </article>

        <article class="summary-card">
            <small>Valor atual</small>
            <h2>R$ 68.940</h2>
            <p class="card-note positive">Carteira atualizada com dados mockados</p>
        </article>

        <article class="summary-card">
            <small>Lucro / prejuízo</small>
            <h2 class="positive">+ R$ 6.640</h2>
            <p class="card-note positive">Resultado positivo acumulado</p>
        </article>

        <article class="summary-card">
            <small>Rentabilidade</small>
            <h2>+10,66%</h2>
            <p class="card-note">Baseada na valorização total da carteira</p>
        </article>
    </section>

    <section class="content-grid">
        <article class="panel chart-panel">
            <div class="panel-header">
                <div>
                    <p class="panel-kicker">Patrimônio ao longo do tempo</p>
                    <h3>Evolução do patrimônio</h3>
                </div>
            </div>

            <canvas id="investmentChart"></canvas>
        </article>

        <article class="panel type-panel">
            <div class="panel-header">
                <div>
                    <p class="panel-kicker">Composição visual</p>
                    <h3>Tipos de investimento</h3>
                </div>
            </div>

            <div class="type-list">
                <div class="type-row">
                    <span class="type-badge reserva">Reserva</span>
                    <strong>R$ 12.400</strong>
                </div>
                <div class="type-row">
                    <span class="type-badge renda-fixa">Renda fixa</span>
                    <strong>R$ 18.600</strong>
                </div>
                <div class="type-row">
                    <span class="type-badge fundos">Fundos</span>
                    <strong>R$ 14.800</strong>
                </div>
                <div class="type-row">
                    <span class="type-badge acoes">Ações</span>
                    <strong>R$ 15.900</strong>
                </div>
                <div class="type-row">
                    <span class="type-badge cripto">Cripto</span>
                    <strong>R$ 7.240</strong>
                </div>
            </div>
        </article>
    </section>

    <section class="panel table-panel">
        <div class="panel-header">
            <div>
                <p class="panel-kicker">Carteira atual</p>
                <h3>Lista de investimentos</h3>
            </div>
        </div>

        <div class="table-wrap">
            <table class="investment-table">
                <thead>
                    <tr>
                        <th>Nome do investimento</th>
                        <th>Tipo</th>
                        <th class="text-end">Valor investido</th>
                        <th class="text-end">Valor atual</th>
                        <th class="text-end">Resultado</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Reserva de emergência CDI</td>
                        <td><span class="type-badge reserva">Reserva</span></td>
                        <td class="text-end">R$ 10.000</td>
                        <td class="text-end">R$ 10.860</td>
                        <td class="text-end result positive">+ R$ 860</td>
                        <td class="text-end">
                            <button class="table-action">Editar</button>
                            <button class="table-action danger">Excluir</button>
                        </td>
                    </tr>
                    <tr>
                        <td>Tesouro Prefixado 2029</td>
                        <td><span class="type-badge renda-fixa">Renda fixa</span></td>
                        <td class="text-end">R$ 12.500</td>
                        <td class="text-end">R$ 13.420</td>
                        <td class="text-end result positive">+ R$ 920</td>
                        <td class="text-end">
                            <button class="table-action">Editar</button>
                            <button class="table-action danger">Excluir</button>
                        </td>
                    </tr>
                    <tr>
                        <td>Fundo multimercado Atlas</td>
                        <td><span class="type-badge fundos">Fundos</span></td>
                        <td class="text-end">R$ 9.800</td>
                        <td class="text-end">R$ 10.060</td>
                        <td class="text-end result positive">+ R$ 260</td>
                        <td class="text-end">
                            <button class="table-action">Editar</button>
                            <button class="table-action danger">Excluir</button>
                        </td>
                    </tr>
                    <tr>
                        <td>Carteira ações Brasil</td>
                        <td><span class="type-badge acoes">Ações</span></td>
                        <td class="text-end">R$ 18.000</td>
                        <td class="text-end">R$ 22.450</td>
                        <td class="text-end result positive">+ R$ 4.450</td>
                        <td class="text-end">
                            <button class="table-action">Editar</button>
                            <button class="table-action danger">Excluir</button>
                        </td>
                    </tr>
                    <tr>
                        <td>Bitcoin posição principal</td>
                        <td><span class="type-badge cripto">Cripto</span></td>
                        <td class="text-end">R$ 12.000</td>
                        <td class="text-end">R$ 12.150</td>
                        <td class="text-end result positive">+ R$ 150</td>
                        <td class="text-end">
                            <button class="table-action">Editar</button>
                            <button class="table-action danger">Excluir</button>
                        </td>
                    </tr>
                    <tr>
                        <td>Caixa de oportunidade</td>
                        <td><span class="type-badge reserva">Reserva</span></td>
                        <td class="text-end">R$ 5.000</td>
                        <td class="text-end">R$ 5.000</td>
                        <td class="text-end result neutral">R$ 0</td>
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

<div class="modal-backdrop" id="investmentModalBackdrop" hidden></div>

<div class="investment-modal" id="investmentModal" hidden>
    <div class="investment-modal-card">
        <div class="modal-header-row">
            <div>
                <p class="panel-kicker">Novo cadastro</p>
                <h3>Novo investimento</h3>
            </div>

            <button class="modal-close" type="button" id="closeInvestmentModal" aria-label="Fechar modal">×</button>
        </div>

        <form class="investment-form">
            <div class="form-grid">
                <label class="form-field form-field-wide">
                    <span>Nome do investimento</span>
                    <input type="text" placeholder="Ex: Tesouro IPCA, BTC, fundo de ações">
                </label>

                <label class="form-field">
                    <span>Tipo</span>
                    <select>
                        <option>Reserva</option>
                        <option>Renda fixa</option>
                        <option>Fundos</option>
                        <option>Ações</option>
                        <option>Cripto</option>
                    </select>
                </label>

                <label class="form-field">
                    <span>Data</span>
                    <input type="date" value="2026-03-22">
                </label>

                <label class="form-field">
                    <span>Valor investido</span>
                    <input type="text" placeholder="R$ 0,00">
                </label>

                <label class="form-field">
                    <span>Valor atual (opcional)</span>
                    <input type="text" placeholder="R$ 0,00">
                </label>
            </div>

            <div class="modal-actions">
                <button class="ghost-button" type="button" id="cancelInvestmentModal">Cancelar</button>
                <button class="primary-button" type="button">Salvar investimento</button>
            </div>
        </form>
    </div>
</div>

<script>
    const userMenu = document.querySelector('.user-menu');
    const userMenuToggle = document.querySelector('.user-menu-toggle');
    const userDropdown = document.querySelector('.user-dropdown');
    const investmentModal = document.getElementById('investmentModal');
    const investmentModalBackdrop = document.getElementById('investmentModalBackdrop');
    const openInvestmentModalButton = document.getElementById('openInvestmentModal');
    const closeInvestmentModalButton = document.getElementById('closeInvestmentModal');
    const cancelInvestmentModalButton = document.getElementById('cancelInvestmentModal');
    const chartElement = document.getElementById('investmentChart');

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

    function openInvestmentModal() {
        investmentModal.hidden = false;
        investmentModalBackdrop.hidden = false;

        requestAnimationFrame(() => {
            investmentModal.classList.add('is-open');
            investmentModalBackdrop.classList.add('is-open');
        });
    }

    function closeInvestmentModal() {
        investmentModal.classList.remove('is-open');
        investmentModalBackdrop.classList.remove('is-open');

        window.setTimeout(() => {
            investmentModal.hidden = true;
            investmentModalBackdrop.hidden = true;
        }, 180);
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

    openInvestmentModalButton.addEventListener('click', openInvestmentModal);
    closeInvestmentModalButton.addEventListener('click', closeInvestmentModal);
    cancelInvestmentModalButton.addEventListener('click', closeInvestmentModal);
    investmentModalBackdrop.addEventListener('click', closeInvestmentModal);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !investmentModal.hidden) {
            closeInvestmentModal();
        }
    });

    new Chart(chartElement, {
        type: 'line',
        data: {
            labels: ['Out', 'Nov', 'Dez', 'Jan', 'Fev', 'Mar'],
            datasets: [
                {
                    label: 'Patrimônio',
                    data: [48200, 51100, 54600, 57300, 62900, 68940],
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.12)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                    pointHoverRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 8,
                    right: 12,
                    bottom: 12,
                    left: 8
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#c4b5fd'
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#94a3b8',
                        padding: 12,
                        maxRotation: 0,
                        minRotation: 0
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.04)'
                    },
                    ticks: {
                        color: '#94a3b8',
                        callback(value) {
                            return 'R$ ' + Number(value).toLocaleString('pt-BR');
                        }
                    }
                }
            }
        }
    });
</script>

</body>
</html>
