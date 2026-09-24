<?php

declare(strict_types=1);

use OverDark\Core\Clock\Clock;
use OverDark\Core\Clock\SystemClock;
use OverDark\Core\Container\Container;
use OverDark\Core\Database\ConnectionFactory;
use OverDark\Core\Logging\Handler\CurlSlackTransport;
use OverDark\Core\Logging\Handler\JsonLinesFileHandler;
use OverDark\Core\Logging\Handler\SlackWebhookHandler;
use OverDark\Core\Logging\LogContext;
use OverDark\Core\Logging\StructuredLogger;
use OverDark\Core\Security\Csrf;
use OverDark\Core\Security\VerifyCsrfToken;
use OverDark\Core\Session\NativeSession;
use OverDark\Core\Session\Session;
use OverDark\Core\View\View;
use OverDark\Modules\Auth\Application\AutenticarUsuario;
use OverDark\Modules\Auth\Application\CriarUsuario;
use OverDark\Modules\Auth\Application\SessaoUsuario;
use OverDark\Modules\Auth\Domain\UsuarioRepository;
use OverDark\Modules\Auth\Http\AuthController;
use OverDark\Modules\Auth\Http\Middleware\Autenticado;
use OverDark\Modules\Auth\Http\Middleware\Visitante;
use OverDark\Modules\Auth\Infrastructure\PdoUsuarioRepository;
use OverDark\Modules\Dashboard\Application\DashboardService;
use OverDark\Modules\Dashboard\Http\DashboardController;
use OverDark\Modules\Investimentos\Application\InvestimentoService;
use OverDark\Modules\Investimentos\Domain\InvestimentoRepository;
use OverDark\Modules\Investimentos\Http\InvestimentoController;
use OverDark\Modules\Investimentos\Infrastructure\InMemoryInvestimentoRepository;
use OverDark\Modules\Movimentacoes\Application\ExcluirMovimentacao;
use OverDark\Modules\Movimentacoes\Application\MovimentacaoService;
use OverDark\Modules\Movimentacoes\Application\RegistrarMovimentacao;
use OverDark\Modules\Movimentacoes\Domain\MovimentacaoRepository;
use OverDark\Modules\Movimentacoes\Http\MovimentacaoController;
use OverDark\Modules\Movimentacoes\Infrastructure\PdoMovimentacaoRepository;
use Psr\Log\LoggerInterface;

/**
 * Ligações do container de dependências.
 *
 * Para trocar a persistência de um módulo, altere apenas a implementação
 * ligada à interface do repositório. (View já é registrada em bootstrap/app.php.)
 */
return static function (Container $c): void {
    // Logs (ver docs/observabilidade.md)
    $c->set(LogContext::class, static fn () => new LogContext());
    $c->set(LoggerInterface::class, static function (Container $c) {
        /** @var array{arquivo: string, nivel: string, slack: array{webhook: string, nivel: string}} $config */
        $config = require __DIR__ . '/logging.php';
        /** @var array{name: string, env: string} $app */
        $app = require __DIR__ . '/app.php';

        return new StructuredLogger($c->get(LogContext::class), [
            new JsonLinesFileHandler($config['arquivo'], $config['nivel']),
            new SlackWebhookHandler($config['slack']['webhook'], new CurlSlackTransport(), $config['slack']['nivel'], $app['name'], $app['env']),
        ]);
    });

    // Infraestrutura
    $c->set(Clock::class, static fn () => new SystemClock());
    $c->set(ConnectionFactory::class, static fn () => ConnectionFactory::fromEnv());
    // A conexão só é aberta quando algum repositório é de fato usado.
    $c->set(PDO::class, static fn (Container $c) => $c->get(ConnectionFactory::class)->create());
    $c->set(Session::class, static fn () => new NativeSession());
    $c->set(Csrf::class, static fn (Container $c) => new Csrf($c->get(Session::class)));
    $c->set(VerifyCsrfToken::class, static fn (Container $c) => new VerifyCsrfToken($c->get(Csrf::class), $c->get(View::class)));

    // Auth
    $c->set(UsuarioRepository::class, static fn (Container $c) => new PdoUsuarioRepository($c->get(PDO::class)));
    $c->set(AutenticarUsuario::class, static fn (Container $c) => new AutenticarUsuario($c->get(UsuarioRepository::class), $c->get(LoggerInterface::class)));
    $c->set(CriarUsuario::class, static fn (Container $c) => new CriarUsuario($c->get(UsuarioRepository::class), $c->get(Clock::class), $c->get(LoggerInterface::class)));
    $c->set(SessaoUsuario::class, static fn (Container $c) => new SessaoUsuario($c->get(Session::class), $c->get(UsuarioRepository::class), $c->get(LoggerInterface::class)));
    $c->set(Autenticado::class, static fn (Container $c) => new Autenticado($c->get(SessaoUsuario::class), $c->get(View::class), $c->get(LogContext::class)));
    $c->set(Visitante::class, static fn (Container $c) => new Visitante($c->get(SessaoUsuario::class)));
    $c->set(AuthController::class, static fn (Container $c) => new AuthController(
        $c->get(AutenticarUsuario::class),
        $c->get(SessaoUsuario::class),
        $c->get(Session::class),
        $c->get(View::class),
        $c->get(LogContext::class),
    ));

    // Movimentações
    $c->set(MovimentacaoRepository::class, static fn (Container $c) => new PdoMovimentacaoRepository($c->get(PDO::class), $c->get(Clock::class)));
    $c->set(MovimentacaoService::class, static fn (Container $c) => new MovimentacaoService($c->get(MovimentacaoRepository::class), $c->get(Clock::class)));
    $c->set(RegistrarMovimentacao::class, static fn (Container $c) => new RegistrarMovimentacao($c->get(MovimentacaoRepository::class), $c->get(LoggerInterface::class)));
    $c->set(ExcluirMovimentacao::class, static fn (Container $c) => new ExcluirMovimentacao($c->get(MovimentacaoRepository::class), $c->get(LoggerInterface::class)));
    $c->set(MovimentacaoController::class, static fn (Container $c) => new MovimentacaoController(
        $c->get(MovimentacaoService::class),
        $c->get(RegistrarMovimentacao::class),
        $c->get(ExcluirMovimentacao::class),
        $c->get(Session::class),
        $c->get(View::class),
    ));

    // Dashboard
    $c->set(DashboardService::class, static fn (Container $c) => new DashboardService($c->get(MovimentacaoService::class)));
    $c->set(DashboardController::class, static fn (Container $c) => new DashboardController(
        $c->get(DashboardService::class),
        $c->get(View::class),
    ));

    // Investimentos (ainda com dados de demonstração)
    $c->set(InvestimentoRepository::class, static fn () => new InMemoryInvestimentoRepository());
    $c->set(InvestimentoService::class, static fn (Container $c) => new InvestimentoService($c->get(InvestimentoRepository::class)));
    $c->set(InvestimentoController::class, static fn (Container $c) => new InvestimentoController(
        $c->get(InvestimentoService::class),
        $c->get(View::class),
    ));
};
