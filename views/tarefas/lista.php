<?php
/**
 * @var array  $tarefas
 * @var int    $total
 * @var int    $pagina
 * @var int    $paginas
 * @var int    $porPagina
 * @var string $busca
 */
?>

<div class="page-header animate-in" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
  <div>
    <h1 class="page-title">Tarefas Agendadas</h1>
    <p class="page-subtitle">Gerencie todas as tarefas do crontab do sistema</p>
  </div>
  <a href="index.php?rota=tarefas&acao=novo" class="btn btn-primary">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
      <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Nova Tarefa
  </a>
</div>

<!-- Barra de busca -->
<div class="card animate-in" style="padding:14px 16px;margin-bottom:12px">
  <form method="GET" action="index.php" style="display:flex;gap:8px;align-items:center">
    <input type="hidden" name="rota" value="tarefas">
    <div style="position:relative;flex:1">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
           style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>"
             class="form-input" style="padding-left:32px"
             placeholder="Buscar por nome, comando ou tag...">
    </div>
    <button type="submit" class="btn btn-secondary">Buscar</button>
    <?php if ($busca !== ''): ?>
    <a href="index.php?rota=tarefas" class="btn btn-ghost" title="Limpar busca">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </a>
    <?php endif; ?>
  </form>
</div>

<div class="card animate-in">
  <?php if (empty($tarefas) && $busca === ''): ?>
  <!-- Estado vazio sem busca -->
  <div class="empty-state">
    <div class="empty-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
      </svg>
    </div>
    <p class="empty-title">Nenhuma tarefa cadastrada</p>
    <p class="empty-desc">Crie sua primeira tarefa para comecar a agendar comandos no sistema.</p>
    <a href="index.php?rota=tarefas&acao=novo" class="btn btn-primary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Criar primeira tarefa
    </a>
  </div>

  <?php elseif (empty($tarefas)): ?>
  <!-- Estado vazio com busca -->
  <div class="empty-state">
    <div class="empty-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
    </div>
    <p class="empty-title">Nenhum resultado encontrado</p>
    <p class="empty-desc">Nenhuma tarefa corresponde a "<strong><?= htmlspecialchars($busca) ?></strong>".</p>
    <a href="index.php?rota=tarefas" class="btn btn-secondary">Limpar busca</a>
  </div>

  <?php else: ?>
  <!-- Contagem de resultados -->
  <div style="padding:10px 16px 0;font-size:12px;color:var(--text-muted)">
    <?php if ($busca !== ''): ?>
      <?= $total ?> resultado(s) para "<strong style="color:var(--text-secondary)"><?= htmlspecialchars($busca) ?></strong>"
    <?php else: ?>
      <?= $total ?> tarefa(s) no total
    <?php endif; ?>
  </div>

  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Tarefa</th>
          <th>Expressao Cron</th>
          <th>Comando</th>
          <th>Status</th>
          <th>Ativo</th>
          <th>Proxima Execucao</th>
          <th style="text-align:right">Acoes</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tarefas as $tarefa): ?>
        <tr data-row="<?= $tarefa['tar_id'] ?>">
          <td class="td-name">
            <?= htmlspecialchars($tarefa['tar_nome']) ?>
            <?php if ($tarefa['tar_tags']): ?>
            <small><?= htmlspecialchars($tarefa['tar_tags']) ?></small>
            <?php endif; ?>
          </td>
          <td>
            <span class="cron-pill" title="<?= htmlspecialchars($tarefa['descricao_cron']) ?>">
              <?= htmlspecialchars($tarefa['expressao_cron']) ?>
            </span>
          </td>
          <td style="max-width:200px">
            <code style="font-family:var(--font-mono);font-size:11.5px;color:var(--text-secondary);
                         white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block"
                  title="<?= htmlspecialchars($tarefa['tar_comando']) ?>">
              <?= htmlspecialchars($tarefa['tar_comando']) ?>
            </code>
          </td>
          <td>
            <?php
            $codigo = $tarefa['ultimo_codigo_saida'] ?? null;
            if (!$tarefa['tar_ativo']): ?>
              <span class="badge badge-muted js-status-badge"><span class="badge-dot"></span> Inativo</span>
            <?php elseif ($codigo === null): ?>
              <span class="badge badge-muted js-status-badge"><span class="badge-dot"></span> Sem dados</span>
            <?php elseif ($codigo == 0): ?>
              <span class="badge badge-green js-status-badge"><span class="badge-dot"></span> OK</span>
            <?php else: ?>
              <span class="badge badge-red js-status-badge"><span class="badge-dot"></span> Erro (<?= $codigo ?>)</span>
            <?php endif; ?>
          </td>
          <td>
            <label class="toggle" title="<?= $tarefa['tar_ativo'] ? 'Desativar' : 'Ativar' ?>">
              <input type="checkbox" class="js-toggle"
                     data-id="<?= $tarefa['tar_id'] ?>"
                     <?= $tarefa['tar_ativo'] ? 'checked' : '' ?>>
              <span class="toggle-slider"></span>
            </label>
          </td>
          <td style="font-size:12px;color:var(--text-secondary)">
            <?php if ($tarefa['proxima_execucao']): ?>
              <?= $tarefa['proxima_execucao']->format('d/m/Y H:i') ?>
            <?php else: ?>
              <span style="color:var(--text-muted)">--</span>
            <?php endif; ?>
          </td>
          <td style="text-align:right">
            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px">

              <!-- Executar agora -->
              <button type="button"
                      class="btn btn-ghost btn-icon js-executar-agora"
                      data-id="<?= $tarefa['tar_id'] ?>"
                      data-nome="<?= htmlspecialchars($tarefa['tar_nome'], ENT_QUOTES) ?>"
                      onclick="abrirModalExecutar(<?= (int)$tarefa['tar_id'] ?>, '<?= addslashes(htmlspecialchars($tarefa['tar_nome'], ENT_QUOTES)) ?>')"
                      title="Executar agora"
                      style="color:var(--gold)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polygon points="5 3 19 12 5 21 5 3"/>
                </svg>
              </button>

              <!-- Logs -->
              <a href="index.php?rota=logs&acao=index&id=<?= $tarefa['tar_id'] ?>"
                 class="btn btn-ghost btn-icon" title="Ver logs">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                  <polyline points="14 2 14 8 20 8"/>
                  <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                  <polyline points="10 9 9 9 8 9"/>
                </svg>
              </a>

              <!-- Editar -->
              <a href="index.php?rota=tarefas&acao=editar&id=<?= $tarefa['tar_id'] ?>"
                 class="btn btn-ghost btn-icon" title="Editar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
              </a>

              <!-- Excluir -->
              <a href="index.php?rota=tarefas&acao=excluir&id=<?= $tarefa['tar_id'] ?>"
                 class="btn btn-ghost btn-icon" title="Excluir"
                 style="color:var(--red);opacity:0.6"
                 onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                </svg>
              </a>

            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginacao -->
  <?php if ($paginas > 1): ?>
  <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;
              border-top:1px solid var(--border);font-size:12px">
    <span style="color:var(--text-muted)">
      Pagina <?= $pagina ?> de <?= $paginas ?>
    </span>
    <div style="display:flex;gap:4px">
      <?php
      $buscaParam = $busca !== '' ? '&busca=' . urlencode($busca) : '';

      // Anterior
      if ($pagina > 1): ?>
      <a href="index.php?rota=tarefas<?= $buscaParam ?>&pagina=<?= $pagina - 1 ?>"
         class="btn btn-ghost" style="font-size:12px;padding:4px 10px">
        &laquo; Anterior
      </a>
      <?php endif; ?>

      <?php
      // Numeros de pagina (mostra ate 5 ao redor da atual)
      $inicio = max(1, $pagina - 2);
      $fim    = min($paginas, $pagina + 2);
      for ($p = $inicio; $p <= $fim; $p++): ?>
      <a href="index.php?rota=tarefas<?= $buscaParam ?>&pagina=<?= $p ?>"
         class="btn <?= $p === $pagina ? 'btn-primary' : 'btn-ghost' ?>"
         style="font-size:12px;padding:4px 10px;min-width:32px;text-align:center">
        <?= $p ?>
      </a>
      <?php endfor; ?>

      <?php if ($pagina < $paginas): ?>
      <a href="index.php?rota=tarefas<?= $buscaParam ?>&pagina=<?= $pagina + 1 ?>"
         class="btn btn-ghost" style="font-size:12px;padding:4px 10px">
        Proxima &raquo;
      </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<!-- Modal de resultado do "Executar agora" -->
<div id="modalExecutar" style="display:none;position:fixed;inset:0;z-index:1000;
     background:rgba(0,0,0,0.7);align-items:center;justify-content:center">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);
              padding:24px;max-width:560px;width:90%;max-height:80vh;overflow-y:auto;
              box-shadow:0 24px 64px rgba(0,0,0,0.6)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <h3 id="modalTitulo" style="font-size:15px;font-weight:600;color:var(--text-primary)">Executando...</h3>
      <button onclick="fecharModal()" class="btn btn-ghost btn-icon">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <div id="modalConteudo">
      <div style="display:flex;align-items:center;gap:10px;color:var(--text-muted);font-size:13px">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             style="animation:spin 1s linear infinite">
          <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
        </svg>
        Aguarde, executando o comando...
      </div>
    </div>
  </div>
</div>
