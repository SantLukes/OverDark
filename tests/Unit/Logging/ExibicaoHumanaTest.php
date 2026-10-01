<?php

declare(strict_types=1);

namespace OverDark\Tests\Unit\Logging;

use OverDark\Core\Logging\ExibicaoHumana;
use PHPUnit\Framework\TestCase;

final class ExibicaoHumanaTest extends TestCase
{
    public function testCentavosViramReaisComNomeSemSufixo(): void
    {
        self::assertSame(['valor', 'R$ 5,55'], ExibicaoHumana::campo('valor_centavos', 555));
        self::assertSame(['valor_total', 'R$ 3.999,99'], ExibicaoHumana::campo('valor_total_centavos', 399999));
        self::assertSame(['valor', '- R$ 120,00'], ExibicaoHumana::campo('valor_centavos', -12000));
        self::assertSame(['valor', 'R$ 0,00'], ExibicaoHumana::campo('valor_centavos', 0));
    }

    public function testDemaisCamposNaoMudam(): void
    {
        self::assertSame(['parcelas', 10], ExibicaoHumana::campo('parcelas', 10));
        self::assertSame(['valor_centavos', null], ExibicaoHumana::campo('valor_centavos', null));
    }
}
