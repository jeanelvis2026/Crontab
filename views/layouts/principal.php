<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($titulo ?? 'CronManager') ?> — CronManager</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="sidebar-logo">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 6 12 12 16 14"/>
      </svg>
    </div>
    <div>
      <span class="sidebar-brand-name">CronManager</span>
      <span class="sidebar-brand-sub">root@system</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <span class="sidebar-section-label">Navegação</span>

    <a href="index.php?rota=dashboard" class="sidebar-link <?= (($_GET['rota'] ?? 'dashboard') === 'dashboard') ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
      </svg>
      Dashboard
    </a>

    <a href="index.php?rota=tarefas" class="sidebar-link <?= (($_GET['rota'] ?? '') === 'tarefas') ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
      </svg>
      Tarefas
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="system-status">
      <span class="status-dot"></span>
      Sistema ativo
    </div>
  </div>
</aside>

<!-- Conteúdo principal -->
<div class="main-wrapper">

  <!-- Topbar -->
  <header class="topbar">
    <button class="topbar-menu-btn" id="menuToggle" aria-label="Menu">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
    <div class="topbar-breadcrumb">
      <span class="topbar-page"><?= htmlspecialchars($titulo ?? '') ?></span>
    </div>
    <?php if (($_GET['rota'] ?? 'dashboard') !== 'dashboard'): ?>
    <a href="index.php?rota=tarefas&acao=novo" class="btn btn-primary btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Nova Tarefa
    </a>
    <?php else: ?>
    <a href="index.php?rota=tarefas&acao=novo" class="btn btn-primary btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Nova Tarefa
    </a>
    <?php endif; ?>
  </header>

  <!-- Mensagem de sessão -->
  <?php if (!empty($_SESSION['mensagem'])): ?>
  <div class="alert alert-<?= $_SESSION['mensagem']['tipo'] ?>" id="alertMsg">
    <?= htmlspecialchars($_SESSION['mensagem']['texto']) ?>
    <button class="alert-close" onclick="this.parentElement.remove()">×</button>
  </div>
  <?php unset($_SESSION['mensagem']); endif; ?>

  <!-- Conteúdo da página -->
  <main class="page-content">
    <?php $conteudo(); ?>
  </main>

</div><!-- /.main-wrapper -->

<script src="assets/js/app.js"></script>
</body>
</html>
