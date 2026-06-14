<?php
namespace CronManager\Models;

use CronManager\Database\Conexao;
use PDO;

/**
 * Model para a tabela crn__tarefas
 */
class TarefaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexao::obter();
    }

    /** Lista todas as tarefas com dados da última execução */
    public function listarTodas(): array
    {
        $sql = "
            SELECT
                t.*,
                e.exe_iniciado_em   AS ultima_execucao,
                e.exe_codigo_saida  AS ultimo_codigo_saida,
                e.exe_duracao_ms    AS ultima_duracao_ms
            FROM crn__tarefas t
            LEFT JOIN crn__tarefas_execucoes e
                ON e.exe_id = (
                    SELECT exe_id
                    FROM crn__tarefas_execucoes
                    WHERE exe_tar_id = t.tar_id
                    ORDER BY exe_iniciado_em DESC
                    LIMIT 1
                )
            ORDER BY t.tar_nome ASC
        ";
        return $this->db->query($sql)->fetchAll();
    }

    /** Busca uma tarefa pelo ID */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM crn__tarefas WHERE tar_id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /** Cria uma nova tarefa e retorna o ID gerado */
    public function criar(array $dados): int
    {
        $sql = "
            INSERT INTO crn__tarefas
                (tar_nome, tar_minuto, tar_hora, tar_dia, tar_mes, tar_dia_semana, tar_comando, tar_ativo, tar_tags)
            VALUES
                (:nome, :minuto, :hora, :dia, :mes, :dia_semana, :comando, :ativo, :tags)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nome'       => $dados['tar_nome'],
            ':minuto'     => $dados['tar_minuto'],
            ':hora'       => $dados['tar_hora'],
            ':dia'        => $dados['tar_dia'],
            ':mes'        => $dados['tar_mes'],
            ':dia_semana' => $dados['tar_dia_semana'],
            ':comando'    => $dados['tar_comando'],
            ':ativo'      => $dados['tar_ativo'] ?? 1,
            ':tags'       => $dados['tar_tags'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Atualiza uma tarefa existente */
    public function atualizar(int $id, array $dados): bool
    {
        $sql = "
            UPDATE crn__tarefas SET
                tar_nome        = :nome,
                tar_minuto      = :minuto,
                tar_hora        = :hora,
                tar_dia         = :dia,
                tar_mes         = :mes,
                tar_dia_semana  = :dia_semana,
                tar_comando     = :comando,
                tar_ativo       = :ativo,
                tar_tags        = :tags
            WHERE tar_id = :id
        ";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'         => $id,
            ':nome'       => $dados['tar_nome'],
            ':minuto'     => $dados['tar_minuto'],
            ':hora'       => $dados['tar_hora'],
            ':dia'        => $dados['tar_dia'],
            ':mes'        => $dados['tar_mes'],
            ':dia_semana' => $dados['tar_dia_semana'],
            ':comando'    => $dados['tar_comando'],
            ':ativo'      => $dados['tar_ativo'] ?? 1,
            ':tags'       => $dados['tar_tags'] ?? null,
        ]);
    }

    /** Alterna o status ativo/inativo de uma tarefa */
    public function alternarAtivo(int $id, int $ativo): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE crn__tarefas SET tar_ativo = :ativo WHERE tar_id = :id"
        );
        return $stmt->execute([':ativo' => $ativo, ':id' => $id]);
    }

    /** Exclui uma tarefa e seus logs em cascata */
    public function excluir(int $id): bool
    {
        // Excluir logs primeiro (sem FK constraint)
        $this->db->prepare(
            "DELETE FROM crn__tarefas_execucoes WHERE exe_tar_id = :id"
        )->execute([':id' => $id]);

        $stmt = $this->db->prepare(
            "DELETE FROM crn__tarefas WHERE tar_id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    /** Atualiza dados da ultima execucao na tarefa */
    public function atualizarUltimaExecucao(int $id, int $exitCode, int $duracaoMs, bool $sucesso): void
    {
        $stmt = $this->db->prepare("
            UPDATE crn__tarefas SET
                tar_ultima_execucao   = NOW(),
                tar_ultimo_exit_code  = :exit_code,
                tar_ultima_duracao_ms = :duracao,
                tar_ultima_sucesso    = :sucesso,
                tar_atualizado_em     = NOW()
            WHERE tar_id = :id
        ");
        $stmt->execute(array(
            ':exit_code' => $exitCode,
            ':duracao'   => $duracaoMs,
            ':sucesso'   => $sucesso ? 1 : 0,
            ':id'        => $id,
        ));
    }

    /** Lista tarefas com paginacao e busca */
    public function listarPaginado(string $busca = '', int $limite = 15, int $offset = 0): array
    {
        $where = '';
        $params = array();
        if ($busca !== '') {
            $where = "WHERE (t.tar_nome LIKE :busca OR t.tar_comando LIKE :busca OR t.tar_tags LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        $sql = "
            SELECT
                t.*,
                e.exe_iniciado_em   AS ultima_execucao,
                e.exe_codigo_saida  AS ultimo_codigo_saida,
                e.exe_duracao_ms    AS ultima_duracao_ms
            FROM crn__tarefas t
            LEFT JOIN crn__tarefas_execucoes e
                ON e.exe_id = (
                    SELECT exe_id FROM crn__tarefas_execucoes
                    WHERE exe_tar_id = t.tar_id
                    ORDER BY exe_iniciado_em DESC LIMIT 1
                )
            {$where}
            ORDER BY t.tar_nome ASC
            LIMIT :limite OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Conta total de tarefas com filtro de busca */
    public function contarTotal(string $busca = ''): int
    {
        $where = '';
        $params = array();
        if ($busca !== '') {
            $where = "WHERE (tar_nome LIKE :busca OR tar_comando LIKE :busca OR tar_tags LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM crn__tarefas {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Conta total de tarefas por status */
    public function contarPorStatus(): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total,
                SUM(tar_ativo = 1) AS ativos,
                SUM(tar_ativo = 0) AS inativos
            FROM crn__tarefas
        ";
        return $this->db->query($sql)->fetch();
    }

    /** Conta falhas nas últimas 24h */
    public function contarFalhas24h(): int
    {
        $sql = "
            SELECT COUNT(DISTINCT exe_tar_id) AS falhas
            FROM crn__tarefas_execucoes
            WHERE exe_codigo_saida != 0
              AND exe_codigo_saida IS NOT NULL
              AND exe_iniciado_em >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ";
        return (int) $this->db->query($sql)->fetchColumn();
    }
}

