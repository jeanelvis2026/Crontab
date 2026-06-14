<?php
namespace CronManager\Helpers;

/**
 * Roteador minimalista baseado em $_GET['rota']
 */
class Roteador
{
    /** Resolve a rota atual e despacha para o controller correto */
    public static function despachar(): void
    {
        $rota  = $_GET['rota'] ?? 'dashboard';
        $acao  = $_GET['acao'] ?? 'index';
        $metodo = $_SERVER['REQUEST_METHOD'];

        // Mapeamento de rotas para controllers
        $mapa = [
            'dashboard' => \CronManager\Controllers\DashboardController::class,
            'tarefas'   => \CronManager\Controllers\TarefaController::class,
            'logs'      => \CronManager\Controllers\LogController::class,
            'api'       => \CronManager\Controllers\ApiController::class,
        ];

        if (!isset($mapa[$rota])) {
            http_response_code(404);
            self::renderizar('erros/404');
            return;
        }

        $controller = new $mapa[$rota]();

        // Método POST → acao_post, GET → acao
        $nomeMetodo = $metodo === 'POST' ? $acao . 'Post' : $acao;

        if (!method_exists($controller, $nomeMetodo)) {
            http_response_code(404);
            self::renderizar('erros/404');
            return;
        }

        $controller->$nomeMetodo();
    }

    /** Renderiza uma view com variáveis */
    public static function renderizar(string $view, array $dados = [], bool $layout = true): void
    {
        $arquivo = dirname(__DIR__, 2) . "/views/{$view}.php";

        if (!file_exists($arquivo)) {
            throw new \RuntimeException("View não encontrada: {$view}");
        }

        extract($dados, EXTR_SKIP);

        if ($layout) {
            $conteudo = function () use ($arquivo, $dados) {
                extract($dados, EXTR_SKIP);
                require $arquivo;
            };
            require dirname(__DIR__, 2) . '/views/layouts/principal.php';
        } else {
            require $arquivo;
        }
    }

    /** Redireciona para uma rota */
    public static function redirecionar(string $rota, string $acao = 'index', array $params = []): void
    {
        $url = "index.php?rota={$rota}&acao={$acao}";
        foreach ($params as $chave => $valor) {
            $url .= "&{$chave}=" . urlencode($valor);
        }
        header("Location: {$url}");
        exit;
    }

    /** Retorna JSON e encerra */
    public static function json(mixed $dados, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
