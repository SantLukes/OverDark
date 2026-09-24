<?php

declare(strict_types=1);

namespace OverDark\Tests\Unit\Core;

use OverDark\Core\Database\SqlErrorReason;
use PDOException;
use PHPUnit\Framework\TestCase;

final class SqlErrorReasonTest extends TestCase
{
    public function testLockWaitTimeoutEhRetentavel(): void
    {
        $e = $this->erro('HY000', 1205, 'Lock wait timeout exceeded; try restarting transaction');

        self::assertSame('lock_wait_timeout', SqlErrorReason::de($e));
        self::assertSame(1205, SqlErrorReason::errno($e));
        self::assertTrue(SqlErrorReason::retentavel($e));
    }

    public function testErrnoTemPrioridadeSobreSqlstateGenerico(): void
    {
        self::assertSame('deadlock', SqlErrorReason::de($this->erro('40001', 1213, 'Deadlock found')));
        self::assertSame('registro_duplicado', SqlErrorReason::de($this->erro('23000', 1062, 'Duplicate entry')));
        self::assertFalse(SqlErrorReason::retentavel($this->erro('23000', 1062, 'Duplicate entry')));
    }

    public function testSqlstateDesconhecidoViraMotivoRastreavel(): void
    {
        self::assertSame('sqlstate_XX999', SqlErrorReason::de($this->erro('XX999', 9999, 'x')));
    }

    private function erro(string $sqlstate, int $errno, string $mensagem): PDOException
    {
        $e = new PDOException("SQLSTATE[$sqlstate]: $mensagem");
        $e->errorInfo = [$sqlstate, $errno, $mensagem];

        return $e;
    }
}
