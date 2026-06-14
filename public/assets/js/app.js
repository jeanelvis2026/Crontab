/* CronManager — app.js */
'use strict';

document.addEventListener('DOMContentLoaded', function () {

  /* ── Sidebar mobile ──────────────────────────────────────── */
  var menuBtn = document.getElementById('menuToggle');
  var sidebar = document.getElementById('sidebar');

  if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (!sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  /* ── Toggle ativo/inativo via API ────────────────────────── */
  document.querySelectorAll('.js-toggle').forEach(function (input) {
    input.addEventListener('change', function () {
      var id    = this.dataset.id;
      var ativo = this.checked ? 1 : 0;
      var row   = this.closest('tr') || this.closest('[data-row]');
      var self  = this;

      fetch('index.php?rota=api&acao=toggle', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: parseInt(id), ativo: ativo }),
      })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data.sucesso) throw new Error(data.erro || 'Erro desconhecido');
        showToast(ativo ? 'Tarefa ativada' : 'Tarefa desativada', 'sucesso');
      })
      .catch(function (err) {
        self.checked = !self.checked;
        showToast('Erro ao alterar status: ' + err.message, 'erro');
      });
    });
  });

  /* ── Validação de cron em tempo real ─────────────────────── */
  var cronInputNames = ['tar_minuto', 'tar_hora', 'tar_dia', 'tar_mes', 'tar_dia_semana'];
  var previewEl      = document.getElementById('cron-preview');
  var validationTimer;

  cronInputNames.forEach(function (name) {
    var el = document.querySelector('[name="' + name + '"]');
    if (el) {
      el.addEventListener('input', function () {
        clearTimeout(validationTimer);
        validationTimer = setTimeout(validarCron, 350);
      });
    }
  });

  function validarCron() {
    var vals = {};
    cronInputNames.forEach(function (name) {
      var el = document.querySelector('[name="' + name + '"]');
      vals[name.replace('tar_', '')] = el ? (el.value.trim() || '*') : '*';
    });

    var params = 'minuto=' + encodeURIComponent(vals.minuto)
               + '&hora='  + encodeURIComponent(vals.hora)
               + '&dia='   + encodeURIComponent(vals.dia)
               + '&mes='   + encodeURIComponent(vals.mes)
               + '&dia_semana=' + encodeURIComponent(vals.dia_semana);

    fetch('index.php?rota=api&acao=validar&' + params)
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (previewEl) {
          var exprEl = previewEl.querySelector('.cron-preview-expr');
          var descEl = previewEl.querySelector('.cron-preview-desc');
          var nextEl = previewEl.querySelector('.cron-preview-next');
          if (exprEl) exprEl.textContent = data.expressao;
          if (descEl) descEl.textContent = data.descricao;
          if (nextEl) nextEl.textContent = data.proxima_execucao ? 'Próxima: ' + data.proxima_execucao : '';
          previewEl.style.borderColor = data.valido ? 'rgba(74,222,128,0.3)' : 'rgba(248,113,113,0.3)';
        }
        cronInputNames.forEach(function (name) {
          var el = document.querySelector('[name="' + name + '"]');
          if (el) {
            el.classList.remove('error');
            var errEl = el.parentElement && el.parentElement.querySelector('.form-error');
            if (errEl) errEl.remove();
          }
        });
        if (!data.valido) {
          var campoMap = { minuto:'tar_minuto', hora:'tar_hora', dia:'tar_dia', mes:'tar_mes', dia_semana:'tar_dia_semana' };
          Object.keys(data.erros).forEach(function (campo) {
            var inputName = campoMap[campo] || campo;
            var el = document.querySelector('[name="' + inputName + '"]');
            if (el) {
              el.classList.add('error');
              if (!el.parentElement.querySelector('.form-error')) {
                var errEl = document.createElement('div');
                errEl.className   = 'form-error';
                errEl.textContent = data.erros[campo];
                el.parentElement.appendChild(errEl);
              }
            }
          });
        }
      })
      .catch(function () {});
  }

  if (document.querySelector('[name="tar_minuto"]')) {
    validarCron();
  }

  /* ── Presets de agendamento ──────────────────────────────── */
  document.querySelectorAll('.preset-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var preset = btn.dataset.preset.split(' ');
      var names  = ['tar_minuto', 'tar_hora', 'tar_dia', 'tar_mes', 'tar_dia_semana'];
      names.forEach(function (name, i) {
        var el = document.querySelector('[name="' + name + '"]');
        if (el && preset[i] !== undefined) el.value = preset[i];
      });
      validarCron();
    });
  });

  /* ── Select de exemplos de comandos ─────────────────────── */
  var selectExemplo = document.getElementById('selectExemplo');
  var cmdTextarea   = document.getElementById('tar_comando');

  if (selectExemplo && cmdTextarea) {
    selectExemplo.addEventListener('change', function () {
      var idx = this.selectedIndex;
      if (idx === 0) return; // placeholder selecionado

      var cmd = this.options[idx].value;
      if (!cmd) return;

      // Preencher o textarea
      cmdTextarea.value = cmd;

      // Feedback visual
      cmdTextarea.style.borderColor = '#c8a96e';
      cmdTextarea.style.boxShadow   = '0 0 0 3px rgba(200,169,110,0.2)';
      cmdTextarea.focus();
      cmdTextarea.scrollIntoView({ behavior: 'smooth', block: 'center' });

      setTimeout(function () {
        cmdTextarea.style.borderColor = '';
        cmdTextarea.style.boxShadow   = '';
      }, 1400);

      // Resetar select para placeholder
      var sel = this;
      setTimeout(function () { sel.selectedIndex = 0; }, 800);

      showToast('Exemplo aplicado — edite conforme necessário', 'sucesso');
    });
  }

  /* ── Executar agora ────────────────────────────────────── */
  document.querySelectorAll('.js-executar-agora').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id   = parseInt(this.dataset.id);
      var nome = this.dataset.nome;
      abrirModalExecutar(id, nome);
    });
  });

  /* ── Expansão de logs ────────────────────────────────────── */
  document.querySelectorAll('.log-header').forEach(function (header) {
    header.addEventListener('click', function () {
      var entry = header.closest('.log-entry');
      if (entry) entry.classList.toggle('open');
    });
  });

  /* ── Confirmação de exclusão: habilitar botão ────────────── */
  var confirmInput  = document.getElementById('confirmInput');
  var confirmButton = document.getElementById('confirmButton');

  if (confirmInput && confirmButton) {
    confirmInput.addEventListener('input', function () {
      var ok = confirmInput.value === 'CONFIRMAR';
      confirmButton.disabled      = !ok;
      confirmButton.style.opacity = ok ? '1' : '0.4';
    });
  }

  /* ── Toast notification ──────────────────────────────────── */
  var toastStyle = document.createElement('style');
  toastStyle.textContent = [
    '.toast{position:fixed;bottom:24px;right:24px;padding:10px 18px;border-radius:8px;',
    'font-size:13px;font-weight:500;z-index:9999;opacity:0;transform:translateY(8px);',
    'transition:all 0.25s cubic-bezier(0.23,1,0.32,1);pointer-events:none;',
    'box-shadow:0 8px 24px rgba(0,0,0,0.5);}',
    '.toast.visible{opacity:1;transform:translateY(0);}',
    '.toast-sucesso{background:#1a2e1a;color:#4ade80;border:1px solid rgba(74,222,128,0.3);}',
    '.toast-erro{background:#2e1a1a;color:#f87171;border:1px solid rgba(248,113,113,0.3);}',
    '.toast-aviso{background:#2e2a1a;color:#fbbf24;border:1px solid rgba(251,191,36,0.3);}'
  ].join('');
  document.head.appendChild(toastStyle);

}); /* fim DOMContentLoaded */

/* Executar agora: modal global */
function fecharModal() {
  var modal = document.getElementById('modalExecutar');
  if (modal) modal.style.display = 'none';
}

function abrirModalExecutar(id, nome) {
  var modal     = document.getElementById('modalExecutar');
  var titulo    = document.getElementById('modalTitulo');
  var conteudo  = document.getElementById('modalConteudo');
  if (!modal) return;

  titulo.textContent = 'Executando: ' + nome;
  conteudo.innerHTML = '<div style="display:flex;align-items:center;gap:10px;color:var(--text-muted);font-size:13px">'
    + '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
    + ' style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>'
    + 'Aguarde, executando o comando...</div>';
  modal.style.display = 'flex';

  fetch('index.php?rota=api&acao=executarAgora', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: id }),
  })
  .then(function (res) { return res.json(); })
  .then(function (data) {
    var html = '';

    if (data.background) {
      // Execucao em background: retornou imediatamente
      html += '<div style="display:flex;align-items:center;gap:8px;font-size:14px;font-weight:600;color:#fbbf24;margin-bottom:10px">';
      html += '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
      html += 'Tarefa iniciada em background</div>';
      html += '<p style="font-size:13px;color:var(--text-secondary);margin:0 0 16px">';
      html += 'O comando foi disparado e esta sendo executado em segundo plano. ';
      html += 'O resultado sera registrado automaticamente nos logs ao concluir.</p>';
    } else {
      var cor   = data.sucesso ? '#4ade80' : '#f87171';
      var icone = data.sucesso
        ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>'
        : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
      html += '<div style="display:flex;align-items:center;gap:8px;font-size:14px;font-weight:600;color:' + cor + ';margin-bottom:6px">' + icone + (data.sucesso ? 'Executado com sucesso' : 'Falha na execucao') + '</div>';
      html += '<div style="font-size:12px;color:var(--text-muted);display:flex;gap:16px;margin-bottom:12px">';
      html += '<span>Exit code: <strong style="color:var(--text-secondary)">' + data.exit_code + '</strong></span>';
      html += '<span>Duracao: <strong style="color:var(--text-secondary)">' + data.duracao_ms + 'ms</strong></span>';
      html += '</div>';
      if (data.stdout && data.stdout.trim()) {
        html += '<div style="margin-bottom:8px"><div style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">stdout</div>';
        html += '<pre style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:6px;padding:10px;font-size:11px;font-family:var(--font-mono);color:#4ade80;white-space:pre-wrap;word-break:break-all;max-height:200px;overflow-y:auto;margin:0">' + escHtml(data.stdout) + '</pre></div>';
      }
      if (data.stderr && data.stderr.trim()) {
        html += '<div style="margin-bottom:8px"><div style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">stderr</div>';
        html += '<pre style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:6px;padding:10px;font-size:11px;font-family:var(--font-mono);color:#f87171;white-space:pre-wrap;word-break:break-all;max-height:200px;overflow-y:auto;margin:0">' + escHtml(data.stderr) + '</pre></div>';
      }
    }

    html += '<div style="display:flex;gap:8px;margin-top:16px">';
    html += '<button onclick="fecharModal()" class="btn btn-secondary" style="flex:1">Fechar</button>';
    html += '<a href="index.php?rota=logs&acao=index&id=' + id + '" class="btn btn-ghost" style="flex:1;text-align:center">Ver logs</a>';
    html += '</div>';

    conteudo.innerHTML = html;
    titulo.textContent = nome;
    showToast(data.background ? 'Tarefa iniciada em background' : (data.sucesso ? 'Executada com sucesso' : 'Falha na execucao'), data.background ? 'aviso' : (data.sucesso ? 'sucesso' : 'erro'));
  })
  .catch(function (err) {
    conteudo.innerHTML = '<div style="color:#f87171;font-size:13px">Erro de comunicacao: ' + escHtml(String(err)) + '</div>'
      + '<button onclick="fecharModal()" class="btn btn-secondary" style="margin-top:12px">Fechar</button>';
    showToast('Erro ao executar tarefa', 'erro');
  });
}

function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/* showToast disponivel globalmente (usada tambem fora do DOMContentLoaded) */
function showToast(msg, tipo) {
  tipo = tipo || 'sucesso';
  var existing = document.querySelector('.toast');
  if (existing) existing.remove();
  var toast = document.createElement('div');
  toast.className   = 'toast toast-' + tipo;
  toast.textContent = msg;
  document.body.appendChild(toast);
  requestAnimationFrame(function () { toast.classList.add('visible'); });
  setTimeout(function () {
    toast.classList.remove('visible');
    setTimeout(function () { toast.remove(); }, 300);
  }, 2800);
}
