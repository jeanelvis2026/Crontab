<?php
/** @var array $tarefa */
/** @var array $execucoes */
/** @var int   $total */
/** @var int   $pagina */
/** @var int   $paginas */
/** @var int   $porPagina */
use CronManager\Helpers\CronHelper;
?>

<div class="page-header animate-in" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
  <div style="display:flex;align-items:center;gap:12px">
    <a href="index.php?rota=tarefas" class="btn btn-ghost btn-icon">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </a>
    <div>
      <h1 class="page-title">Logs de Execução</h1>
      <p class="page-subtitle">
        <span style="color:var(--accent)"><?= htmlspecialchars($tarefa['tar_nome']) ?></span>
        &nbsp;·&nbsp;
        <span class="cron-pill" style="font-size:11px"><?= htmlspecialchars($tarefa['expressao_cron']) ?></span>
      </p>
    </div>
  </div>
  <div style="display:flex;align-items:center;gap:8px">
    <span style="font-size:12px;color:var(--text-muted)"><?= $total ?> execuções registradas</span>
    <a href="index.php?rota=tarefas&acao=editar&id=<?= $tarefa['tar_id'] ?>" class="btn btn-secondary btn-sm">Editar tarefa</a>
  </div>
</div>

<?php if (empty($execucoes)): ?>
<div class="card animate-in">
  <div class="empty-state">
    <div class="empty-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
      </svg>
    </div>
    <p class="empty-title">Nenhuma execução registrada</p>
    <p class="empty-desc">Os logs aparecerão aqui após a primeira execução da tarefa.</p>
  </div>
</div>
<?php else: ?>

<div class="animate-in">
  <?php foreach ($execucoes as $i => $exec): ?>
  <?php
    $sucesso  = isset($exec['exe_codigo_saida']) && $exec['exe_codigo_saida'] == 0;
    $falha    = isset($exec['exe_codigo_saida']) && $exec['exe_codigo_saida'] != 0;
    $andamento = !isset($exec['exe_finalizado_em']) || $exec['exe_finalizado_em'] === null;
    $dt = new DateTime($exec['exe_iniciado_em']);
  ?>
  <div class="log-entry">
    <div class="log-header">
      <!-- Status badge -->
      <?php if ($andamento): ?>
        <span class="badge badge-yellow"><span class="badge-dot"></span> Em andamento</span>
      <?php elseif ($sucesso): ?>
        <span class="badge badge-green"><span class="badge-dot"></span> Sucesso</span>
      <?php else: ?>
        <span class="badge badge-red"><span class="badge-dot"></span> Erro (<?= $exec['exe_codigo_saida'] ?>)</span>
      <?php endif; ?>

      <!-- Data/hora -->
      <span class="log-time"><?= $dt->format('d/m/Y H:i:s') ?></span>

      <!-- Duração -->
      <span class="log-duration"><?= $exec['duracao_formatada'] ?></span>

      <!-- Chevron -->
      <svg class="log-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="9 18 15 12 9 6"/>
      </svg>
    </div>

    <div class="log-body">
      <!-- STDOUT -->
      <div class="log-section-label">stdout</div>
      <div class="log-terminal <?= empty(trim($exec['exe_stdout'] ?? '')) ? 'empty' : '' ?>">
        <?php if (!empty(trim($exec['exe_stdout'] ?? ''))): ?>
          <?= htmlspecialchars($exec['exe_stdout']) ?>
        <?php else: ?>
          (sem saída)
        <?php endif; ?>
      </div>

      <!-- STDERR (só exibe se houver conteúdo) -->
      <?php if (!empty(trim($exec['exe_stderr'] ?? ''))): ?>
      <div class="log-section-label">stderr</div>
      <div class="log-terminal stderr"><?= htmlspecialchars($exec['exe_stderr']) ?></div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Paginação -->
<?php if ($paginas > 1): ?>
<div class="pagination">
  <a href="?rota=logs&acao=index&id=<?= $tarefa['tar_id'] ?>&pagina=<?= max(1, $pagina-1) ?>"
     class="page-link <?= $pagina <= 1 ? 'disabled' : '' ?>">← Anterior</a>

  <?php for ($p = max(1, $pagina-2); $p <= min($paginas, $pagina+2); $p++): ?>
  <a href="?rota=logs&acao=index&id=<?= $tarefa['tar_id'] ?>&pagina=<?= $p ?>"
     class="page-link <?= $p === $pagina ? 'active' : '' ?>"><?= $p ?></a>
  <?php endfor; ?>

  <a href="?rota=logs&acao=index&id=<?= $tarefa['tar_id'] ?>&pagina=<?= min($paginas, $pagina+1) ?>"
     class="page-link <?= $pagina >= $paginas ? 'disabled' : '' ?>">Próxima →</a>
</div>
<?php endif; ?>

<?php endif; ?>
