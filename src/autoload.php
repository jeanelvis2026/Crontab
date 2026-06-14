<?php
/**
 * Autoloader PSR-4 simples para o CronManager
 * Namespace raiz: CronManager → /src/
 */
spl_autoload_register(function (string $classe): void {
    $prefixo   = 'CronManager\\';
    $diretorio = __DIR__ . '/';

    if (!str_starts_with($classe, $prefixo)) {
        return;
    }

    $relativo = substr($classe, strlen($prefixo));
    $arquivo  = $diretorio . str_replace('\\', '/', $relativo) . '.php';

    if (file_exists($arquivo)) {
        require $arquivo;
    }
});
