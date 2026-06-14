0~<?php
/**
 * executor_bg.php
 * Executado em background pelo ApiController::executarAgoraPost()
 * Recebe: $argv[1] = tar_id, $argv[2] = exe_id
 * Executa o comando e registra o resultado no banco.
 * Compativel com PHP 7.0+
 */

if (php_sapi_name() !== 'cli') {
    exit(1);
}

$tarefaId   = isset($argv[1]) ? (int)$argv[1] : 0;
$execucaoId = isset($argv[2]) ? (int)$argv[2] : 0;

if ($tarefaId <= 0 || $execucaoId <= 0) {
    fwrite(STDERR, "[executor_bg] Argumentos invalidos: tar_id={$tarefaId} exe_id={$execucaoId}\n");
    exit(1);
}

// Carregar autoloader
$raiz = __DIR__;
require_once $raiz . '/src/autoload.php';

use CronManager\Database\Conexao;
use CronManager\Models\TarefaModel;
use CronManager\Models\ExecucaoModel;

$tarefaModel   = new TarefaModel();
$execucaoModel = new ExecucaoModel();

$tarefa = $tarefaModel->buscarPorId($tarefaId);
if (!$tarefa) {
    fwrite(STDERR, "[executor_bg] Tarefa #{$tarefaId} nao encontrada.\n");
    exit(1);
}

$comando = $tarefa['tar_comando'];

$inicio = microtime(true);

// Usar diretorio logs/ do projeto (www-data tem permissao de escrita)
$logsDir = $raiz . '/logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}

$pid    = getmypid();
$tmpOut = $logsDir . '/bg_out_' . $pid . '_' . $tarefaId . '.tmp';
$tmpErr = $logsDir . '/bg_err_' . $pid . '_' . $tarefaId . '.tmp';

$cmdSeguro = 'sh -c ' . escapeshellarg($comando)
           . ' > ' . escapeshellarg($tmpOut)
           . ' 2> ' . escapeshellarg($tmpErr);

$exitCode = null;
exec($cmdSeguro, $saida, $exitCode);

$duracaoMs = (int)round((microtime(true) - $inicio) * 1000);

$stdout = (file_exists($tmpOut)) ? file_get_contents($tmpOut) : '';
$stderr = (file_exists($tmpErr)) ? file_get_contents($tmpErr) : '';

@unlink($tmpOut);
@unlink($tmpErr);

$sucesso = ($exitCode === 0);

// Reconectar ao banco (conexao pode ter fechado durante execucao longa)
try {
    Conexao::reconectar();
} catch (Exception $e) {
    // ignora, tenta com a conexao existente
}
$execucaoModel = new ExecucaoModel();
$tarefaModel   = new TarefaModel();

// Registrar resultado no banco
$execucaoModel->registrarFim(
    $execucaoId,
    $exitCode,
    mb_substr($stdout, 0, 65535),
    mb_substr($stderr, 0, 65535),
    $duracaoMs
);

// Atualizar ultima execucao na tarefa
$tarefaModel->atualizarUltimaExecucao($tarefaId, $exitCode, $duracaoMs, $sucesso);

$status = $sucesso ? 'sucesso' : 'falha';
$ts     = date('Y-m-d H:i:s');
$logBg  = $logsDir . '/cronmgr_bg.log';
file_put_contents(
    $logBg,
    "[{$ts}] Tarefa #{$tarefaId} concluida -- exit: {$exitCode} | duracao: {$duracaoMs}ms | {$status}\n",
    FILE_APPEND
);

fwrite(STDOUT, "[executor_bg] Tarefa #{$tarefaId} concluida -- exit: {$exitCode} | duracao: {$duracaoMs}ms | {$status}\n");
exit(0);

