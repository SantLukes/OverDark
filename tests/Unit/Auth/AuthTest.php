<?php

declare(strict_types=1);

namespace OverDark\Tests\Unit\Auth;

use DateTimeImmutable;
use OverDark\Core\Clock\FixedClock;
use OverDark\Core\Validation\ValidationException;
use OverDark\Modules\Auth\Application\AutenticarUsuario;
use OverDark\Modules\Auth\Application\CriarUsuario;
use OverDark\Modules\Auth\Domain\CredenciaisInvalidas;
use OverDark\Tests\Support\InMemoryUsuarioRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AuthTest extends TestCase
{
    private InMemoryUsuarioRepository $usuarios;

    protected function setUp(): void
    {
        $this->usuarios = new InMemoryUsuarioRepository();
        (new CriarUsuario($this->usuarios, new FixedClock(new DateTimeImmutable()), new NullLogger()))
            ->executar('Lucas', 'Lucas@OverDark.local', 'senha-secreta');
    }

    public function testSenhaEhGuardadaComoHash(): void
    {
        $usuario = $this->usuarios->buscarPorEmail('lucas@overdark.local');

        self::assertNotNull($usuario, 'E-mail deve ser normalizado para minúsculas.');
        self::assertNotSame('senha-secreta', $usuario->senhaHash);
        self::assertTrue($usuario->senhaConfere('senha-secreta'));
    }

    public function testAutenticaComCredenciaisCorretas(): void
    {
        $usuario = (new AutenticarUsuario($this->usuarios, new NullLogger()))->executar(' LUCAS@overdark.local ', 'senha-secreta');

        self::assertSame('Lucas', $usuario->nome);
        self::assertSame('L', $usuario->identidade()->inicial());
    }

    public function testSenhaErrada(): void
    {
        try {
            (new AutenticarUsuario($this->usuarios, new NullLogger()))->executar('lucas@overdark.local', 'errada');
            self::fail('Deveria lançar CredenciaisInvalidas');
        } catch (CredenciaisInvalidas $e) {
            self::assertSame('senha_incorreta', $e->motivo);
            self::assertSame(CredenciaisInvalidas::MENSAGEM, $e->getMessage());
        }
    }

    public function testUsuarioInexistenteTemAMesmaMensagem(): void
    {
        try {
            (new AutenticarUsuario($this->usuarios, new NullLogger()))->executar('ninguem@overdark.local', 'x');
            self::fail('Deveria lançar CredenciaisInvalidas');
        } catch (CredenciaisInvalidas $e) {
            self::assertSame('usuario_inexistente', $e->motivo);
            self::assertSame(CredenciaisInvalidas::MENSAGEM, $e->getMessage());
        }
    }

    public function testCamposObrigatorios(): void
    {
        try {
            (new AutenticarUsuario($this->usuarios, new NullLogger()))->executar('nao-e-email', '');
            self::fail('Deveria lançar ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(['email', 'senha'], array_keys($e->errors));
        }
    }

    public function testCadastroValidaEmailDuplicadoESenhaCurta(): void
    {
        try {
            (new CriarUsuario($this->usuarios, new FixedClock(new DateTimeImmutable()), new NullLogger()))->executar('', 'lucas@overdark.local', '123');
            self::fail('Deveria lançar ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(['nome', 'email', 'senha'], array_keys($e->errors));
        }
    }
}
