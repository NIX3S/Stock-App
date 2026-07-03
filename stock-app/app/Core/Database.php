<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Accès unique à la base de données. Toute la base de l'application passe par
 * cette classe ; aucune autre partie du code n'instancie PDO directement.
 */
final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $config = Config::all();
            $db = $config['db'];

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $db['host'],
                $db['port'],
                $db['name'],
                $db['charset']
            );

            try {
                
                self::$instance = new PDO($dsn, $db['user'], $db['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                error_log('[DB] Connection failed: ' . $e->getMessage());
                http_response_code(500);
                die('Erreur de connexion à la base de données.');
            }
        }

        return self::$instance;
    }
}
