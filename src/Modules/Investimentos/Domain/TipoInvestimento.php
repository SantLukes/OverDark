<?php

declare(strict_types=1);

namespace OverDark\Modules\Investimentos\Domain;

/**
 * Classes de ativo em que a carteira pode ser distribuída.
 */
enum TipoInvestimento: string
{
    case Reserva = 'reserva';
    case RendaFixa = 'renda-fixa';
    case Fundos = 'fundos';
    case Acoes = 'acoes';
    case Cripto = 'cripto';

    public function rotulo(): string
    {
        return match ($this) {
            self::Reserva => 'Reserva',
            self::RendaFixa => 'Renda fixa',
            self::Fundos => 'Fundos',
            self::Acoes => 'Ações',
            self::Cripto => 'Cripto',
        };
    }
}
