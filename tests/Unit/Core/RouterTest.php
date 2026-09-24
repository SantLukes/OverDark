<?php

declare(strict_types=1);

namespace OverDark\Tests\Unit\Core;

use OverDark\Core\Http\HttpException;
use OverDark\Core\Http\Request;
use OverDark\Core\Http\Router;
use OverDark\Core\Security\VerifyCsrfToken;
use PHPUnit\Framework\TestCase;
use stdClass;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->get('/login', [stdClass::class, 'form']);
        $this->router->group([VerifyCsrfToken::class], static function (Router $r): void {
            $r->get('/dashboard', [stdClass::class, 'index']);
            $r->post('/salvar', [stdClass::class, 'salvar']);
        });
    }

    public function testEncontraRota(): void
    {
        self::assertSame([stdClass::class, 'index'], $this->router->match('GET', '/dashboard')->handler);
        self::assertSame([stdClass::class, 'index'], $this->router->match('GET', '/dashboard/')->handler);
        self::assertSame([stdClass::class, 'index'], $this->router->match('HEAD', '/dashboard')->handler);
    }

    public function testGrupoAplicaMiddlewareSomenteAsRotasDeDentro(): void
    {
        self::assertSame([VerifyCsrfToken::class], $this->router->match('GET', '/dashboard')->middleware);
        self::assertSame([], $this->router->match('GET', '/login')->middleware);
    }

    public function testRotaInexistenteRetorna404(): void
    {
        try {
            $this->router->match('GET', '/nao-existe');
            self::fail('Deveria lançar HttpException');
        } catch (HttpException $e) {
            self::assertSame(404, $e->status);
        }
    }

    public function testMetodoNaoPermitidoRetorna405ComAllow(): void
    {
        try {
            $this->router->match('GET', '/salvar');
            self::fail('Deveria lançar HttpException');
        } catch (HttpException $e) {
            self::assertSame(405, $e->status);
            self::assertSame(['Allow' => 'POST'], $e->headers);
        }
    }

    public function testNormalizacaoDeCaminho(): void
    {
        self::assertSame('/', Request::normalizePath(''));
        self::assertSame('/', Request::normalizePath('/index.php'));
        self::assertSame('/movimentacoes', Request::normalizePath('movimentacoes/'));
    }
}
