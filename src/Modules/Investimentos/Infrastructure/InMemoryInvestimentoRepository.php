<?php

declare(strict_types=1);

namespace OverDark\Modules\Investimentos\Infrastructure;

use OverDark\Modules\Investimentos\Domain\Alocacao;
use OverDark\Modules\Investimentos\Domain\Investimento;
use OverDark\Modules\Investimentos\Domain\InvestimentoRepository;
use OverDark\Modules\Investimentos\Domain\ResumoCarteira;
use OverDark\Modules\Investimentos\Domain\TipoInvestimento as Tipo;
use OverDark\Shared\Domain\Money;
use OverDark\Shared\Domain\PontoMensal;

/**
 * Dados de demonstração (mock) enquanto a persistência em MySQL não existe.
 *
 * Atenção: o resumo e a lista são fixtures independentes — a soma da lista
 * (R$ 67.300 investidos) não bate com o total do resumo (R$ 62.300).
 */
final class InMemoryInvestimentoRepository implements InvestimentoRepository
{
    public function listar(): array
    {
        return [
            new Investimento('Reserva de emergência CDI', Tipo::Reserva, Money::fromReais(10000), Money::fromReais(10860)),
            new Investimento('Tesouro Prefixado 2029', Tipo::RendaFixa, Money::fromReais(12500), Money::fromReais(13420)),
            new Investimento('Fundo multimercado Atlas', Tipo::Fundos, Money::fromReais(9800), Money::fromReais(10060)),
            new Investimento('Carteira ações Brasil', Tipo::Acoes, Money::fromReais(18000), Money::fromReais(22450)),
            new Investimento('Bitcoin posição principal', Tipo::Cripto, Money::fromReais(12000), Money::fromReais(12150)),
            new Investimento('Caixa de oportunidade', Tipo::Reserva, Money::fromReais(5000)),
        ];
    }

    public function resumo(): ResumoCarteira
    {
        return new ResumoCarteira(
            totalInvestido: Money::fromReais(62300),
            composicao: [
                new Alocacao(Tipo::Reserva, Money::fromReais(12400)),
                new Alocacao(Tipo::RendaFixa, Money::fromReais(18600)),
                new Alocacao(Tipo::Fundos, Money::fromReais(14800)),
                new Alocacao(Tipo::Acoes, Money::fromReais(15900)),
                new Alocacao(Tipo::Cripto, Money::fromReais(7240)),
            ],
        );
    }

    public function evolucaoPatrimonio(): array
    {
        return [
            new PontoMensal('Out', Money::fromReais(48200)),
            new PontoMensal('Nov', Money::fromReais(51100)),
            new PontoMensal('Dez', Money::fromReais(54600)),
            new PontoMensal('Jan', Money::fromReais(57300)),
            new PontoMensal('Fev', Money::fromReais(62900)),
            new PontoMensal('Mar', Money::fromReais(68940)),
        ];
    }
}
