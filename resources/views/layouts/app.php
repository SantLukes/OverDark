<?php
/**
 * Layout das páginas autenticadas (header + navegação).
 *
 * @var \OverDark\Core\View\Template $this
 * @var string $content
 * @var string $title
 * @var string $nav         chave do item ativo no menu (ver partials/header)
 * @var list<string> $styles  CSS extras, relativos a public/assets/css
 * @var list<string> $scripts módulos JS extras, relativos a public/assets/js
 * @var bool $charts        carrega Chart.js
 */
$styles ??= [];
$scripts ??= [];
$charts ??= false;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdark - <?= $this->e($title) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $this->asset('css/base.css') ?>">
<?php foreach ($styles as $style): ?>
    <link rel="stylesheet" href="<?= $this->asset('css/' . $style) ?>">
<?php endforeach; ?>
<?php if ($charts): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<?php endif; ?>
</head>
<body>

<?php $this->insert('partials/header', ['nav' => $nav ?? null]); ?>

<?= $content ?>

<script type="module" src="<?= $this->asset('js/components/user-menu.js') ?>"></script>
<?php foreach ($scripts as $script): ?>
<script type="module" src="<?= $this->asset('js/' . $script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
