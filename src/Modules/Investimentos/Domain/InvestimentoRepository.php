<?php

declare(strict_types=1);

namespace OverDark\Modules\Investimentos\Domain;

use OverDark\Shared\Domain\PontoMensal;

interface InvestimentoRepository
{
    /**
     * @return list<Investimento>
     */
    public function listar(): array;

    public function resumo(): ResumoCarteira;

    /**
     * Patrimônio total mês a mês (do mais antigo para o mais recente).
     *
     * @return list<PontoMensal>
     */
    public function evolucaoPatrimonio(): array;
}
