<?php
/**
 * CronManager — Ponto de entrada da aplicação
 * Hospedar este arquivo como raiz do virtual host no Nginx
 */

declare(strict_types=1);

// Timezone padrão
date_default_timezone_set('America/Fortaleza');

// Sessão
session_start();

// Autoloader
require dirname(__DIR__) . '/src/autoload.php';

// Despachar rota
\CronManager\Helpers\Roteador::despachar();
