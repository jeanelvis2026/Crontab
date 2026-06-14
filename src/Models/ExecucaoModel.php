<?php
namespace CronManager\Models;

use CronManager\Database\Conexao;
use PDO;

/**
 * Model para a tabela crn__tarefas_execucoes
 */
class ExecucaoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexao::obter();
    }

    /** Lista execuções de uma tarefa com paginação */
    public function listarPorTarefa(int $tarefaId, int $limite = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM crn__tarefas_execucoes
            WHERE exe_tar_id = :id
            ORDER BY exe_iniciado_em DESC
            LIMIT :limite OFFSET :offset
        ");
        $stmt->bindValue(':id',     $tarefaId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite,   PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Conta total de execuções de uma tarefa */
    public function contarPorTarefa(int $tarefaId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM crn__tarefas_execucoes WHERE exe_tar_id = :id"
        );
        $stmt->execute([':id' => $tarefaId]);
        return (int) $stmt->fetchColumn();
    }

    /** Registra o início de uma execução e retorna o ID */
    public function registrarInicio(int $tarefaId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO crn__tarefas_execucoes (exe_tar_id, exe_iniciado_em)
            VALUES (:id, NOW())
        ");
        $stmt->execute([':id' => $tarefaId]);
        return (int) $this->db->lastInsertId();
    }

    /** Atualiza uma execução com o resultado final */
    public function registrarFim(int $execucaoId, int $codigoSaida, ?string $stdout, ?string $stderr, int $duracaoMs): bool
    {
        $stmt = $this->db->prepare("
            UPDATE crn__tarefas_execucoes SET
                exe_finalizado_em = NOW(),
                exe_codigo_saida  = :codigo,
                exe_stdout        = :stdout,
                exe_stderr        = :stderr,
                exe_duracao_ms    = :duracao
            WHERE exe_id = :id
        ");
        return $stmt->execute([
            ':id'     => $execucaoId,
            ':codigo' => $codigoSaida,
            ':stdout' => $stdout,
            ':stderr' => $stderr,
            ':duracao'=> $duracaoMs,
        ]);
    }

    /** Busca uma execução pelo ID */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM crn__tarefas_execucoes WHERE exe_id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /** Remove logs mais antigos que N dias */
    public function limparAntigos(int $dias = 90): int
    {
        $stmt = $this->db->prepare("
            DELETE FROM crn__tarefas_execucoes
            WHERE exe_iniciado_em < DATE_SUB(NOW(), INTERVAL :dias DAY)
        ");
        $stmt->execute([':dias' => $dias]);
        return $stmt->rowCount();
    }
}
