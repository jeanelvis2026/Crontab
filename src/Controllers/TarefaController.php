<?php
namespace CronManager\Controllers;

use CronManager\Helpers\Roteador;
use CronManager\Helpers\CronHelper;
use CronManager\Models\TarefaModel;

class TarefaController
{
    private TarefaModel $model;

    public function __construct()
    {
        $this->model = new TarefaModel();
    }

    /** Lista tarefas com busca e paginacao */
    public function index(): void
    {
        $porPagina = 15;
        $pagina    = max(1, (int)($_GET['pagina'] ?? 1));
        $busca     = trim($_GET['busca'] ?? '');
        $offset    = ($pagina - 1) * $porPagina;

        $total   = $this->model->contarTotal($busca);
        $paginas = $total > 0 ? (int)ceil($total / $porPagina) : 1;
        $pagina  = min($pagina, $paginas);
        $offset  = ($pagina - 1) * $porPagina;

        $tarefas = $this->model->listarPaginado($busca, $porPagina, $offset);

        foreach ($tarefas as &$tarefa) {
            $tarefa['proxima_execucao'] = $tarefa['tar_ativo']
                ? CronHelper::proximaExecucao(
                    $tarefa['tar_minuto'], $tarefa['tar_hora'],
                    $tarefa['tar_dia'],    $tarefa['tar_mes'],
                    $tarefa['tar_dia_semana']
                )
                : null;

            $tarefa['descricao_cron'] = CronHelper::descricao(
                $tarefa['tar_minuto'], $tarefa['tar_hora'],
                $tarefa['tar_dia'],    $tarefa['tar_mes'],
                $tarefa['tar_dia_semana']
            );

            $tarefa['expressao_cron'] = CronHelper::montar(
                $tarefa['tar_minuto'], $tarefa['tar_hora'],
                $tarefa['tar_dia'],    $tarefa['tar_mes'],
                $tarefa['tar_dia_semana']
            );

            $tarefa['duracao_formatada'] = CronHelper::formatarDuracao($tarefa['ultima_duracao_ms'] ?? null);
        }
        unset($tarefa);

        Roteador::renderizar('tarefas/lista', array(
            'titulo'    => 'Tarefas Agendadas',
            'tarefas'   => $tarefas,
            'total'     => $total,
            'pagina'    => $pagina,
            'paginas'   => $paginas,
            'porPagina' => $porPagina,
            'busca'     => $busca,
        ));
    }

    /** Formulário de nova tarefa */
    public function novo(): void
    {
        Roteador::renderizar('tarefas/formulario', [
            'titulo'  => 'Nova Tarefa',
            'tarefa'  => null,
            'erros'   => [],
        ]);
    }

    /** Salva nova tarefa (POST) */
    public function novoPost(): void
    {
        $dados = $this->extrairDadosPost();
        $validacao = CronHelper::validar(
            $dados['tar_minuto'], $dados['tar_hora'],
            $dados['tar_dia'],    $dados['tar_mes'],
            $dados['tar_dia_semana']
        );

        $erros = $validacao['erros'];
        if (empty(trim($dados['tar_nome']))) {
            $erros['tar_nome'] = 'O nome da tarefa é obrigatório.';
        }
        if (empty(trim($dados['tar_comando']))) {
            $erros['tar_comando'] = 'O comando é obrigatório.';
        }

        if (!empty($erros)) {
            Roteador::renderizar('tarefas/formulario', [
                'titulo' => 'Nova Tarefa',
                'tarefa' => $dados,
                'erros'  => $erros,
            ]);
            return;
        }

        $id = $this->model->criar($dados);
        $_SESSION['mensagem'] = ['tipo' => 'sucesso', 'texto' => 'Tarefa criada com sucesso!'];
        Roteador::redirecionar('tarefas');
    }

    /** Formulário de edição */
    public function editar(): void
    {
        $id     = (int)($_GET['id'] ?? 0);
        $tarefa = $this->model->buscarPorId($id);

        if (!$tarefa) {
            $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Tarefa não encontrada.'];
            Roteador::redirecionar('tarefas');
            return;
        }

        Roteador::renderizar('tarefas/formulario', [
            'titulo' => 'Editar Tarefa',
            'tarefa' => $tarefa,
            'erros'  => [],
        ]);
    }

    /** Salva edição (POST) */
    public function editarPost(): void
    {
        $id     = (int)($_POST['tar_id'] ?? 0);
        $tarefa = $this->model->buscarPorId($id);

        if (!$tarefa) {
            Roteador::redirecionar('tarefas');
            return;
        }

        $dados = $this->extrairDadosPost();
        $validacao = CronHelper::validar(
            $dados['tar_minuto'], $dados['tar_hora'],
            $dados['tar_dia'],    $dados['tar_mes'],
            $dados['tar_dia_semana']
        );

        $erros = $validacao['erros'];
        if (empty(trim($dados['tar_nome']))) {
            $erros['tar_nome'] = 'O nome da tarefa é obrigatório.';
        }
        if (empty(trim($dados['tar_comando']))) {
            $erros['tar_comando'] = 'O comando é obrigatório.';
        }

        if (!empty($erros)) {
            $dados['tar_id'] = $id;
            Roteador::renderizar('tarefas/formulario', [
                'titulo' => 'Editar Tarefa',
                'tarefa' => $dados,
                'erros'  => $erros,
            ]);
            return;
        }

        $this->model->atualizar($id, $dados);
        $_SESSION['mensagem'] = ['tipo' => 'sucesso', 'texto' => 'Tarefa atualizada com sucesso!'];
        Roteador::redirecionar('tarefas');
    }

    /** Confirmação de exclusão */
    public function excluir(): void
    {
        $id     = (int)($_GET['id'] ?? 0);
        $tarefa = $this->model->buscarPorId($id);

        if (!$tarefa) {
            Roteador::redirecionar('tarefas');
            return;
        }

        Roteador::renderizar('tarefas/confirmar_exclusao', [
            'titulo' => 'Confirmar Exclusão',
            'tarefa' => $tarefa,
        ]);
    }

    /** Executa a exclusão (POST) */
    public function excluirPost(): void
    {
        $id            = (int)($_POST['tar_id'] ?? 0);
        $confirmacao   = $_POST['confirmacao'] ?? '';

        if ($confirmacao !== 'CONFIRMAR') {
            $_SESSION['mensagem'] = ['tipo' => 'aviso', 'texto' => 'Exclusão cancelada.'];
            Roteador::redirecionar('tarefas');
            return;
        }

        $tarefa = $this->model->buscarPorId($id);
        if ($tarefa) {
            $this->model->excluir($id);
            $_SESSION['mensagem'] = ['tipo' => 'sucesso', 'texto' => "Tarefa \"{$tarefa['tar_nome']}\" excluída com sucesso."];
        }

        Roteador::redirecionar('tarefas');
    }

    /** Extrai e sanitiza dados do POST */
    private function extrairDadosPost(): array
    {
        return [
            'tar_nome'       => trim($_POST['tar_nome'] ?? ''),
            'tar_minuto'     => trim($_POST['tar_minuto'] ?? '*'),
            'tar_hora'       => trim($_POST['tar_hora'] ?? '*'),
            'tar_dia'        => trim($_POST['tar_dia'] ?? '*'),
            'tar_mes'        => trim($_POST['tar_mes'] ?? '*'),
            'tar_dia_semana' => trim($_POST['tar_dia_semana'] ?? '*'),
            'tar_comando'    => trim($_POST['tar_comando'] ?? ''),
            'tar_ativo'      => isset($_POST['tar_ativo']) ? 1 : 0,
            'tar_tags'       => trim($_POST['tar_tags'] ?? ''),
        ];
    }
}
