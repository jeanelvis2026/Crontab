0~<?php
/** @var array|null $tarefa */
/** @var array      $erros */
$editando = !empty($tarefa['tar_id']);
$acao     = $editando ? 'editar' : 'novo';

$v = fn(string $campo, string $default = '') =>
    htmlspecialchars($tarefa[$campo] ?? $default);
?>

<div class="page-header animate-in" style="display:flex;align-items:center;gap:12px">
  <a href="index.php?rota=tarefas" class="btn btn-ghost btn-icon">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="15 18 9 12 15 6"/>
    </svg>
  </a>
  <div>
    <h1 class="page-title"><?= $editando ? 'Editar Tarefa' : 'Nova Tarefa' ?></h1>
    <p class="page-subtitle"><?= $editando ? 'Altere os dados da tarefa agendada' : 'Configure uma nova tarefa para o crontab' ?></p>
  </div>
</div>

<div class="form-card animate-in">
  <form method="POST" action="index.php?rota=tarefas&acao=<?= $acao ?>" novalidate>
    <?php if ($editando): ?>
    <input type="hidden" name="tar_id" value="<?= (int)$tarefa['tar_id'] ?>">
    <?php endif; ?>

    <!-- Identificação -->
    <div class="form-section-title">Identificação</div>

    <div class="form-group">
      <label class="form-label" for="tar_nome">
        Nome da Tarefa <span class="required">*</span>
      </label>
      <input type="text" id="tar_nome" name="tar_nome"
             class="form-input <?= isset($erros['tar_nome']) ? 'error' : '' ?>"
             value="<?= $v('tar_nome') ?>"
             placeholder="Ex: Backup diário do banco de dados"
             autocomplete="off" required>
      <?php if (isset($erros['tar_nome'])): ?>
      <div class="form-error"><?= htmlspecialchars($erros['tar_nome']) ?></div>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label class="form-label" for="tar_tags">Tags</label>
      <input type="text" id="tar_tags" name="tar_tags"
             class="form-input"
             value="<?= $v('tar_tags') ?>"
             placeholder="Ex: backup, banco, diário">
      <div class="form-hint">Separe as tags por vírgula para facilitar a organização.</div>
    </div>

    <!-- Agendamento -->
    <div class="form-section-title" style="margin-top:24px">Agendamento (Expressão Cron)</div>

    <!-- Presets -->
    <div class="presets-row">
      <span style="font-size:11px;color:var(--text-muted);align-self:center">Presets:</span>
      <button type="button" class="preset-btn" data-preset="* * * * *">A cada minuto</button>
      <button type="button" class="preset-btn" data-preset="0 * * * *">A cada hora</button>
      <button type="button" class="preset-btn" data-preset="0 0 * * *">Diariamente</button>
      <button type="button" class="preset-btn" data-preset="0 0 * * 0">Semanalmente</button>
      <button type="button" class="preset-btn" data-preset="0 0 1 * *">Mensalmente</button>
      <button type="button" class="preset-btn" data-preset="0 2 * * *">Todo dia às 2h</button>
      <button type="button" class="preset-btn" data-preset="*/5 * * * *">A cada 5 min</button>
      <button type="button" class="preset-btn" data-preset="0 8 * * 1-5">Dias úteis 8h</button>
    </div>

    <!-- Campos individuais -->
    <div class="cron-grid">
      <?php
      $campos = [
        ['tar_minuto',     'Minuto',       '0-59',  $v('tar_minuto', '*')],
        ['tar_hora',       'Hora',         '0-23',  $v('tar_hora', '*')],
        ['tar_dia',        'Dia',          '1-31',  $v('tar_dia', '*')],
        ['tar_mes',        'Mês',          '1-12',  $v('tar_mes', '*')],
        ['tar_dia_semana', 'Dia Semana',   '0-7',   $v('tar_dia_semana', '*')],
      ];
      foreach ($campos as [$name, $label, $range, $val]):
        $hasError = isset($erros[str_replace('tar_', '', $name)]);
      ?>
      <div>
        <div class="cron-field-label"><?= $label ?></div>
        <input type="text" name="<?= $name ?>"
               class="form-input <?= $hasError ? 'error' : '' ?>"
               style="text-align:center;font-family:var(--font-mono)"
               value="<?= $val ?>"
               placeholder="*"
               title="<?= $label ?> (<?= $range ?>)">
        <?php if ($hasError): ?>
        <div class="form-error" style="font-size:10px"><?= htmlspecialchars($erros[str_replace('tar_', '', $name)]) ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Preview da expressão -->
    <div class="cron-preview" id="cron-preview">
      <span class="cron-preview-expr">
        <?= $v('tar_minuto','*') ?> <?= $v('tar_hora','*') ?> <?= $v('tar_dia','*') ?> <?= $v('tar_mes','*') ?> <?= $v('tar_dia_semana','*') ?>
      </span>
      <span class="cron-preview-desc">Calculando…</span>
      <span class="cron-preview-next"></span>
    </div>

    <!-- Comando -->
    <div class="form-section-title" style="margin-top:24px">Comando</div>

    <!-- Select de exemplos de comandos -->
    <div class="form-group">
      <label class="form-label" for="selectExemplo">Exemplos de comandos</label>
      <div style="display:flex;gap:8px;align-items:center">
        <select id="selectExemplo" class="form-select" style="flex:1"
          onchange="(function(sel){
            var idx = sel.selectedIndex;
            if(idx===0) return;
            var cmd = sel.options[idx].value;
            if(!cmd) return;
            var ta = document.getElementById('tar_comando');
            if(!ta) return;
            ta.value = cmd;
            ta.focus();
            ta.style.borderColor='#c8a96e';
            ta.style.boxShadow='0 0 0 3px rgba(200,169,110,0.2)';
            setTimeout(function(){ ta.style.borderColor=''; ta.style.boxShadow=''; }, 1400);
            setTimeout(function(){ sel.selectedIndex=0; }, 800);
          })(this)">
          <option value="">— Selecione um exemplo para preencher o comando —</option>

          <optgroup label="🌐 Requisição HTTP (URL externa)">
            <option value="/usr/bin/curl -s https://exemplo.com/api/ping > /dev/null">curl — Visitar URL externa (silencioso)</option>
            <option value="/usr/bin/curl -s -o /dev/null -w &quot;%{http_code}&quot; https://exemplo.com/health">curl — Verificar status HTTP de URL externa</option>
            <option value="/usr/bin/curl -s -X POST https://exemplo.com/webhook -H 'Content-Type: application/json' -d '{&quot;evento&quot;:&quot;cron&quot;}' > /dev/null">curl — Disparar webhook externo via POST</option>
          </optgroup>

          <optgroup label="🖥️ Página interna / localhost">
            <option value="/usr/bin/curl -s http://localhost/cron/processar > /dev/null">curl — Chamar rota interna da aplicação</option>
            <option value="/usr/bin/curl -s http://127.0.0.1:8080/api/jobs/executar > /dev/null">curl — Chamar API interna em porta específica</option>
            <option value="/usr/bin/wget -q -O /dev/null http://localhost/tarefas/executar">wget — Visitar página interna</option>
          </optgroup>

          <optgroup label="🐘 Scripts PHP">
            <option value="/usr/bin/php /var/www/html/script.php">php — Executar script PHP diretamente</option>
            <option value="/usr/bin/php /var/www/html/artisan schedule:run >> /var/log/laravel-cron.log 2>&1">php — Laravel Artisan schedule:run com log</option>
            <option value="/usr/bin/php /var/www/html/bin/console messenger:consume --time-limit=60 >> /dev/null 2>&1">php — Symfony Console Command</option>
            <option value="/usr/bin/php /var/www/html/wp-cron.php">php — WordPress Cron manual</option>
          </optgroup>

          <optgroup label="🐚 Scripts Shell / Bash">
            <option value="/bin/bash /var/scripts/backup.sh >> /var/log/backup.log 2>&1">bash — Executar script com log</option>
            <option value="/bin/bash /var/scripts/limpeza.sh > /dev/null 2>&1">bash — Script de limpeza (saída descartada)</option>
          </optgroup>

          <optgroup label="🐍 Python / Node.js">
            <option value="/usr/bin/python3 /var/scripts/relatorio.py >> /var/log/relatorio.log 2>&1">python3 — Executar script com log</option>
            <option value="/usr/bin/node /var/scripts/notificacoes.js > /dev/null 2>&1">node — Executar script Node.js</option>
          </optgroup>

          <optgroup label="🗄️ Banco de dados">
            <option value="/usr/bin/mysqldump -u root -pSENHA banco_dados | gzip > /backups/db_$(date +\%Y\%m\%d).sql.gz">mysqldump — Backup MySQL comprimido com data</option>
            <option value="find /backups -name '*.sql.gz' -mtime +30 -delete">find — Remover backups com mais de 30 dias</option>
          </optgroup>

          <optgroup label="⚙️ Utilitários do sistema">
            <option value="find /var/log -name '*.log' -mtime +7 -exec truncate -s 0 {} \;">find — Limpar logs com mais de 7 dias</option>
            <option value="/usr/sbin/logrotate -f /etc/logrotate.conf > /dev/null 2>&1">logrotate — Rotacionar logs do sistema</option>
            <option value="/usr/bin/certbot renew --quiet >> /var/log/certbot.log 2>&1">certbot — Renovar certificado SSL Let's Encrypt</option>
          </optgroup>
        </select>
      </div>
      <div class="form-hint">Selecione um exemplo para preencher automaticamente o campo abaixo. Edite conforme necessário.</div>
    </div>

    <div class="form-group">
      <label class="form-label" for="tar_comando">
        Comando Shell <span class="required">*</span>
      </label>
      <textarea id="tar_comando" name="tar_comando"
                class="form-textarea <?= isset($erros['tar_comando']) ? 'error' : '' ?>"
                rows="3"
                placeholder="/usr/bin/php /var/www/html/artisan schedule:run >> /dev/null 2>&1"><?= $v('tar_comando') ?></textarea>
      <?php if (isset($erros['tar_comando'])): ?>
      <div class="form-error"><?= htmlspecialchars($erros['tar_comando']) ?></div>
      <?php endif; ?>
      <div class="form-hint">Use o caminho absoluto do executável para garantir compatibilidade. Clique em um exemplo acima para preenchê-lo automaticamente.</div>
    </div>

    <!-- Status -->
    <div class="checkbox-row">
      <label class="toggle">
        <input type="checkbox" name="tar_ativo" id="tar_ativo"
               <?= ($tarefa['tar_ativo'] ?? 1) ? 'checked' : '' ?>>
        <span class="toggle-slider"></span>
      </label>
      <label class="checkbox-label" for="tar_ativo">Tarefa ativa</label>
    </div>

    <!-- Ações -->
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
          <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
        </svg>
        <?= $editando ? 'Salvar Alterações' : 'Criar Tarefa' ?>
      </button>
      <a href="index.php?rota=tarefas" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>

