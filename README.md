# CronManager — PHP + MySQL

Aplicação web para gerenciamento do crontab do root com interface dark elegante.

---

## Requisitos do servidor

| Componente | Versão mínima |
|---|---|
| PHP | 8.1+ |
| MySQL | 8.0+ |
| Nginx | 1.18+ |
| Extensões PHP | `pdo`, `pdo_mysql`, `mbstring`, `json` |

---

## Estrutura do projeto

```
cronmanager_php/
├── public/                ← Raiz do virtual host (document root)
│   ├── index.php          ← Ponto de entrada
│   └── assets/
│       ├── css/app.css
│       └── js/app.js
├── src/
│   ├── autoload.php       ← Autoloader PSR-4
│   ├── Database/
│   │   └── Conexao.php    ← Singleton PDO
│   ├── Models/
│   │   ├── TarefaModel.php
│   │   └── ExecucaoModel.php
│   ├── Controllers/
│   │   ├── DashboardController.php
│   │   ├── TarefaController.php
│   │   ├── LogController.php
│   │   └── ApiController.php
│   └── Helpers/
│       ├── CronHelper.php  ← Validação e cálculo de cron
│       └── Roteador.php    ← Roteador minimalista
├── views/
│   ├── layouts/principal.php
│   ├── dashboard/index.php
│   ├── tarefas/
│   │   ├── lista.php
│   │   ├── formulario.php
│   │   └── confirmar_exclusao.php
│   └── logs/lista.php
├── config/
│   └── banco.php          ← Credenciais do banco
├── banco.sql              ← Script de criação das tabelas
└── nginx.conf             ← Configuração de exemplo para Nginx
```

---

## Instalação passo a passo

### 1. Fazer upload dos arquivos

```bash
# Copiar o projeto para o servidor
scp -r cronmanager_php/ usuario@SEU_SERVIDOR:/var/www/cronmanager

# Ou via rsync
rsync -avz cronmanager_php/ usuario@SEU_SERVIDOR:/var/www/cronmanager/
```

### 2. Ajustar permissões

```bash
chown -R www-data:www-data /var/www/cronmanager
chmod -R 755 /var/www/cronmanager
chmod 640 /var/www/cronmanager/config/banco.php
```

### 3. Configurar o Nginx

```bash
# Copiar a configuração
cp /var/www/cronmanager/nginx.conf /etc/nginx/sites-available/cronmanager

# Editar e substituir SEU_DOMINIO_OU_IP e o caminho do root
nano /etc/nginx/sites-available/cronmanager

# Ativar o site
ln -s /etc/nginx/sites-available/cronmanager /etc/nginx/sites-enabled/

# Testar e recarregar
nginx -t && systemctl reload nginx
```

### 4. Verificar banco de dados

O banco e as tabelas já foram criados automaticamente no servidor `191.7.32.180`, banco `crontab`.

Caso precise recriar:

```bash
mysql -h 191.7.32.180 -u manus -p crontab < banco.sql
```

### 5. Ajustar credenciais (se necessário)

Edite `config/banco.php` com as credenciais corretas:

```php
return [
    'host'    => '191.7.32.180',
    'banco'   => 'crontab',
    'usuario' => 'manus',
    'senha'   => 'SUA_SENHA',
];
```

---

## Tabelas criadas

| Tabela | Descrição |
|---|---|
| `crn__tarefas` | Tarefas agendadas (jobs cron) |
| `crn__tarefas_execucoes` | Histórico de execuções e logs |
| `crn__configuracoes` | Configurações gerais do sistema |

### Prefixo de nomenclatura

```
crn__tarefas               → tabela principal
crn__tarefas_execucoes     → subtabela de tarefas
crn__configuracoes         → tabela auxiliar
```

Cada coluna usa o prefixo da tabela:
- `tar_id`, `tar_nome`, `tar_comando` → tabela tarefas
- `exe_id`, `exe_tar_id`, `exe_stdout` → tabela execuções
- `cfg_chave`, `cfg_valor` → tabela configurações

---

## Integração com crontab real

Para que a aplicação execute os comandos de verdade, adicione ao crontab do root:

```bash
crontab -e
```

E insira uma linha que chame o executor PHP a cada minuto:

```cron
* * * * * /usr/bin/php /var/www/cronmanager/executor.php >> /var/log/cronmanager.log 2>&1
```

> **Nota:** O arquivo `executor.php` pode ser criado para ler as tarefas ativas do banco e executá-las conforme o agendamento, registrando stdout/stderr na tabela `crn__tarefas_execucoes`.

---

## Funcionalidades

- **Dashboard** — visão geral com total de tarefas, ativas, inativas e falhas nas últimas 24h; tabela de próximas execuções
- **Listagem** — todas as tarefas com expressão cron, comando, status, toggle ativo/inativo e ações
- **Criar/Editar** — formulário com campos individuais (minuto, hora, dia, mês, dia da semana), presets rápidos, preview em tempo real com validação via API
- **Excluir** — confirmação obrigatória digitando "CONFIRMAR"
- **Logs** — histórico paginado com timestamp, duração, exit code, stdout e stderr em estilo terminal

---

## Segurança recomendada

- Proteja o acesso à aplicação com autenticação HTTP Basic no Nginx ou implemente login próprio
- Nunca exponha `config/banco.php` publicamente (o Nginx já bloqueia arquivos `.ht*`)
- Use HTTPS com certificado Let's Encrypt (`certbot --nginx`)
