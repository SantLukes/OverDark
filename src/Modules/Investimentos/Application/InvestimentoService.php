<?php

declare(strict_types=1);

namespace OverDark\Modules\Investimentos\Application;

use DateTimeImmutable;
use OverDark\Modules\Investimentos\Domain\InvestimentoRepository;
use OverDark\Modules\Investimentos\Domain\TipoInvestimento;

final class InvestimentoService
{
    public function __construct(
        private readonly InvestimentoRepository $repository,
    ) {
    }

    public function painel(): PainelInvestimentos
    {
        return new PainelInvestimentos(
            resumo: $this->repository->resumo(),
            investimentos: $this->repository->listar(),
            evolucao: $this->repository->evolucaoPatrimonio(),
            tipos: TipoInvestimento::cases(),
            dataPadrao: new DateTimeImmutable('today'),
        );
    }
}
