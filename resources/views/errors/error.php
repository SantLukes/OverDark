<?php
/**
 * Página de erro exibida ao usuário. O código (correlation_id) é o mesmo
 * dos logs, do alerta no Slack e do header X-Correlation-ID (aba Network).
 *
 * @var \OverDark\Core\View\Template $this
 * @var int $status
 * @var string $rota
 * @var \DateTimeImmutable $horario
 * @var \Throwable|null $exception  preenchido apenas com APP_DEBUG=true
 * @var string $correlationId
 */
$correlationId ??= '';

[$titulo, $descricao] = match ($status) {
    400 => ['Requisição inválida', 'Os dados enviados não puderam ser processados.'],
    403 => ['Acesso negado', 'Você não tem permissão para acessar este recurso.'],
    404 => ['Página não encontrada', 'O endereço acessado não existe ou o item não está mais disponível.'],
    405 => ['Ação não permitida', 'Esta página não aceita esse tipo de envio.'],
    419 => ['Sessão expirada', 'Por segurança, sua sessão expirou. Volte, recarregue a página e tente novamente.'],
    503 => ['Serviço temporariamente indisponível', 'Estamos com instabilidade. Tente novamente em alguns instantes.'],
    default => ['Algo deu errado', 'Não foi possível concluir sua solicitação. Nossa equipe já foi notificada.'],
};
$mostrarCodigo = $status >= 500 || $status === 419;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdark - <?= $this->e($titulo) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $this->asset('css/base.css') ?>">
    <link rel="stylesheet" href="<?= $this->asset('css/pages/erro.css') ?>">
</head>
<body>
<main class="error-shell"
      data-error
      data-status="<?= (int) $status ?>"
      data-correlation-id="<?= $this->e($correlationId) ?>"
      data-rota="<?= $this->e($rota) ?>">

    <div class="error-card">
        <div class="brand error-brand">
            <span class="brand-mark"><img src="<?= $this->asset('img/logo-mark.png') ?>" alt=""></span>
            <span class="brand-name">Over<span>Dark</span></span>
        </div>

        <p class="error-status">Erro <?= (int) $status ?></p>
        <h1><?= $this->e($titulo) ?></h1>
        <p class="error-description"><?= $this->e($descricao) ?></p>

<?php if ($mostrarCodigo): ?>
        <div class="error-code-box">
            <small>Código do erro</small>
            <div class="error-code-row">
                <code id="errorCode"><?= $this->e($correlationId) ?></code>
                <button type="button" class="error-copy" data-copy-target="errorCode">Copiar</button>
            </div>
            <small class="error-meta"><?= $this->e($horario->format('d/m/Y H:i:s')) ?></small>
        </div>
        <p class="error-hint">Ao entrar em contato com o suporte, informe este código.</p>
<?php endif; ?>

        <div class="error-actions">
            <button type="button" class="ghost-button" data-history-back>Voltar</button>
            <a href="/dashboard" class="primary-button">Ir para o dashboard</a>
        </div>

<?php if ($exception !== null): ?>
        <details class="error-debug">
            <summary>Detalhes técnicos (APP_DEBUG=true — nunca em produção)</summary>
            <pre><?= $this->e($exception::class . ': ' . $exception->getMessage() . "\n\n" . $exception->getTraceAsString()) ?></pre>
        </details>
<?php endif; ?>
    </div>
</main>

<script type="module" src="<?= $this->asset('js/pages/erro.js') ?>"></script>
</body>
</html>
