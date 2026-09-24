-- Valores monetários em centavos (BIGINT), espelhando o value object Money.
-- Compras parceladas geram uma linha por parcela, ligadas por grupo_parcelamento.
CREATE TABLE movimentacoes (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id          BIGINT UNSIGNED NOT NULL,
    tipo                VARCHAR(20)     NOT NULL,
    descricao           VARCHAR(160)    NOT NULL,
    valor_centavos      BIGINT          NOT NULL,
    data                DATE            NOT NULL,
    parcela_numero      TINYINT UNSIGNED NULL,
    parcelas_total      TINYINT UNSIGNED NULL,
    grupo_parcelamento  CHAR(32)        NULL,
    criado_em           DATETIME        NOT NULL,
    CONSTRAINT fk_movimentacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT ck_movimentacoes_tipo CHECK (tipo IN ('receita', 'gasto', 'cartao')),
    CONSTRAINT ck_movimentacoes_valor CHECK (valor_centavos > 0),
    INDEX ix_movimentacoes_usuario_data (usuario_id, data),
    INDEX ix_movimentacoes_grupo (grupo_parcelamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
