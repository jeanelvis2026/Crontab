<?php
/** @var array $contagem */
/** @var int   $falhas24h */
/** @var array $proximasExecucoes */
use CronManager\Helpers\CronHelper;
?>

<div class="page-header animate-in">
  <h1 class="page-title">Dashboard</h1>
  <p class="page-subtitle">Visão geral do sistema de tarefas agendadas</p>
</div>

<!-- Cards de estatísticas -->
<div class="stats-grid">
  <div class="stat-card animate-in stagger-1">
    <div>
      <div class="stat-label">Total de Tarefas</div>
      <div class="stat-value"><?= (int)($contagem['total'] ?? 0) ?></div>
    </div>
    <div class="stat-icon amber">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
      </svg>
    </div>
  </div>

  <div class="stat-card animate-in stagger-2">
    <div>
      <div class="stat-label">Tarefas Ativas</div>
      <div class="stat-value"><?= (int)($contagem['ativos'] ?? 0) ?></div>
    </div>
    <div class="stat-icon green">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
      </svg>
    </div>
  </div>

  <div class="stat-card animate-in stagger-3">
    <div>
      <div class="stat-label">Tarefas Inativas</div>
      <div class="stat-value"><?= (int)($contagem['inativos'] ?? 0) ?></div>
    </div>
    <div class="stat-icon muted">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
      </svg>
    </div>
  </div>

  <div class="stat-card animate-in stagger-4">
    <div>
      <div class="stat-label">Falhas (24h)</div>
      <div class="stat-value" style="color: <?= $falhas24h > 0 ? 'var(--red)' : 'var(--text-primary)' ?>">
        <?= $falhas24h ?>
      </div>
    </div>
    <div class="stat-icon <?= $falhas24h > 0 ? 'red' : 'muted' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
        <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
      </svg>
    </div>
  </div>
</div>

<!-- Próximas execuções -->
<div class="card animate-in">
  <div class="card-header">
    <span class="card-title">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
      </svg>
      Próximas Execuções
    </span>
    <a href="index.php?rota=tarefas" class="btn btn-ghost btn-sm">Ver todas →</a>
  </div>

  <?php if (empty($proximasExecucoes)): ?>
  <div class="empty-state">
    <div class="empty-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
      </svg>
    </div>
    <p class="empty-title">Nenhuma tarefa ativa</p>
    <p class="empty-desc">Crie e ative tarefas para visualizar as próximas execuções aqui.</p>
    <a href="index.php?rota=tarefas&acao=novo" class="btn btn-secondary btn-sm">Criar tarefa</a>
  </div>
  <?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Tarefa</th>
          <th>Agendamento</th>
          <th>Próxima Execução</th>
          <th>Última Execução</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($proximasExecucoes as $item): ?>
        <?php $t = $item['tarefa']; ?>
        <tr>
          <td class="td-name">
            <?= htmlspecialchars($t['tar_nome']) ?>
            <?php if ($t['tar_tags']): ?>
            <small><?= htmlspecialchars($t['tar_tags']) ?></small>
            <?php endif; ?>
          </td>
          <td>
            <span class="cron-pill"><?= htmlspecialchars(CronHelper::montar($t['tar_minuto'],$t['tar_hora'],$t['tar_dia'],$t['tar_mes'],$t['tar_dia_semana'])) ?></span>
          </td>
          <td>
            <?php if ($item['proxima']): ?>
              <span style="font-size:13px;color:var(--text-primary)"><?= $item['proxima']->format('d/m H:i') ?></span>
              <small style="display:block;font-size:11px;color:var(--text-muted)"><?= self_relative($item['proxima']) ?></small>
            <?php else: ?>
              <span style="color:var(--text-muted);font-size:12px">—</span>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;color:var(--text-secondary)">
            <?= $t['ultima_execucao'] ? (new DateTime($t['ultima_execucao']))->format('d/m H:i') : '<span style="color:var(--text-muted)">Nunca</span>' ?>
          </td>
          <td>
            <?php
            $codigo = $t['ultimo_codigo_saida'] ?? null;
            if ($codigo === null): ?>
              <span class="badge badge-muted"><span class="badge-dot"></span> Sem dados</span>
            <?php elseif ($codigo == 0): ?>
              <span class="badge badge-green"><span class="badge-dot"></span> OK</span>
            <?php else: ?>
              <span class="badge badge-red"><span class="badge-dot"></span> Erro (<?= $codigo ?>)</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="index.php?rota=logs&acao=index&id=<?= $t['tar_id'] ?>" class="btn btn-ghost btn-sm">Logs</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php
function self_relative(\DateTime $dt): string {
    $diff = $dt->getTimestamp() - time();
    if ($diff < 60)   return "em {$diff}s";
    if ($diff < 3600) return 'em ' . round($diff/60) . 'min';
    if ($diff < 86400) return 'em ' . round($diff/3600) . 'h';
    return 'em ' . round($diff/86400) . ' dias';
}
?>
