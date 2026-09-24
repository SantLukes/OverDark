<?php

declare(strict_types=1);

namespace OverDark\Core\Database;

use PDOException;

/**
 * Traduz um erro do banco para um `motivo` legível no log
 * ("lock_wait_timeout" diz mais que "SQLSTATE[HY000] 1205").
 */
final class SqlErrorReason
{
    /** Códigos do MySQL (errno) — mais específicos que o SQLSTATE. */
    private const POR_ERRNO = [
        1205 => 'lock_wait_timeout',
        1213 => 'deadlock',
        1062 => 'registro_duplicado',
        1452 => 'referencia_inexistente',
        1406 => 'texto_maior_que_a_coluna',
        1264 => 'valor_fora_do_intervalo',
        1054 => 'coluna_inexistente',
        1146 => 'tabela_inexistente',
        2006 => 'conexao_perdida',
        2013 => 'conexao_perdida',
    ];

    private const POR_SQLSTATE = [
        '42S22' => 'coluna_inexistente',
        '42S02' => 'tabela_inexistente',
        '42000' => 'sql_invalido',
        '23000' => 'violacao_de_integridade',
        '22001' => 'texto_maior_que_a_coluna',
        '22003' => 'valor_fora_do_intervalo',
        '22007' => 'data_invalida',
        '40001' => 'deadlock',
    ];

    /** Falhas transitórias: tentar de novo em instantes costuma resolver. */
    private const RETENTAVEIS = [1205, 1213, 2006, 2013];

    public static function de(PDOException $e): string
    {
        return self::POR_ERRNO[self::errno($e)]
            ?? self::POR_SQLSTATE[self::sqlstate($e)]
            ?? 'sqlstate_' . self::sqlstate($e);
    }

    public static function retentavel(PDOException $e): bool
    {
        return in_array(self::errno($e), self::RETENTAVEIS, true);
    }

    public static function sqlstate(PDOException $e): string
    {
        $info = $e->errorInfo;

        if (is_array($info) && isset($info[0]) && is_string($info[0]) && $info[0] !== '') {
            return $info[0];
        }

        return (string) $e->getCode();
    }

    public static function errno(PDOException $e): ?int
    {
        $info = $e->errorInfo;

        return is_array($info) && isset($info[1]) && is_numeric($info[1]) ? (int) $info[1] : null;
    }
}
