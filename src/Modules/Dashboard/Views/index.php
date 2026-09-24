<?php
/**
 * @var \OverDark\Core\View\Template $this
 * @var \OverDark\Modules\Dashboard\Application\PainelDashboard $painel
 */

use OverDark\Shared\Domain\PontoMensal;

$this->layout('layouts/app', [
    'title' => 'Dashboard',
    'nav' => 'dashboard',
    'styles' => ['pages/dashboard.css'],
    'scripts' => ['pages/dashboard.js'],
    'charts' => true,
]);

$indicadores = $painel->indicadores;
$mes = $indicadores->competencia->nomeMes();
$cards = [
    "Receitas ($mes)" => $indicadores->receitas,
    "Gastos ($mes)" => $indicadores->gastos,
    "Saldo ($mes)" => $indicadores->saldo(),
    "Cartão de crédito ($mes)" => $indicadores->cartao,
];
?>
<main class="page-shell">
    <section class="page-intro">
        <p class="eyebrow">Visão geral</p>
        <h1>Dashboard</h1>
        <p class="page-description">Acompanhe receitas, gastos, saldo e a evolução do seu dinheiro em um único painel.</p>
    </section>

    <section class="row g-4 summary-grid">
<?php foreach ($cards as $rotulo => $valor): ?>
        <div class="col-lg-3 col-md-6">
            <div class="card summary-card">
                <small><?= $this->e($rotulo) ?></small>
                <h5 class="fw-bold"><?= $this->money($valor) ?></h5>
            </div>
        </div>
<?php endforeach; ?>
    </section>

    <section class="content-grid">
        <article class="card panel chart-panel">
            <h6>Evolução do saldo</h6>
            <canvas id="financeChart" data-chart="<?= $this->json(PontoMensal::paraGrafico($painel->evolucaoSaldo)) ?>"></canvas>
        </article>

        <article class="card panel summary-panel">
            <h6>Saldo mensal de <?= (int) $painel->ano ?></h6>

            <ul class="list-group custom-list">
<?php foreach ($painel->resumoMensal as $ponto): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?= $this->e($ponto->rotulo) ?></span>
                    <span class="<?= $ponto->valor->isNegative() ? 'is-negative' : '' ?>"><?= $this->money($ponto->valor) ?></span>
                </li>
<?php endforeach; ?>
            </ul>
        </article>
    </section>
</main>
