<?php
/**
 * @var \OverDark\Core\View\Template $this
 * @var \OverDark\Modules\Investimentos\Application\PainelInvestimentos $painel
 */

use OverDark\Shared\Domain\Money;
use OverDark\Shared\Domain\PontoMensal;

$this->layout('layouts/app', [
    'title' => 'Investimentos',
    'nav' => 'investimentos',
    'styles' => ['components.css', 'pages/investimentos.css'],
    'scripts' => ['pages/investimentos.js'],
    'charts' => true,
]);

$resumo = $painel->resumo;
$lucro = $resumo->lucro();

$sinal = static fn (Money $valor): string => match (true) {
    $valor->isPositive() => 'positive',
    $valor->isNegative() => 'negative',
    default => 'neutral',
};
?>
<main class="page-shell">
    <section class="page-toolbar">
        <div class="page-heading">
            <p class="eyebrow">Carteira patrimonial</p>
            <h1>Investimentos</h1>
        </div>

        <div class="page-actions">
            <button class="primary-button" type="button" data-modal-open="investmentModal">+ Novo investimento</button>
        </div>
    </section>

    <section class="summary-grid">
        <article class="summary-card">
            <small>Total investido</small>
            <h2><?= $this->money($resumo->totalInvestido) ?></h2>
            <p class="card-note">Aportes distribuídos em <?= $resumo->quantidadeClasses() ?> classes</p>
        </article>

        <article class="summary-card">
            <small>Valor atual</small>
            <h2><?= $this->money($resumo->valorAtual()) ?></h2>
            <p class="card-note positive">Carteira atualizada com dados mockados</p>
        </article>

        <article class="summary-card">
            <small>Lucro / prejuízo</small>
            <h2 class="<?= $sinal($lucro) ?>"><?= $this->signedMoney($lucro) ?></h2>
<?php if ($lucro->isNegative()): ?>
            <p class="card-note negative">Resultado negativo acumulado</p>
<?php else: ?>
            <p class="card-note positive">Resultado positivo acumulado</p>
<?php endif; ?>
        </article>

        <article class="summary-card">
            <small>Rentabilidade</small>
            <h2><?= $this->percent($resumo->rentabilidade(), signed: true) ?></h2>
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

            <canvas id="investmentChart" data-chart="<?= $this->json(PontoMensal::paraGrafico($painel->evolucao)) ?>"></canvas>
        </article>

        <article class="panel type-panel">
            <div class="panel-header">
                <div>
                    <p class="panel-kicker">Composição visual</p>
                    <h3>Tipos de investimento</h3>
                </div>
            </div>

            <div class="type-list">
<?php foreach ($resumo->composicao as $alocacao): ?>
                <div class="type-row">
                    <span class="type-badge <?= $this->e($alocacao->tipo->value) ?>"><?= $this->e($alocacao->tipo->rotulo()) ?></span>
                    <strong><?= $this->money($alocacao->valor) ?></strong>
                </div>
<?php endforeach; ?>
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
            <table class="data-table">
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
<?php foreach ($painel->investimentos as $investimento): ?>
                    <tr>
                        <td><?= $this->e($investimento->nome) ?></td>
                        <td><span class="type-badge <?= $this->e($investimento->tipo->value) ?>"><?= $this->e($investimento->tipo->rotulo()) ?></span></td>
                        <td class="text-end"><?= $this->money($investimento->valorInvestido) ?></td>
                        <td class="text-end"><?= $this->money($investimento->valorAtual) ?></td>
                        <td class="text-end amount <?= $sinal($investimento->resultado()) ?>"><?= $this->signedMoney($investimento->resultado()) ?></td>
                        <td class="text-end">
                            <button class="table-action" type="button">Editar</button>
                            <button class="table-action danger" type="button">Excluir</button>
                        </td>
                    </tr>
<?php endforeach; ?>
<?php if ($painel->investimentos === []): ?>
                    <tr>
                        <td colspan="6" class="empty-state">Nenhum investimento cadastrado.</td>
                    </tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<div class="app-modal-backdrop" data-modal-backdrop="investmentModal" hidden></div>

<div class="app-modal" id="investmentModal" role="dialog" aria-modal="true" aria-labelledby="investmentModalTitle" hidden>
    <div class="app-modal-card">
        <div class="modal-header-row">
            <div>
                <p class="panel-kicker">Novo cadastro</p>
                <h3 id="investmentModalTitle">Novo investimento</h3>
            </div>

            <button class="modal-close" type="button" aria-label="Fechar modal" data-modal-close>×</button>
        </div>

        <!-- data-no-submit: o cadastro de investimentos ainda não tem backend (versão demo). -->
        <form class="modal-form" id="investmentForm" novalidate data-validate data-no-submit>
            <div class="flash flash-info" data-demo-notice hidden>Dados válidos! O cadastro de investimentos chega na próxima versão.</div>

            <div class="form-grid">
                <label class="form-field form-field-wide">
                    <span>Nome do investimento</span>
                    <input type="text" name="nome" placeholder="Ex: Tesouro IPCA, BTC, fundo de ações" required maxlength="120"
                           data-msg-required="Informe o nome do investimento." data-msg-maxlength="Use no máximo 120 caracteres.">
                </label>

                <label class="form-field">
                    <span>Tipo</span>
                    <select name="tipo" required data-msg-required="Selecione um tipo.">
<?php foreach ($painel->tipos as $tipo): ?>
                        <option value="<?= $this->e($tipo->value) ?>"><?= $this->e($tipo->rotulo()) ?></option>
<?php endforeach; ?>
                    </select>
                </label>

                <label class="form-field">
                    <span>Data</span>
                    <input type="date" name="data" value="<?= $this->e($painel->dataPadrao->format('Y-m-d')) ?>" required data-rule="date"
                           data-msg-required="Informe uma data válida." data-msg-invalid="Informe uma data válida.">
                </label>

                <label class="form-field">
                    <span>Valor investido</span>
                    <input type="text" name="valor_investido" placeholder="0,00" required data-mask="money" data-rule="money"
                           data-msg-required="Informe o valor investido." data-msg-invalid="Informe um valor válido (ex.: 1.234,56)." data-msg-min="O valor investido deve ser maior que zero.">
                </label>

                <label class="form-field">
                    <span>Valor atual (opcional)</span>
                    <input type="text" name="valor_atual" placeholder="Opcional — igual ao investido" data-mask="money" data-rule="money"
                           data-msg-invalid="Informe um valor válido (ex.: 1.234,56)." data-msg-min="O valor atual deve ser maior que zero.">
                </label>
            </div>

            <div class="modal-actions">
                <button class="ghost-button" type="button" data-modal-close>Cancelar</button>
                <button class="primary-button" type="submit">Salvar investimento</button>
            </div>
        </form>
    </div>
</div>
