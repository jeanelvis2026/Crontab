<?php
/**
 * CronManager -- executor.php
 * Compativel com PHP 7.0+
 *
 * Adicione ao crontab do root:
 *   * * * * * /usr/bin/php /var/www/html/executor.php >> /var/log/executor.log 2>&1
 */

declare(strict_types=1);

define('CRONMANAGER_ROOT', __DIR__);
define('CRONMANAGER_TIMEZONE', 'America/Sao_Paulo');
define('CRONMANAGER_MAX_EXEC_SECONDS', 300);

date_default_timezone_set(CRONMANAGER_TIMEZONE);

$agora = new DateTime('now', new DateTimeZone(CRONMANAGER_TIMEZONE));

// -- Log -----------------------------------------------------------------------

function log_executor($msg)
{
    $ts = date('Y-m-d H:i:s');
    echo "[{$ts}] {$msg}" . PHP_EOL;
}

// -- Conexao com o banco -------------------------------------------------------

$cfg = require CRONMANAGER_ROOT . '/config/banco.php';

try {
    $dsn = "mysql:host={$cfg['host']};port={$cfg['porta']};dbname={$cfg['banco']};charset={$cfg['charset']}";
    $pdo = new PDO($dsn, $cfg['usuario'], $cfg['senha'], array(
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 10,
    ));
} catch (PDOException $e) {
    log_executor("ERRO: Falha ao conectar ao banco -- " . $e->getMessage());
    exit(1);
}

// -- Verificacao de campo cron -------------------------------------------------

/**
 * Verifica se um valor numerico satisfaz um campo da expressao cron.
 * Suporta: *, n, n-m, n/step, n-m/step, listas com virgula
 */
function campo_match($valor, $campo)
{
    $valor = (int)$valor;

    if ($campo === '*') {
        return true;
    }

    $partes = explode(',', $campo);
    foreach ($partes as $parte) {
        $parte = trim($parte);

        // Step: */n ou n-m/n
        if (strpos($parte, '/') !== false) {
            $segmentos = explode('/', $parte, 2);
            $range     = $segmentos[0];
            $step      = (int)$segmentos[1];
            if ($step < 1) {
                continue;
            }
            if ($range === '*') {
                if ($valor % $step === 0) {
                    return true;
                }
            } elseif (strpos($range, '-') !== false) {
                $limites = explode('-', $range);
                $ini     = (int)$limites[0];
                $fim     = (int)$limites[1];
                if ($valor >= $ini && $valor <= $fim && ($valor - $ini) % $step === 0) {
                    return true;
                }
            }
            continue;
        }

        // Range: n-m
        if (strpos($parte, '-') !== false) {
            $limites = explode('-', $parte);
            $ini     = (int)$limites[0];
            $fim     = (int)$limites[1];
            if ($valor >= $ini && $valor <= $fim) {
                return true;
            }
            continue;
        }

        // Valor simples
        if (is_numeric($parte) && (int)$parte === $valor) {
            return true;
        }
    }

    return false;
}

/**
 * Verifica se a tarefa deve ser executada no minuto atual
 */
function deve_executar($tarefa, $agora)
{
    return campo_match($agora->format('i'), $tarefa['tar_minuto'])
        && campo_match($agora->format('G'), $tarefa['tar_hora'])
        && campo_match($agora->format('j'), $tarefa['tar_dia'])
        && campo_match($agora->format('n'), $tarefa['tar_mes'])
        && campo_match($agora->format('w'), $tarefa['tar_dia_semana']);
}

// -- Buscar tarefas ativas -----------------------------------------------------

try {
    $stmt = $pdo->query("
        SELECT tar_id, tar_nome, tar_minuto, tar_hora, tar_dia, tar_mes,
               tar_dia_semana, tar_comando
        FROM crn__tarefas
        WHERE tar_ativo = 1
        ORDER BY tar_id ASC
    ");
    $tarefas = $stmt->fetchAll();
} catch (PDOException $e) {
    log_executor("ERRO: Falha ao buscar tarefas -- " . $e->getMessage());
    exit(1);
}

if (empty($tarefas)) {
    log_executor("Nenhuma tarefa ativa encontrada.");
    exit(0);
}

log_executor("Verificando " . count($tarefas) . " tarefa(s) ativa(s) -- " . $agora->format('Y-m-d H:i'));

// -- Executar tarefas elegiveis ------------------------------------------------

$executadas = 0;

foreach ($tarefas as $tarefa) {
    if (!deve_executar($tarefa, $agora)) {
        continue;
    }

    $id      = (int)$tarefa['tar_id'];
    $nome    = $tarefa['tar_nome'];
    $comando = $tarefa['tar_comando'];

    log_executor("Iniciando tarefa #{$id} -- {$nome}");

    $inicio = microtime(true);

    // Arquivos temporarios para capturar stdout e stderr
    $tmpStdout = tempnam(sys_get_temp_dir(), 'cronmgr_out_');
    $tmpStderr = tempnam(sys_get_temp_dir(), 'cronmgr_err_');

    $cmdSeguro = 'timeout ' . CRONMANAGER_MAX_EXEC_SECONDS
               . ' sh -c ' . escapeshellarg($comando)
               . ' > ' . escapeshellarg($tmpStdout)
               . ' 2> ' . escapeshellarg($tmpStderr);

    $exitCode = null;
    exec($cmdSeguro, $saida, $exitCode);

    $duracaoMs = (int)round((microtime(true) - $inicio) * 1000);

    $stdout = ($tmpStdout && file_exists($tmpStdout)) ? file_get_contents($tmpStdout) : '';
    $stderr = ($tmpStderr && file_exists($tmpStderr)) ? file_get_contents($tmpStderr) : '';

    @unlink($tmpStdout);
    @unlink($tmpStderr);

    $sucesso = ($exitCode === 0);

    log_executor(sprintf(
        "Tarefa #%d concluida -- exit: %d | duracao: %dms | sucesso: %s",
        $id, $exitCode, $duracaoMs, $sucesso ? 'sim' : 'nao'
    ));

    if (!empty(trim($stderr))) {
        log_executor("  STDERR: " . substr(trim($stderr), 0, 200));
    }

    // -- Registrar no banco ----------------------------------------------------

    try {
        $iniciado   = new DateTime('@' . (int)$inicio);
        $iniciado->setTimezone(new DateTimeZone(CRONMANAGER_TIMEZONE));
        $finalizado = new DateTime('now', new DateTimeZone(CRONMANAGER_TIMEZONE));

        $stmtLog = $pdo->prepare("
            INSERT INTO crn__tarefas_execucoes
                (exe_tar_id, exe_iniciado_em, exe_finalizado_em,
                 exe_duracao_ms, exe_codigo_saida, exe_stdout, exe_stderr)
            VALUES
                (:tarefa_id, :iniciado, :finalizado,
                 :duracao, :exit_code, :stdout, :stderr)
        ");

        $stmtLog->execute(array(
            ':tarefa_id'  => $id,
            ':iniciado'   => $iniciado->format('Y-m-d H:i:s'),
            ':finalizado' => $finalizado->format('Y-m-d H:i:s'),
            ':duracao'    => $duracaoMs,
            ':exit_code'  => $exitCode,
            ':stdout'     => mb_substr($stdout, 0, 65535),
            ':stderr'     => mb_substr($stderr, 0, 65535),
        ));

        $stmtUpd = $pdo->prepare("
            UPDATE crn__tarefas SET
                tar_ultima_execucao   = :ultima,
                tar_ultimo_exit_code  = :exit_code,
                tar_ultima_duracao_ms = :duracao,
                tar_ultima_sucesso    = :sucesso,
                tar_atualizado_em     = NOW()
            WHERE tar_id = :id
        ");

        $stmtUpd->execute(array(
            ':ultima'    => $finalizado->format('Y-m-d H:i:s'),
            ':exit_code' => $exitCode,
            ':duracao'   => $duracaoMs,
            ':sucesso'   => $sucesso ? 1 : 0,
            ':id'        => $id,
        ));

    } catch (PDOException $e) {
        log_executor("ERRO ao registrar execucao da tarefa #{$id}: " . $e->getMessage());
    }

    $executadas++;
}

log_executor("Concluido -- {$executadas} tarefa(s) executada(s) neste ciclo.");
exit(0);
