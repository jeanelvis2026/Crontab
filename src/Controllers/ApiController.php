<?php
namespace CronManager\Controllers;

use CronManager\Helpers\Roteador;
use CronManager\Helpers\CronHelper;
use CronManager\Models\TarefaModel;
use CronManager\Models\ExecucaoModel;

class ApiController
{
    /** POST index.php?rota=api&acao=toggle -- alterna ativo/inativo */
    public function togglePost(): void
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        $id      = (int)($payload['id']   ?? 0);
        $ativo   = (int)($payload['ativo'] ?? 0);

        $model = new TarefaModel();
        if (!$model->buscarPorId($id)) {
            Roteador::json(array('erro' => 'Tarefa nao encontrada.'), 404);
        }

        $model->alternarAtivo($id, $ativo);
        Roteador::json(array('sucesso' => true, 'ativo' => $ativo));
    }

    /** GET index.php?rota=api&acao=validar -- valida expressao cron */
    public function validar(): void
    {
        $minuto    = $_GET['minuto']     ?? '*';
        $hora      = $_GET['hora']       ?? '*';
        $dia       = $_GET['dia']        ?? '*';
        $mes       = $_GET['mes']        ?? '*';
        $diaSemana = $_GET['dia_semana'] ?? '*';

        $resultado = CronHelper::validar($minuto, $hora, $dia, $mes, $diaSemana);

        $proxima = null;
        if ($resultado['valido']) {
            $dt = CronHelper::proximaExecucao($minuto, $hora, $dia, $mes, $diaSemana);
            $proxima = $dt ? $dt->format('d/m/Y H:i') : null;
        }

        Roteador::json(array(
            'valido'           => $resultado['valido'],
            'erros'            => $resultado['erros'],
            'proxima_execucao' => $proxima,
            'descricao'        => CronHelper::descricao($minuto, $hora, $dia, $mes, $diaSemana),
            'expressao'        => CronHelper::montar($minuto, $hora, $dia, $mes, $diaSemana),
        ));
    }

    /**
     * POST index.php?rota=api&acao=executarAgora
     * Executa imediatamente o comando de uma tarefa e registra o resultado.
     */
    public function executarAgoraPost(): void
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        $id      = (int)($payload['id'] ?? 0);

        $tarefaModel   = new TarefaModel();
        $execucaoModel = new ExecucaoModel();

        $tarefa = $tarefaModel->buscarPorId($id);
        if (!$tarefa) {
            Roteador::json(array('sucesso' => false, 'erro' => 'Tarefa nao encontrada.'), 404);
        }

        // Registrar inicio no banco e obter o ID da execucao
        $execucaoId = $execucaoModel->registrarInicio($id);

        // Montar caminho absoluto do executor_bg.php (mesmo diretorio do executor.php)
        $raiz  = dirname(dirname(__DIR__));
        $bgScript = $raiz . '/executor_bg.php';

        // Usar o PHP CLI diretamente (php8.1), pois /usr/bin/php pode apontar para o FPM
        if (is_executable('/usr/bin/php8.1')) {
            $phpBin = '/usr/bin/php8.1';
        } elseif (is_executable('/usr/bin/php7.4')) {
            $phpBin = '/usr/bin/php7.4';
        } else {
            $phpBin = '/usr/bin/php';
        }

        // Log de debug — usar diretorio do projeto (www-data tem permissao)
        $logDir = $raiz . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logBg  = $logDir . '/cronmgr_bg.log';
        $logCtl = $logDir . '/cronmgr_ctl.log';
        $ts     = date('Y-m-d H:i:s');

        // Gravar informacoes de debug antes de disparar
        file_put_contents($logCtl,
            "[{$ts}] phpBin={$phpBin}\n" .
            "[{$ts}] bgScript={$bgScript}\n" .
            "[{$ts}] id={$id} execucaoId={$execucaoId}\n",
            FILE_APPEND | LOCK_EX
        );

        // Disparar em background com nohup para desacoplar do processo PHP-FPM
        $cmd = 'nohup ' . $phpBin . ' ' . escapeshellarg($bgScript)
             . ' ' . escapeshellarg((string)$id)
             . ' ' . escapeshellarg((string)$execucaoId)
             . ' >> ' . escapeshellarg($logBg) . ' 2>&1 &';

        $output = array();
        $retval = null;
        exec($cmd, $output, $retval);

        file_put_contents($logCtl,
            "[{$ts}] cmd={$cmd}\n" .
            "[{$ts}] retval={$retval}\n",
            FILE_APPEND | LOCK_EX
        );

        // Retornar imediatamente ao browser informando que foi iniciado
        Roteador::json(array(
            'sucesso'      => true,
            'background'   => true,
            'execucao_id'  => $execucaoId,
            'mensagem'     => 'Tarefa iniciada em background. Acompanhe o resultado nos logs.',
        ));
    }
}
