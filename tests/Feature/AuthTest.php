<?php

declare(strict_types=1);

namespace OverDark\Tests\Feature;

use OverDark\Tests\Support\FeatureTestCase;

final class AuthTest extends FeatureTestCase
{
    public function testPaginasInternasExigemLogin(): void
    {
        foreach (['/dashboard', '/movimentacoes', '/investimentos'] as $rota) {
            self::assertRedirect('/login', $this->get($rota));
        }
    }

    public function testTelaDeLogin(): void
    {
        $response = $this->get('/login');

        self::assertSame(200, $response->status);
        self::assertStringContainsString('name="_token"', $response->body);
        self::assertStringContainsString('name="senha"', $response->body);
    }

    public function testLoginComSucessoAbreSessao(): void
    {
        $usuario = $this->criarUsuario();

        $response = $this->post('/login', ['email' => 'lucas@overdark.local', 'senha' => 'senha-secreta']);

        self::assertRedirect('/dashboard', $response);
        self::assertSame($usuario->id, $this->session->get('usuario_id'));
        self::assertSame(200, $this->get('/dashboard')->status);
    }

    public function testSenhaErradaVoltaParaOLoginComMensagemEEmail(): void
    {
        $this->criarUsuario();

        self::assertRedirect('/login', $this->post('/login', ['email' => 'lucas@overdark.local', 'senha' => 'errada']));
        self::assertNull($this->session->get('usuario_id'));

        $tela = $this->get('/login')->body;
        self::assertStringContainsString('E-mail ou senha inválidos.', $tela);
        self::assertStringContainsString('value="lucas@overdark.local"', $tela);
    }

    public function testLoginSemTokenCsrfEhRejeitado(): void
    {
        $this->criarUsuario();

        self::assertSame(419, $this->post('/login', ['email' => 'lucas@overdark.local', 'senha' => 'senha-secreta'], comCsrf: false)->status);
    }

    public function testUsuarioLogadoNaoVeOLogin(): void
    {
        $this->logarComo();

        self::assertRedirect('/dashboard', $this->get('/login'));
    }

    public function testLogoutEncerraASessao(): void
    {
        $this->logarComo();

        self::assertRedirect('/login', $this->post('/logout'));
        self::assertRedirect('/login', $this->get('/dashboard'));
    }

    public function testHeaderMostraOUsuarioLogado(): void
    {
        $this->logarComo($this->criarUsuario(nome: 'Lucas Santana'));

        $body = $this->get('/dashboard')->body;
        self::assertStringContainsString('<span class="user-avatar">L</span>', $body);
        self::assertStringContainsString('Lucas Santana', $body);
    }
}
