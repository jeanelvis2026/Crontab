-- ============================================================
--  CronManager — Script de criação do banco de dados
--  Convenção de nomenclatura:
--    prefixo__tabela
--    prefixo__tabela_subtabela
--  Prefixos utilizados:
--    crn  → domínio principal (cron)
-- ============================================================

-- Banco de dados: crontab (já existente no servidor)
USE crontab;

-- ============================================================
--  crn__tarefas
--  Armazena cada tarefa agendada (job cron)
-- ============================================================
CREATE TABLE IF NOT EXISTS crn__tarefas (
    tar_id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    tar_nome        VARCHAR(120)    NOT NULL                        COMMENT 'Nome descritivo da tarefa',
    tar_minuto      VARCHAR(20)     NOT NULL DEFAULT '*'            COMMENT 'Campo minuto da expressão cron (0-59)',
    tar_hora        VARCHAR(20)     NOT NULL DEFAULT '*'            COMMENT 'Campo hora da expressão cron (0-23)',
    tar_dia         VARCHAR(20)     NOT NULL DEFAULT '*'            COMMENT 'Campo dia do mês (1-31)',
    tar_mes         VARCHAR(20)     NOT NULL DEFAULT '*'            COMMENT 'Campo mês (1-12)',
    tar_dia_semana  VARCHAR(20)     NOT NULL DEFAULT '*'            COMMENT 'Campo dia da semana (0=dom, 6=sab)',
    tar_comando     TEXT            NOT NULL                        COMMENT 'Comando shell a ser executado',
    tar_ativo       TINYINT(1)      NOT NULL DEFAULT 1              COMMENT '1=ativo, 0=inativo',
    tar_tags        VARCHAR(255)    NULL                            COMMENT 'Tags separadas por vírgula',
    tar_criado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tar_atualizado_em DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (tar_id),
    INDEX idx_tar_ativo (tar_ativo),
    INDEX idx_tar_criado_em (tar_criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tarefas agendadas do crontab';

-- ============================================================
--  crn__tarefas_execucoes
--  Histórico de execuções de cada tarefa (logs)
-- ============================================================
CREATE TABLE IF NOT EXISTS crn__tarefas_execucoes (
    exe_id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    exe_tar_id      INT UNSIGNED    NOT NULL                        COMMENT 'FK → crn__tarefas.tar_id',
    exe_iniciado_em DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Momento de início da execução',
    exe_finalizado_em DATETIME      NULL                            COMMENT 'Momento de término (NULL = em andamento)',
    exe_codigo_saida SMALLINT       NULL                            COMMENT 'Exit code do processo (0 = sucesso)',
    exe_stdout      MEDIUMTEXT      NULL                            COMMENT 'Saída padrão (stdout) do comando',
    exe_stderr      MEDIUMTEXT      NULL                            COMMENT 'Saída de erro (stderr) do comando',
    exe_duracao_ms  INT UNSIGNED    NULL                            COMMENT 'Duração total em milissegundos',
    PRIMARY KEY (exe_id),
    INDEX idx_exe_tar_id (exe_tar_id),
    INDEX idx_exe_iniciado_em (exe_iniciado_em),
    INDEX idx_exe_codigo_saida (exe_codigo_saida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Histórico de execuções das tarefas agendadas';

-- ============================================================
--  crn__configuracoes
--  Configurações gerais do sistema
-- ============================================================
CREATE TABLE IF NOT EXISTS crn__configuracoes (
    cfg_chave       VARCHAR(80)     NOT NULL                        COMMENT 'Chave única da configuração',
    cfg_valor       TEXT            NULL                            COMMENT 'Valor da configuração',
    cfg_descricao   VARCHAR(255)    NULL                            COMMENT 'Descrição do parâmetro',
    cfg_atualizado_em DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (cfg_chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configurações gerais do CronManager';

-- Valores padrão de configuração
INSERT INTO crn__configuracoes (cfg_chave, cfg_valor, cfg_descricao) VALUES
('retencao_logs_dias',   '90',       'Quantidade de dias para manter logs de execução'),
('timezone',             'America/Sao_Paulo', 'Fuso horário padrão do sistema'),
('versao',               '1.0.0',    'Versão do CronManager')
ON DUPLICATE KEY UPDATE cfg_valor = VALUES(cfg_valor);
