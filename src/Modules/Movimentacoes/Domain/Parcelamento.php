<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use OverDark\Shared\Domain\Competencia;
use OverDark\Shared\Domain\Money;

/**
 * Divide uma compra no cartão em parcelas mensais.
 *
 * - Sem valor de parcela informado: o total é dividido igualmente e os centavos
 *   que sobrarem vão para a 1ª parcela (a soma sempre fecha com o total).
 * - Com valor de parcela informado (ex.: compra com juros): todas as parcelas
 *   têm esse valor.
 * - A 1ª parcela cai na data da compra; as demais, no mesmo dia dos meses
 *   seguintes (ajustado para o último dia quando o mês é mais curto).
 */
final class Parcelamento
{
    /** Quantidades de parcelas aceitas (1 = à vista, não gera parcelamento). */
    public const OPCOES = [2, 3, 4, 6, 10];

    public function __construct(
        public readonly Money $valorTotal,
        public readonly int $quantidade,
        public readonly ?Money $valorParcela = null,
    ) {
        if (!in_array($quantidade, self::OPCOES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Quantidade de parcelas inválida: %d. Opções: %s.',
                $quantidade,
                implode(', ', self::OPCOES),
            ));
        }

        if (!$valorTotal->isPositive()) {
            throw new InvalidArgumentException('O valor total deve ser maior que zero.');
        }

        if ($valorParcela !== null && !$valorParcela->isPositive()) {
            throw new InvalidArgumentException('O valor da parcela deve ser maior que zero.');
        }
    }

    /**
     * @return list<Money> valor de cada parcela, da 1ª à última
     */
    public function valores(): array
    {
        if ($this->valorParcela !== null) {
            return array_fill(0, $this->quantidade, $this->valorParcela);
        }

        $base = intdiv($this->valorTotal->cents, $this->quantidade);
        $resto = $this->valorTotal->cents - $base * $this->quantidade;

        $valores = array_fill(0, $this->quantidade, Money::fromCents($base));
        $valores[0] = Money::fromCents($base + $resto);

        return $valores;
    }

    /**
     * @return list<DateTimeImmutable> data de cada parcela, da 1ª à última
     */
    public function datas(DateTimeImmutable $dataCompra): array
    {
        $dia = (int) $dataCompra->format('j');
        $competencia = Competencia::daData($dataCompra);
        $datas = [];

        for ($i = 0; $i < $this->quantidade; $i++) {
            $alvo = $competencia->somarMeses($i);
            $ultimoDia = (int) $alvo->ultimoDia()->format('j');
            $datas[] = $alvo->primeiroDia()->setDate($alvo->ano, $alvo->mes, min($dia, $ultimoDia));
        }

        return $datas;
    }
}
