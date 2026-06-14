<?php
namespace CronManager\Database;

use PDO;
use PDOException;

/**
 * Singleton de conexão PDO com o MySQL
 */
class Conexao
{
    private static ?PDO $instancia = null;

    private function __construct() {}
    private function __clone() {}

    /** Forca uma nova conexao (util apos execucoes longas que fecham a conexao) */
    public static function reconectar(): void
    {
        self::$instancia = null;
    }

    public static function obter(): PDO
    {
        if (self::$instancia === null) {
            $cfg = require dirname(__DIR__, 2) . '/config/banco.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['porta'],
                $cfg['banco'],
                $cfg['charset']
            );

            try {
                self::$instancia = new PDO($dsn, $cfg['usuario'], $cfg['senha'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);

                self::$instancia->exec("SET time_zone = '+00:00'");
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode([
                    'erro' => 'Falha na conexão com o banco de dados.',
                    'detalhe' => $e->getMessage(),
                ]));
            }
        }

        return self::$instancia;
    }
}
