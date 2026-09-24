<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Application;

use DateTimeImmutable;
use InvalidArgumentException;
use OverDark\Core\Database\SqlErrorReason;
use OverDark\Core\Validation\ValidationException;
use OverDark\Modules\Movimentacoes\Domain\Movimentacao;
use OverDark\Modules\Movimentacoes\Domain\MovimentacaoRepository;
use OverDark\Modules\Movimentacoes\Domain\Parcela;
use OverDark\Modules\Movimentacoes\Domain\Parcelamento;
use OverDark\Modules\Movimentacoes\Domain\TipoMovimentacao;
use OverDark\Shared\Domain\Money;
use OverDark\Shared\Formatting\MoneyParser;
use PDOException;
use Psr\Log\LoggerInterface;

/**
 * Caso de uso: registrar uma movimentação.
 *
 * Compra parcelada no cartão vira N movimentações (uma por mês), ligadas pelo
 * mesmo grupo de parcelamento.
 */
final class RegistrarMovimentacao
{
    private const OPERACAO = 'registrar_movimentacao';

    public function __construct(
        private readonly MovimentacaoRepository $repository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return list<Movimentacao> movimentações criadas (1 ou N parcelas), com id
     *
     * @throws ValidationException
     * @throws FalhaAoRegistrarMovimentacao quando os dados são válidos, mas o banco recusa a gravação
     */
    public function executar(int $usuarioId, NovaMovimentacao $dados): array
    {
        $erros = [];

        $tipo = TipoMovimentacao::tryFrom($dados->tipo);
        if ($tipo === null) {
            $erros['tipo'] = 'Selecione um tipo válido.';
        }

        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $dados->data);
        if ($data === false || $data->format('Y-m-d') !== $dados->data) {
            $erros['data'] = 'Informe uma data válida.';
        }

        $descricao = trim($dados->descricao);
        if ($descricao === '') {
            $erros['descricao'] = 'Informe uma descrição.';
        } elseif (mb_strlen($descricao) > Movimentacao::DESCRICAO_MAXIMA) {
            $erros['descricao'] = sprintf('Use no máximo %d caracteres.', Movimentacao::DESCRICAO_MAXIMA);
        }

        $valor = $this->valorPositivo($dados->valor, 'valor', $erros);

        $parcelas = 1;
        $valorParcela = null;

        if ($tipo !== null && $tipo->permiteParcelamento()) {
            $parcelas = (int) $dados->parcelas;

            if ($parcelas !== 1 && !in_array($parcelas, Parcelamento::OPCOES, true)) {
                $erros['parcelas'] = 'Selecione uma quantidade de parcelas válida.';
            }

            if ($parcelas > 1 && trim($dados->valorParcela) !== '') {
                $valorParcela = $this->valorPositivo($dados->valorParcela, 'valor_parcela', $erros);
            }
        }

        if ($erros !== [] || $tipo === null || $data === false || $valor === null) {
            // Registramos QUAIS campos falharam, nunca o que o usuário digitou.
            $this->logger->warning('movimentacao_validacao_recusada', [
                'operacao' => self::OPERACAO,
                'status' => 422,
                'motivo' => 'dados_invalidos',
                'campos' => array_keys($erros),
                'tipo' => $dados->tipo,
            ]);

            throw new ValidationException($erros);
        }

        $movimentacoes = $parcelas > 1
            ? $this->parcelar($tipo, $descricao, new Parcelamento($valor, $parcelas, $valorParcela), $data)
            : [new Movimentacao($tipo, $descricao, $valor, $data)];

        $inicio = hrtime(true);

        try {
            $salvas = $this->repository->salvar($usuarioId, $movimentacoes);
        } catch (PDOException $e) {
            // Aqui temos o contexto de negócio que o kernel não tem: quem, o quê,
            // quanto e quantas parcelas. Por isso o erro é logado AQUI, uma única vez.
            $this->logger->error('movimentacao_registro_falhou', [
                'operacao' => self::OPERACAO,
                'status' => 500,
                'usuario_id' => $usuarioId,
                'tipo' => $tipo->value,
                'parcelas' => count($movimentacoes),
                'valor_centavos' => $valor->cents,
                'data' => $data->format('Y-m-d'),
                'motivo' => SqlErrorReason::de($e),
                'integracao' => 'MySQL',
                'sqlstate' => SqlErrorReason::sqlstate($e),
                'errno' => SqlErrorReason::errno($e),
                'retentavel' => SqlErrorReason::retentavel($e),
                'duracao_ms' => round((hrtime(true) - $inicio) / 1e6),
                'exception' => $e,
            ]);

            throw new FalhaAoRegistrarMovimentacao($e);
        }

        $this->registrarSucesso($usuarioId, $salvas, $valor);

        return $salvas;
    }

    /**
     * @param list<Movimentacao> $salvas
     */
    private function registrarSucesso(int $usuarioId, array $salvas, Money $valorInformado): void
    {
        $primeira = $salvas[0];
        $ultima = $salvas[count($salvas) - 1];

        if ($primeira->parcela === null) {
            $this->logger->info('movimentacao_registrada', [
                'operacao' => self::OPERACAO,
                'status' => 'SUCCESS',
                'usuario_id' => $usuarioId,
                'movimentacao_id' => $primeira->id,
                'tipo' => $primeira->tipo->value,
                'valor_centavos' => $primeira->valor->cents,
                'data' => $primeira->data->format('Y-m-d'),
                'competencia' => $primeira->competencia()->chave(),
            ]);

            return;
        }

        $this->logger->info('compra_parcelada_registrada', [
            'operacao' => self::OPERACAO,
            'status' => 'SUCCESS',
            'usuario_id' => $usuarioId,
            'grupo_parcelamento' => $primeira->parcela->grupo,
            'movimentacao_ids' => array_map(static fn (Movimentacao $m): ?int => $m->id, $salvas),
            'parcelas' => count($salvas),
            'valor_total_centavos' => Money::sum(...array_map(static fn (Movimentacao $m): Money => $m->valor, $salvas))->cents,
            'valor_informado_centavos' => $valorInformado->cents,
            'primeira_competencia' => $primeira->competencia()->chave(),
            'ultima_competencia' => $ultima->competencia()->chave(),
        ]);
    }

    /**
     * @return list<Movimentacao>
     */
    private function parcelar(TipoMovimentacao $tipo, string $descricao, Parcelamento $parcelamento, DateTimeImmutable $dataCompra): array
    {
        $grupo = bin2hex(random_bytes(16));
        $valores = $parcelamento->valores();
        $datas = $parcelamento->datas($dataCompra);
        $movimentacoes = [];

        foreach ($valores as $i => $valor) {
            $movimentacoes[] = new Movimentacao(
                $tipo,
                $descricao,
                $valor,
                $datas[$i],
                new Parcela($i + 1, $parcelamento->quantidade, $grupo),
            );
        }

        return $movimentacoes;
    }

    /**
     * @param array<string, string> $erros
     */
    private function valorPositivo(string $entrada, string $campo, array &$erros): ?Money
    {
        try {
            $valor = MoneyParser::parse($entrada);
        } catch (InvalidArgumentException) {
            $erros[$campo] = 'Informe um valor válido (ex.: 1.234,56).';

            return null;
        }

        if (!$valor->isPositive()) {
            $erros[$campo] = 'O valor deve ser maior que zero.';

            return null;
        }

        return $valor;
    }
}
