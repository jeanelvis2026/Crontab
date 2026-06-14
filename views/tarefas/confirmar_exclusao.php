0~<?php
/** @var array $tarefa */
use CronManager\Helpers\CronHelper;
$expressao = CronHelper::montar(
    $tarefa['tar_minuto'], $tarefa['tar_hora'],
    $tarefa['tar_dia'],    $tarefa['tar_mes'],
    $tarefa['tar_dia_semana']
);
?>

<div class="page-header animate-in" style="display:flex;align-items:center;gap:12px">
  <a href="index.php?rota=tarefas" class="btn btn-ghost btn-icon">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="15 18 9 12 15 6"/>
    </svg>
  </a>
  <div>
    <h1 class="page-title">Confirmar Exclusão</h1>
    <p class="page-subtitle">Esta ação é irreversível</p>
  </div>
</div>

<div class="confirm-card animate-in">
  <div class="confirm-icon">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="3 6 5 6 21 6"/>
      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
      <line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>
    </svg>
  </div>

  <h2 class="confirm-title">Excluir "<?= htmlspecialchars($tarefa['tar_nome']) ?>"?</h2>
  <p class="confirm-desc">
    Você está prestes a excluir permanentemente a tarefa
    <strong style="color:var(--text-primary)"><?= htmlspecialchars($tarefa['tar_nome']) ?></strong>
    e todo o seu histórico de execuções. Esta ação <strong style="color:var(--red)">não pode ser desfeita</strong>.
  </p>

  <!-- Detalhes da tarefa -->
  <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius-sm);
              padding:12px 14px;margin-bottom:20px;font-size:12px">
    <div style="display:flex;gap:8px;margin-bottom:6px">
      <span style="color:var(--text-muted);min-width:80px">Expressão:</span>
      <span class="cron-pill" style="font-size:11px"><?= htmlspecialchars($expressao) ?></span>
    </div>
    <div style="display:flex;gap:8px">
      <span style="color:var(--text-muted);min-width:80px">Comando:</span>
      <code style="font-family:var(--font-mono);color:var(--text-secondary);font-size:11px;
                   word-break:break-all"><?= htmlspecialchars($tarefa['tar_comando']) ?></code>
    </div>
  </div>

  <!-- Confirmação por digitação -->
  <div class="confirm-input-row">
    <p class="confirm-input-hint">
      Para confirmar, digite <strong>CONFIRMAR</strong> no campo abaixo:
    </p>
    <input type="text" id="confirmInput" class="form-input"
           placeholder="Digite CONFIRMAR" autocomplete="off">
  </div>

  <form method="POST" action="index.php?rota=tarefas&acao=excluir">
    <input type="hidden" name="tar_id" value="<?= (int)$tarefa['tar_id'] ?>">
    <input type="hidden" name="confirmacao" id="confirmacaoHidden" value="">

    <div style="display:flex;gap:10px">
      <button type="submit" id="confirmButton" class="btn btn-danger"
              disabled style="opacity:0.4"
              onclick="document.getElementById('confirmacaoHidden').value=document.getElementById('confirmInput').value">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="3 6 5 6 21 6"/>
          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
        </svg>
        Excluir Definitivamente
      </button>
      <a href="index.php?rota=tarefas" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>

