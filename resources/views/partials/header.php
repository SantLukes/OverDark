<?php
/**
 * @var \OverDark\Core\View\Template $this
 * @var string|null $nav
 * @var \OverDark\Shared\Auth\UsuarioAutenticado|null $usuarioLogado  compartilhado pelo middleware Autenticado
 */
$usuarioLogado ??= null;
$links = [
    'dashboard' => ['/dashboard', 'Dashboard'],
    'movimentacoes' => ['/movimentacoes', 'Movimentações'],
    'investimentos' => ['/investimentos', 'Investimentos'],
];
?>
<header class="page-header">
    <div class="header-bar">
        <div class="header-left">
            <a href="/dashboard" class="brand" aria-label="OverDark — ir para o dashboard">
                <span class="brand-mark"><img src="<?= $this->asset('img/logo-mark.png') ?>" alt=""></span>
                <span class="brand-name">Over<span>Dark</span></span>
            </a>

            <nav class="main-nav" aria-label="Navegação principal">
<?php foreach ($links as $key => [$href, $label]): ?>
                <a href="<?= $this->e($href) ?>" class="main-nav-link<?= $key === $nav ? ' active' : '' ?>"<?= $key === $nav ? ' aria-current="page"' : '' ?>><?= $this->e($label) ?></a>
<?php endforeach; ?>
            </nav>
        </div>

<?php if ($usuarioLogado !== null): ?>
        <div class="user-menu" data-user-menu>
            <button class="user-menu-toggle" type="button" aria-expanded="false" aria-label="Abrir menu do usuário" data-user-menu-toggle>
                <span class="user-avatar"><?= $this->e($usuarioLogado->inicial()) ?></span>
            </button>

            <div class="user-dropdown" data-user-menu-dropdown hidden>
                <div class="user-dropdown-identity">
                    <strong><?= $this->e($usuarioLogado->nome) ?></strong>
                    <small><?= $this->e($usuarioLogado->email) ?></small>
                </div>

                <form method="post" action="/logout">
                    <?= $this->csrf() ?>
                    <button type="submit" class="user-dropdown-item">Sair</button>
                </form>
            </div>
        </div>
<?php endif; ?>
    </div>
</header>
