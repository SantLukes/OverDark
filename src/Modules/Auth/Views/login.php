<?php
/**
 * @var \OverDark\Core\View\Template $this
 * @var array<string, string> $erros
 * @var string $emailAnterior
 */

$this->layout('layouts/guest', [
    'title' => 'Login',
    'styles' => ['pages/login.css'],
    'scripts' => ['pages/login.js'],
]);
?>
<div class="login-shell">
    <section class="login-side">
        <div class="login-card">
            <div class="brand">
                <span class="brand-mark"><img src="<?= $this->asset('img/logo-mark.png') ?>" alt=""></span>
                <span class="brand-name">Over<span>Dark</span></span>
            </div>

            <h1>Bem-vindo de volta</h1>
            <p class="login-subtitle">Entre para acompanhar suas finanças.</p>

<?php if (isset($erros['geral'])): ?>
            <div class="login-alert" role="alert"><?= $this->e($erros['geral']) ?></div>
<?php endif; ?>

            <form method="post" action="/login" id="loginForm" novalidate data-validate data-error-class="login-field-error">
                <?= $this->csrf() ?>

                <div class="login-field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= $this->e($emailAnterior) ?>" class="<?= isset($erros['email']) ? 'is-invalid' : '' ?>" placeholder="voce@email.com" autocomplete="username" required autofocus
                           data-rule="email" data-msg-required="Informe um e-mail válido." data-msg-invalid="Informe um e-mail válido.">
<?php if (isset($erros['email'])): ?>
                    <small class="login-field-error" data-error-for="email"><?= $this->e($erros['email']) ?></small>
<?php endif; ?>
                </div>

                <div class="login-field">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" class="<?= isset($erros['senha']) ? 'is-invalid' : '' ?>" placeholder="••••••••" autocomplete="current-password" required
                           data-msg-required="Informe a senha.">
<?php if (isset($erros['senha'])): ?>
                    <small class="login-field-error" data-error-for="senha"><?= $this->e($erros['senha']) ?></small>
<?php endif; ?>
                </div>

                <button class="login-submit" type="submit" data-loading-text="Entrando...">Entrar</button>
            </form>
        </div>

        <p class="login-footnote">OverDark · controle financeiro pessoal</p>
    </section>

    <aside class="login-brand-panel" aria-hidden="true">
        <span class="login-bird-watermark"></span>

        <div class="login-brand-copy">
            <h2>Domine seu dinheiro</h2>
            <p>O <strong>OverDark</strong> transforma sua rotina financeira em decisões inteligentes, organizando gastos, receitas e investimentos em um só lugar.</p>

            <ul class="login-highlights">
                <li>Parcelas do cartão mês a mês</li>
                <li>Saldo e evolução em tempo real</li>
                <li>Receitas, gastos e investimentos</li>
            </ul>
        </div>

        <div class="login-brand-footer">Controle total <span>•</span> Simples <span>•</span> Poderoso</div>
    </aside>
</div>
