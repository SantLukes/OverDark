<?php

declare(strict_types=1);

namespace OverDark\Tests\Unit\Movimentacoes;

use DateTimeImmutable;
use OverDark\Core\Clock\FixedClock;
use OverDark\Core\Validation\ValidationException;
use OverDark\Modules\Movimentacoes\Application\ExcluirMovimentacao;
use OverDark\Modules\Movimentacoes\Application\MovimentacaoService;
use OverDark\Modules\Movimentacoes\Application\NovaMovimentacao;
use OverDark\Modules\Movimentacoes\Application\RegistrarMovimentacao;
use OverDark\Modules\Movimentacoes\Domain\MovimentacaoNaoEncontrada;
use OverDark\Shared\Domain\Competencia;
use OverDark\Tests\Support\InMemoryMovimentacaoRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CasosDeUsoTest extends TestCase
{
    private const USUARIO = 1;

    private InMemoryMovimentacaoRepository $repo;
    private RegistrarMovimentacao $registrar;

    protected function setUp(): void
    {
        $this->repo = new InMemoryMovimentacaoRepository();
        $this->registrar = new RegistrarMovimentacao($this->repo, new NullLogger());
    }

    public function testRegistraMovimentacaoSimples(): void
    {
        $criadas = $this->registrar->executar(self::USUARIO, new NovaMovimentacao('receita', '2026-03-05', ' Salário ', '5.200,00'));

        self::assertCount(1, $criadas);
        self::assertSame('Salário', $criadas[0]->descricao);
        self::assertSame(520000, $criadas[0]->valor->cents);
        self::assertNotNull($criadas[0]->id);
    }

    public function testCompraParceladaGeraUmaMovimentacaoPorMesNoMesmoGrupo(): void
    {
        $criadas = $this->registrar->executar(self::USUARIO, new NovaMovimentacao('cartao', '2026-03-11', 'Notebook', '3.990', '10'));

        self::assertCount(10, $criadas);
        self::assertSame('1/10', $criadas[0]->parcela?->rotulo());
        self::assertSame('10/10', $criadas[9]->parcela?->rotulo());
        self::assertSame('2026-12-11', $criadas[9]->data->format('Y-m-d'));
        self::assertCount(1, array_unique(array_map(static fn ($m) => $m->parcela?->grupo, $criadas)));
    }

    public function testCartaoAVistaNaoGeraParcelas(): void
    {
        $criadas = $this->registrar->executar(self::USUARIO, new NovaMovimentacao('cartao', '2026-03-11', 'Passagem', '287', '1'));

        self::assertCount(1, $criadas);
        self::assertNull($criadas[0]->parcela);
    }

    public function testParcelasSaoIgnoradasParaTiposNaoParcelaveis(): void
    {
        $criadas = $this->registrar->executar(self::USUARIO, new NovaMovimentacao('gasto', '2026-03-11', 'Mercado', '420', '10'));

        self::assertCount(1, $criadas);
    }

    public function testValidacaoReuneTodosOsErrosPorCampo(): void
    {
        try {
            $this->registrar->executar(self::USUARIO, new NovaMovimentacao('pix', '2026-02-30', '', '0'));
            self::fail('Deveria lançar ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(['tipo', 'data', 'descricao', 'valor'], array_keys($e->errors));
        }
    }

    public function testQuantidadeDeParcelasInvalida(): void
    {
        $this->expectException(ValidationException::class);
        $this->registrar->executar(self::USUARIO, new NovaMovimentacao('cartao', '2026-03-11', 'TV', '1000', '5'));
    }

    public function testExcluirParcelaRemoveACompraInteira(): void
    {
        $criadas = $this->registrar->executar(self::USUARIO, new NovaMovimentacao('cartao', '2026-03-11', 'TV', '1000', '4'));

        $removidas = (new ExcluirMovimentacao($this->repo, new NullLogger()))->executar(self::USUARIO, (int) $criadas[2]->id);

        self::assertCount(4, $removidas);
        self::assertSame([], $this->repo->competenciasComLancamentos(self::USUARIO));
    }

    public function testNaoExcluiMovimentacaoDeOutroUsuario(): void
    {
        $criadas = $this->registrar->executar(self::USUARIO, new NovaMovimentacao('receita', '2026-03-05', 'Salário', '100'));

        $this->expectException(MovimentacaoNaoEncontrada::class);
        (new ExcluirMovimentacao($this->repo, new NullLogger()))->executar(99, (int) $criadas[0]->id);
    }

    public function testServicoPreencheMesesSemLancamentosNoHistorico(): void
    {
        $this->registrar->executar(self::USUARIO, new NovaMovimentacao('receita', '2026-01-05', 'Salário', '1000'));
        $this->registrar->executar(self::USUARIO, new NovaMovimentacao('gasto', '2026-03-05', 'Conta', '300'));

        $servico = new MovimentacaoService($this->repo, new FixedClock(new DateTimeImmutable('2026-03-15')));
        $historico = $servico->historico(self::USUARIO, new Competencia(2026, 1), new Competencia(2026, 3));

        self::assertSame(['2026-01', '2026-02', '2026-03'], array_map(static fn ($c) => $c->competencia->chave(), $historico));
        self::assertSame([100000, 0, -30000], array_map(static fn ($c) => $c->saldo()->cents, $historico));
    }

    public function testPainelUsaMesAtualEOferecemMesesComLancamentos(): void
    {
        $this->registrar->executar(self::USUARIO, new NovaMovimentacao('cartao', '2026-03-11', 'TV', '1000', '3'));

        $servico = new MovimentacaoService($this->repo, new FixedClock(new DateTimeImmutable('2026-03-15')));
        $painel = $servico->painel(self::USUARIO);

        self::assertSame('2026-03', $painel->competencia->chave());
        self::assertSame(['2026-05', '2026-04', '2026-03'], array_map(static fn ($c) => $c->chave(), $painel->competencias));
        self::assertCount(1, $painel->movimentacoes);
    }
}
