<?php
namespace CronManager\Controllers;

use CronManager\Helpers\Roteador;
use CronManager\Helpers\CronHelper;
use CronManager\Models\TarefaModel;
use CronManager\Models\ExecucaoModel;

class DashboardController
{
    public function index(): void
    {
        $tarefaModel   = new TarefaModel();
        $execucaoModel = new ExecucaoModel();

        $contagem  = $tarefaModel->contarPorStatus();
        $falhas24h = $tarefaModel->contarFalhas24h();
        $tarefas   = $tarefaModel->listarTodas();

        // Calcular próximas execuções para tarefas ativas
        $proximasExecucoes = [];
        foreach ($tarefas as $tarefa) {
            if (!$tarefa['tar_ativo']) continue;

            $proxima = CronHelper::proximaExecucao(
                $tarefa['tar_minuto'],
                $tarefa['tar_hora'],
                $tarefa['tar_dia'],
                $tarefa['tar_mes'],
                $tarefa['tar_dia_semana']
            );

            $proximasExecucoes[] = [
                'tarefa'   => $tarefa,
                'proxima'  => $proxima,
                'descricao'=> CronHelper::descricao(
                    $tarefa['tar_minuto'], $tarefa['tar_hora'],
                    $tarefa['tar_dia'], $tarefa['tar_mes'], $tarefa['tar_dia_semana']
                ),
            ];
        }

        // Ordenar por próxima execução
        usort($proximasExecucoes, fn($a, $b) => ($a['proxima'] ?? PHP_INT_MAX) <=> ($b['proxima'] ?? PHP_INT_MAX));

        Roteador::renderizar('dashboard/index', [
            'titulo'            => 'Dashboard',
            'contagem'          => $contagem,
            'falhas24h'         => $falhas24h,
            'proximasExecucoes' => array_slice($proximasExecucoes, 0, 10),
        ]);
    }
}
