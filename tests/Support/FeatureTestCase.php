<?php

declare(strict_types=1);

namespace OverDark\Tests\Support;

use DateTimeImmutable;
use OverDark\Core\Application;
use OverDark\Core\Clock\Clock;
use OverDark\Core\Clock\FixedClock;
use OverDark\Core\Database\Migrator;
use OverDark\Core\Http\Request;
use OverDark\Core\Http\Response;
use OverDark\Core\Logging\LogContext;
use OverDark\Core\Logging\StructuredLogger;
use OverDark\Core\Security\Csrf;
use OverDark\Core\Session\ArraySession;
use OverDark\Core\Session\Session;
use OverDark\Modules\Auth\Application\CriarUsuario;
use OverDark\Modules\Auth\Domain\Usuario;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Base dos testes de ponta a ponta: aplicação real + banco overdark_test.
 *
 * - O schema é recriado uma vez por execução; as tabelas são limpas a cada teste.
 * - Sessão em memória, relógio parado em 15/03/2026 12:00 e logs capturados em $this->logs.
 */
abstract class FeatureTestCase extends TestCase
{
    protected const AGORA = '2026-03-15 12:00:00';

    private static bool $schemaPronto = false;

    protected Application $app;

    protected ArraySession $session;

    /** Todos os logs emitidos durante o teste. */
    protected InMemoryLogHandler $logs;

    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $container = $this->app->container();

        $this->session = new ArraySession();
        $container->set(Session::class, fn () => $this->session);
        $container->set(Clock::class, static fn () => new FixedClock(new DateTimeImmutable(self::AGORA)));

        $this->logs = new InMemoryLogHandler();
        $container->set(LoggerInterface::class, fn () => new StructuredLogger($container->get(LogContext::class), [$this->logs]));

        $pdo = $this->pdo();

        if ($this->valor('SELECT DATABASE()') !== 'overdark_test') {
            self::fail('Os testes de feature só rodam no banco overdark_test.');
        }

        if (!self::$schemaPronto) {
            $migrator = new Migrator($pdo, dirname(__DIR__, 2) . '/database/migrations');
            $migrator->dropAll();
            $migrator->migrate();
            self::$schemaPronto = true;
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec('TRUNCATE movimentacoes');
        $pdo->exec('TRUNCATE usuarios');
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    protected function pdo(): PDO
    {
        /** @var PDO */
        return $this->app->container()->get(PDO::class);
    }

    /** Primeiro campo da primeira linha de uma consulta. */
    protected function valor(string $sql): mixed
    {
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function linhas(string $sql): array
    {
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute();

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /**
     * @param array<string, string> $query
     */
    protected function get(string $path, array $query = []): Response
    {
        return $this->app->handle(new Request('GET', $path, $query));
    }

    /**
     * POST com token CSRF válido (a menos que $comCsrf = false).
     *
     * @param array<string, string|int> $dados
     */
    protected function post(string $path, array $dados = [], bool $comCsrf = true): Response
    {
        if ($comCsrf) {
            $dados[Csrf::FIELD] = (new Csrf($this->session))->token();
        }

        return $this->app->handle(new Request('POST', $path, [], $dados));
    }

    protected function criarUsuario(string $email = 'lucas@overdark.local', string $senha = 'senha-secreta', string $nome = 'Lucas'): Usuario
    {
        /** @var CriarUsuario $criar */
        $criar = $this->app->container()->get(CriarUsuario::class);

        return $criar->executar($nome, $email, $senha);
    }

    protected function logarComo(?Usuario $usuario = null): Usuario
    {
        $usuario ??= $this->criarUsuario();
        $this->session->set('usuario_id', $usuario->id);

        return $usuario;
    }

    protected static function assertRedirect(string $location, Response $response): void
    {
        self::assertSame(302, $response->status, 'Esperava redirecionamento. Corpo: ' . mb_substr($response->body, 0, 300));
        self::assertSame($location, $response->headers['Location'] ?? null);
    }
}
