<?php

declare(strict_types=1);

namespace OverDark\Core\Logging;

/**
 * Formata campos de log para leitura humana (Slack, terminal).
 *
 * O arquivo JSON continua com o dado bruto (ex.: valor_centavos: 555), que é
 * exato e fácil de filtrar/somar. Aqui só muda a EXIBIÇÃO: "valor : R$ 5,55".
 */
final class ExibicaoHumana
{
    private const SUFIXO_CENTAVOS = '_centavos';

    /**
     * @return array{0: string, 1: mixed} [nome exibido, valor exibido]
     */
    public static function campo(string $campo, mixed $valor): array
    {
        if (str_ends_with($campo, self::SUFIXO_CENTAVOS) && is_int($valor)) {
            return [substr($campo, 0, -strlen(self::SUFIXO_CENTAVOS)), self::reais($valor)];
        }

        return [$campo, $valor];
    }

    /** 555 → "R$ 5,55"; -39999 → "- R$ 399,99" */
    public static function reais(int $centavos): string
    {
        $sinal = $centavos < 0 ? '- ' : '';

        return $sinal . 'R$ ' . number_format(abs($centavos) / 100, 2, ',', '.');
    }
}
