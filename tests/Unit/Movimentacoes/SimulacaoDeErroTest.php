<?php

declare(strict_types=1);

namespace OverDark\Tests\Unit\Movimentacoes;

use PHPUnit\Framework\TestCase;

/**
 * A simulação de erro de produção (demo de logs) precisa estar DESLIGADA
 * no código versionado. Se este teste falhar, comente a linha de volta em
 * PdoMovimentacaoRepository::salvar().
 */
final class SimulacaoDeErroTest extends TestCase
{
    public function testLinhaDeSimulacaoEstaComentada(): void
    {
        $codigo = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Modules/Movimentacoes/Infrastructure/PdoMovimentacaoRepository.php');

        self::assertMatchesRegularExpression(
            '/^\s*\/\/ \$rotinaConcorrente = /m',
            $codigo,
            'A linha de simulação deve existir e estar comentada.',
        );
        self::assertDoesNotMatchRegularExpression(
            '/^\s*\$rotinaConcorrente = /m',
            $codigo,
            'Simulação de erro LIGADA: comente a linha $rotinaConcorrente em PdoMovimentacaoRepository::salvar().',
        );
    }
}
