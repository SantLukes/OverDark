<?php
/**
 * @var \OverDark\Core\View\Template $this
 * @var \OverDark\Modules\Movimentacoes\Application\PainelMovimentacoes $painel
 * @var string|null $sucesso
 * @var array<string, string> $erros
 * @var array<string, string> $antigo  valores enviados no POST que falhou
 */

use OverDark\Modules\Movimentacoes\Domain\SituacaoFluxo;

$this->layout('layouts/app', [
    'title' => 'Movimentações',
    'nav' => 'movimentacoes',
    'styles' => ['components.css', 'pages/movimentacoes.css'],
    'scripts' => ['pages/movimentacoes.js'],
]);

$consolidado = $painel->consolidado;
$saldo = $consolidado->saldo();
$situacao = $consolidado->situacao();

$antigo = [
    'tipo' => 'receita',
    'data' => $painel->dataPadrao->format('Y-m-d'),
    'descricao' => '',
    'valor' => '',
    'parcelas' => '1',
    'valor_parcela' => '',
    ...$antigo,
];

// data-error-for: o mesmo elemento é reaproveitado pela validação em JS.
$erro = fn (string $campo): string => isset($erros[$campo])
    ? '<small class="field-error" data-error-for="' . $this->e($campo) . '">' . $this->e($erros[$campo]) . '</small>'
    : '';
$invalido = static fn (string $campo): string => isset($erros[$campo]) ? ' aria-invalid="true"' : '';
?>
<main class="page-shell">
<?php if ($sucesso !== null): ?>
    <div class="flash flash-success" role="status"><?= $this->e($sucesso) ?></div>
<?php endif; ?>

    <section class="page-toolbar">
        <div class="page-heading">
            <p class="eyebrow">Controle financeiro</p>
            <h1>Movimentações</h1>
        </div>

        <div class="page-actions">
            <form method="get" action="/movimentacoes" class="month-filter">
                <label>
                    <span class="visually-hidden">Selecionar mês</span>
                    <select name="competencia" data-autosubmit>
<?php foreach ($painel->competencias as $competencia): ?>
                        <option value="<?= $this->e($competencia->chave()) ?>"<?= $competencia->equals($painel->competencia) ? ' selected' : '' ?>><?= $this->e($competencia->rotulo()) ?></option>
<?php endforeach; ?>
                    </select>
                </label>
            </form>

            <button class="primary-button" type="button" data-modal-open="movementModal">+ Nova movimentação</button>
        </div>
    </section>

    <section class="summary-grid">
        <article class="summary-card">
            <small>Receita total</small>
            <h2><?= $this->money($consolidado->receitas) ?></h2>
<?php if ($consolidado->variacaoReceita !== null): ?>
            <p class="card-note<?= $consolidado->variacaoReceita >= 0 ? ' positive' : ' negative' ?>"><?= $this->percent($consolidado->variacaoReceita, signed: true, decimals: 1) ?> em relação ao mês anterior</p>
<?php else: ?>
            <p class="card-note">Sem receita no mês anterior para comparar</p>
<?php endif; ?>
        </article>

        <article class="summary-card">
            <small>Gastos totais</small>
            <h2><?= $this->money($consolidado->gastosTotais()) ?></h2>
            <p class="card-note"><?= $consolidado->quantidadeLancamentos ?> lançamento<?= $consolidado->quantidadeLancamentos === 1 ? '' : 's' ?> · <?= $this->money($consolidado->cartao) ?> no cartão</p>
        </article>

        <article class="summary-card">
            <small>Saldo do mês</small>
            <h2><?= $this->money($saldo) ?></h2>
<?php if ($situacao === SituacaoFluxo::Positivo): ?>
            <p class="card-note positive">Receitas cobrem os gastos do mês</p>
<?php else: ?>
            <p class="card-note negative">Gastos superam as receitas</p>
<?php endif; ?>
        </article>

        <article class="summary-card status-card">
            <small>Status</small>
            <div class="status-row <?= $this->e($situacao->value) ?>">
                <span class="status-icon"><?= $this->e($situacao->icone()) ?></span>
                <div>
                    <h2><?= $this->e($situacao->rotulo()) ?></h2>
                    <p class="card-note"><?= $this->e($situacao->descricao()) ?></p>
                </div>
            </div>
        </article>
    </section>

    <section class="panel table-panel">
        <div class="panel-header">
            <div>
                <p class="panel-kicker">Lançamentos de <?= $this->e($painel->competencia->rotulo()) ?></p>
                <h3>Lista de movimentações</h3>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
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
<?php foreach ($painel->movimentacoes as $movimentacao): ?>
<?php
    $confirmacao = $movimentacao->parcela !== null
        ? sprintf('A compra "%s" tem %d parcelas. Todas elas serão removidas.', $movimentacao->descricao, $movimentacao->parcela->total)
        : sprintf('A movimentação "%s" será removida. Essa ação não pode ser desfeita.', $movimentacao->descricao);
?>
                    <tr>
                        <td><span class="type-badge <?= $this->e($movimentacao->tipo->value) ?>"><?= $this->e($movimentacao->tipo->rotulo()) ?></span></td>
                        <td>
                            <?= $this->e($movimentacao->descricao) ?>
<?php if ($movimentacao->parcela !== null): ?>
                            <span class="installment-tag" title="Parcela <?= $this->e($movimentacao->parcela->rotulo()) ?>"><?= $this->e($movimentacao->parcela->rotulo()) ?></span>
<?php endif; ?>
                        </td>
                        <td class="text-end amount <?= $movimentacao->isEntrada() ? 'positive' : 'negative' ?>"><?= $this->money($movimentacao->valor) ?></td>
                        <td><?= $this->date($movimentacao->data) ?></td>
                        <td class="text-end">
                            <form method="post" action="/movimentacoes/excluir" class="inline-form"
                                  data-confirm="<?= $this->e($confirmacao) ?>"
                                  data-confirm-title="<?= $movimentacao->parcela !== null ? 'Excluir compra parcelada?' : 'Excluir movimentação?' ?>"
                                  data-confirm-button="Excluir">
                                <?= $this->csrf() ?>
                                <input type="hidden" name="id" value="<?= (int) $movimentacao->id ?>">
                                <input type="hidden" name="competencia_atual" value="<?= $this->e($painel->competencia->chave()) ?>">
                                <button class="table-action danger" type="submit">Excluir</button>
                            </form>
                        </td>
                    </tr>
<?php endforeach; ?>
<?php if ($painel->movimentacoes === []): ?>
                    <tr>
                        <td colspan="5" class="empty-state">Nenhuma movimentação em <?= $this->e($painel->competencia->rotulo()) ?>. Use <strong>+ Nova movimentação</strong> para começar.</td>
                    </tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<div class="app-modal-backdrop" data-modal-backdrop="movementModal" hidden></div>

<div class="app-modal" id="movementModal" role="dialog" aria-modal="true" aria-labelledby="movementModalTitle"<?= $erros !== [] ? ' data-modal-autoopen' : '' ?> hidden>
    <div class="app-modal-card">
        <div class="modal-header-row">
            <div>
                <p class="panel-kicker">Novo lançamento</p>
                <h3 id="movementModalTitle">Nova movimentação</h3>
            </div>

            <button class="modal-close" type="button" aria-label="Fechar modal" data-modal-close>×</button>
        </div>

        <form class="modal-form" method="post" action="/movimentacoes" id="movementForm" novalidate data-validate>
            <?= $this->csrf() ?>
            <input type="hidden" name="competencia_atual" value="<?= $this->e($painel->competencia->chave()) ?>">

<?php if ($erros !== []): ?>
            <div class="flash flash-error" role="alert">Revise os campos destacados.</div>
<?php endif; ?>

            <div class="form-grid">
                <label class="form-field">
                    <span>Tipo</span>
                    <select name="tipo" data-installments-toggle="installmentsFields" required data-msg-required="Selecione um tipo válido."<?= $invalido('tipo') ?>>
<?php foreach ($painel->tipos as $tipo): ?>
                        <option value="<?= $this->e($tipo->value) ?>"<?= $tipo->permiteParcelamento() ? ' data-parcelavel' : '' ?><?= $antigo['tipo'] === $tipo->value ? ' selected' : '' ?>><?= $this->e($tipo->rotuloCompleto()) ?></option>
<?php endforeach; ?>
                    </select>
                    <?= $erro('tipo') ?>
                </label>

                <label class="form-field">
                    <span>Data</span>
                    <input type="date" name="data" value="<?= $this->e($antigo['data']) ?>" required data-rule="date"
                           data-msg-required="Informe uma data válida." data-msg-invalid="Informe uma data válida."<?= $invalido('data') ?>>
                    <?= $erro('data') ?>
                </label>

                <label class="form-field form-field-wide">
                    <span>Descrição</span>
                    <input type="text" name="descricao" value="<?= $this->e($antigo['descricao']) ?>" maxlength="160" placeholder="Ex: Mercado, salário, notebook" required
                           data-msg-required="Informe uma descrição." data-msg-maxlength="Use no máximo 160 caracteres."<?= $invalido('descricao') ?>>
                    <?= $erro('descricao') ?>
                </label>

                <label class="form-field">
                    <span>Valor <small class="field-hint" data-installments-only>(total da compra)</small></span>
                    <input type="text" name="valor" value="<?= $this->e($antigo['valor']) ?>" placeholder="0,00" required data-mask="money" data-rule="money"
                           data-msg-required="Informe um valor válido (ex.: 1.234,56)." data-msg-invalid="Informe um valor válido (ex.: 1.234,56)." data-msg-min="O valor deve ser maior que zero."<?= $invalido('valor') ?>>
                    <?= $erro('valor') ?>
                </label>
            </div>

            <div class="installments-grid" id="installmentsFields" hidden>
                <label class="form-field">
                    <span>Parcelas</span>
                    <select name="parcelas"<?= $invalido('parcelas') ?>>
                        <option value="1"<?= $antigo['parcelas'] === '1' ? ' selected' : '' ?>>À vista</option>
<?php foreach ($painel->opcoesParcelas as $quantidade): ?>
                        <option value="<?= $quantidade ?>"<?= $antigo['parcelas'] === (string) $quantidade ? ' selected' : '' ?>><?= $quantidade ?> parcelas</option>
<?php endforeach; ?>
                    </select>
                    <?= $erro('parcelas') ?>
                </label>

                <label class="form-field">
                    <span>Valor por parcela</span>
                    <input type="text" name="valor_parcela" value="<?= $this->e($antigo['valor_parcela']) ?>" placeholder="Opcional — divide o total" data-mask="money" data-rule="money"
                           data-msg-invalid="Informe um valor válido (ex.: 1.234,56)." data-msg-min="O valor deve ser maior que zero."<?= $invalido('valor_parcela') ?>>
                    <?= $erro('valor_parcela') ?>
                </label>
            </div>

            <div class="modal-actions">
                <button class="ghost-button" type="button" data-modal-close>Cancelar</button>
                <button class="primary-button" type="submit">Salvar movimentação</button>
            </div>
        </form>
    </div>
</div>
