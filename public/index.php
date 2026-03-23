<?php

require_once __DIR__ . '/../config/routes.php';
$url = isset($_GET['url']) ? $_GET['url'] : parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: index.php?url=/dashboard');
    exit;
}

$route = isset($routes[$url]) ? $routes[$url] : null;

if ($route === 'dashboard') {
    require_once __DIR__ . '/../src/views/dashboard/index.php';
    exit;
}

if ($route === 'movimentacoes') {
    require_once __DIR__ . '/../src/views/movimentacoes/index.php';
    exit;
}

if ($route === 'investimentos') {
    require_once __DIR__ . '/../src/views/investimentos/index.php';
    exit;
}

if ($route !== 'login') {
    http_response_code(404);
    echo "404 - Página não encontrada";
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Overdark - Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/login.css">
</head>
<body>

<div class="container-fluid vh-100">
    <div class="row h-100">

        <div class="col-md-5 d-flex align-items-center justify-content-center">

            <div class="login-card p-4">
                <h3 class="mb-3 text-purple">Login</h3>
                <p class="text-secondary mb-4"><b>Acesse sua conta</b></p>

                <form method="post" action="index.php">
                    <div class="mb-3">
                        <label class="form-label text-purple">Email</label>
                        <input type="email" class="form-control" placeholder="Digite seu email">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-purple">Senha</label>
                        <input type="password" class="form-control" placeholder="Digite sua senha">
                    </div>

                    <button class="btn btn-primary w-100">Entrar</button>
                </form>
            </div>

        </div>

        <div class="col-md-7 d-none d-md-flex branding-section">

            <img src="/assets/logo2.png" class="logo-top">

            <div class="branding-center">

                <h1><b>Domine seu dinheiro</b></h1>

                <p>
                    O <b>OverDark</b> transforma sua rotina financeira em decisões<br> inteligentes,
                    organizando gastos, receitas e investimentos<br> em um só lugar.
                </p>

            </div>

            <div class="branding-footer">
                Controle total • Simples • Poderoso
            </div>

        </div>

    </div>
</div>

</body>
</html>
