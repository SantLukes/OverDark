<?php

declare(strict_types=1);

namespace OverDark\Tests\Feature;

use OverDark\Core\Http\Request;
use OverDark\Tests\Support\FeatureTestCase;

final class RoutesTest extends FeatureTestCase
{
    public function testRotaInexistente(): void
    {
        self::assertSame(404, $this->get('/nao-existe')->status);
    }

    public function testMetodoNaoPermitido(): void
    {
        $response = $this->app->handle(new Request('DELETE', '/dashboard'));

        self::assertSame(405, $response->status);
        self::assertSame('GET', $response->headers['Allow']);
    }

    public function testLinkLegadoComQueryUrlContinuaFuncionando(): void
    {
        $_GET = ['url' => '/login'];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/index.php?url=/login';

        self::assertStringContainsString('Overdark - Login', $this->app->handle(Request::fromGlobals())->body);
    }

    public function testInvestimentosSegueComDadosDeDemonstracao(): void
    {
        $this->logarComo();

        $body = $this->get('/investimentos')->body;

        self::assertStringContainsString('R$ 62.300', $body);
        self::assertStringContainsString('+10,66%', $body);
    }
}
