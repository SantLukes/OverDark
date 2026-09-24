<?php

declare(strict_types=1);

namespace OverDark\Core\Database;

use PDO;

/**
 * Executa, em ordem alfabética, os arquivos .sql de database/migrations
 * que ainda não constam na tabela `migrations`.
 */
final class Migrator
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $path,
    ) {
    }

    /**
     * @return list<string> migrations executadas nesta chamada
     */
    public function migrate(): array
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                nome VARCHAR(190) PRIMARY KEY,
                executada_em DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $executadas = $this->coluna('SELECT nome FROM migrations');
        $arquivos = glob($this->path . '/*.sql') ?: [];
        sort($arquivos);

        $novas = [];

        foreach ($arquivos as $arquivo) {
            $nome = basename($arquivo);

            if (in_array($nome, $executadas, true)) {
                continue;
            }

            $this->pdo->exec((string) file_get_contents($arquivo));
            $this->pdo->prepare('INSERT INTO migrations (nome, executada_em) VALUES (?, NOW())')->execute([$nome]);
            $novas[] = $nome;
        }

        return $novas;
    }

    /**
     * Apaga todas as tabelas do banco atual. Usado apenas pelo banco de testes.
     */
    public function dropAll(): void
    {
        $tabelas = $this->coluna('SHOW TABLES');

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tabelas as $tabela) {
            $this->pdo->exec(sprintf('DROP TABLE `%s`', str_replace('`', '', $tabela)));
        }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * @return list<string>
     */
    private function coluna(string $sql): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return array_values(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
    }
}
