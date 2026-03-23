<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Overdark - Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/dashboard.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<header class="dashboard-header">
    <div class="header-bar">
        <div class="header-left">
            <div class="brand">
                <img src="/assets/logo2.png" alt="Overdark">
                <span>OverDark</span>
            </div>

            <nav class="main-nav" aria-label="Navegacao principal">
                <a href="index.php?url=/dashboard" class="main-nav-link active">Dashboard</a>
                <a href="index.php?url=/movimentacoes" class="main-nav-link">Movimentações</a>
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

<main class="dashboard-shell">
    <section class="page-intro">
        <p class="eyebrow">Visao geral</p>
        <h1>Dashboard</h1>
        <p class="page-description">Acompanhe receitas, gastos, saldo e a evolução do seu dinheiro em um único painel.</p>
    </section>

    <section class="row g-4 summary-grid">
        <div class="col-lg-3 col-md-6">
            <div class="card summary-card">
                <small>Receitas (Atual mês)</small>
                <h5 class="fw-bold">R$ 3.200</h5>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card summary-card">
                <small>Gastos (Atual mês)</small>
                <h5 class="fw-bold">R$ 1.850</h5>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card summary-card">
                <small>Saldo (Atual mês)</small>
                <h5 class="fw-bold">R$ 1.350</h5>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card summary-card">
                <small>Investimentos (Total)</small>
                <h5 class="fw-bold">R$ 5.000</h5>
            </div>
        </div>
    </section>

    <section class="content-grid">
        <article class="card panel chart-panel">
            <h6>Evolucao financeira</h6>
            <canvas id="financeChart"></canvas>
        </article>

        <article class="card panel summary-panel">
            <h6>Resumo mensal</h6>

            <ul class="list-group custom-list">
                <li class="list-group-item d-flex justify-content-between">
                    <span>Janeiro</span>
                    <span>R$ 1.500</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Fevereiro</span>
                    <span>R$ 1.600</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Marco</span>
                    <span>R$ 2.000</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Abril</span>
                    <span>R$ 2.000</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Maio</span>
                    <span>R$ 2.000</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Junho</span>
                    <span>R$ 2.000</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Julho</span>
                    <span>R$ 2.150</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Agosto</span>
                    <span>R$ 2.300</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Setembro</span>
                    <span>R$ 2.280</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Outubro</span>
                    <span>R$ 2.420</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Novembro</span>
                    <span>R$ 2.510</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Dezembro</span>
                    <span>R$ 2.680</span>
                </li>
            </ul>
        </article>
    </section>
</main>

<script>
    const ctx = document.getElementById('financeChart');
    const userMenu = document.querySelector('.user-menu');
    const userMenuToggle = document.querySelector('.user-menu-toggle');
    const userDropdown = document.querySelector('.user-dropdown');

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

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
            datasets: [
                {
                    label: 'Saldo',
                    data: [1500, 1600, 2000, 1800, 2200, 2500],
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: true
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
                        color: '#9ca3af',
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
                        color: '#9ca3af',
                        padding: 10
                    }
                }
            }
        }
    });
</script>

</body>
</html>
