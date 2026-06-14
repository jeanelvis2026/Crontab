<?php
namespace CronManager\Controllers;

use CronManager\Helpers\Roteador;
use CronManager\Helpers\CronHelper;
use CronManager\Models\TarefaModel;
use CronManager\Models\ExecucaoModel;

class LogController
{
    public function index(): void
    {
        $tarefaId      = (int)($_GET['id'] ?? 0);
        $pagina        = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina     = 20;
        $offset        = ($pagina - 1) * $porPagina;

        $tarefaModel   = new TarefaModel();
        $execucaoModel = new ExecucaoModel();

        $tarefa = $tarefaModel->buscarPorId($tarefaId);
        if (!$tarefa) {
            $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Tarefa não encontrada.'];
            Roteador::redirecionar('tarefas');
            return;
        }

        $execucoes = $execucaoModel->listarPorTarefa($tarefaId, $porPagina, $offset);
        $total     = $execucaoModel->contarPorTarefa($tarefaId);
        $paginas   = (int)ceil($total / $porPagina);

        // Formatar duração
        foreach ($execucoes as &$exec) {
            $exec['duracao_formatada'] = CronHelper::formatarDuracao($exec['exe_duracao_ms']);
        }
        unset($exec);

        $tarefa['expressao_cron'] = CronHelper::montar(
            $tarefa['tar_minuto'], $tarefa['tar_hora'],
            $tarefa['tar_dia'],    $tarefa['tar_mes'],
            $tarefa['tar_dia_semana']
        );

        Roteador::renderizar('logs/lista', [
            'titulo'    => "Logs — {$tarefa['tar_nome']}",
            'tarefa'    => $tarefa,
            'execucoes' => $execucoes,
            'total'     => $total,
            'pagina'    => $pagina,
            'paginas'   => $paginas,
            'porPagina' => $porPagina,
        ]);
    }
}
