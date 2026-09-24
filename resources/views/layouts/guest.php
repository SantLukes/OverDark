<?php
/**
 * Layout das páginas públicas (login), sem header/navegação.
 *
 * @var \OverDark\Core\View\Template $this
 * @var string $content
 * @var string $title
 * @var list<string> $styles
 * @var list<string> $scripts  módulos JS extras, relativos a public/assets/js
 */
$styles ??= [];
$scripts ??= [];
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
    <link rel="stylesheet" href="<?= $this->asset('css/base.css') ?>">
<?php foreach ($styles as $style): ?>
    <link rel="stylesheet" href="<?= $this->asset('css/' . $style) ?>">
<?php endforeach; ?>
</head>
<body>

<?= $content ?>

<?php foreach ($scripts as $script): ?>
<script type="module" src="<?= $this->asset('js/' . $script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
