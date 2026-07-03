<?php

declare(strict_types=1);

namespace App\Domain\Backup;

use App\Core\Config;
use App\Core\Logger;

/**
 * Sauvegarde simple via mysqldump si disponible en CLI sur l'hébergement,
 * sinon repli sur un export SQL généré en PHP pur (compatible mutualisé
 * sans accès shell).
 */
final class BackupService
{
    public function __construct(private string $backupDir)
    {
    }

    public function run(int $byUserId): string
    {
        $config = Config::all();
        $filename = 'backup_' . date('Y-m-d_His') . '.sql';
        $path = rtrim($this->backupDir, '/') . '/' . $filename;

        if ($this->canUseMysqldump()) {
            $this->backupWithMysqldump($config['db'], $path);
        } else {
            $this->backupWithPdo($path);
        }

        Logger::record('backup.created', $byUserId, 'backup', null, ['file' => $filename]);
        return $path;
    }

    private function canUseMysqldump(): bool
    {
        if (!function_exists('exec') || in_array('exec', array_map('trim', explode(',', (string) ini_get('disable_functions'))), true)) {
            return false;
        }
        exec('command -v mysqldump', $output, $returnCode);
        return $returnCode === 0;
    }

    private function backupWithMysqldump(array $db, string $path): void
    {
        $cmd = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
            escapeshellarg($db['host']),
            escapeshellarg((string) $db['port']),
            escapeshellarg($db['user']),
            escapeshellarg($db['pass']),
            escapeshellarg($db['name']),
            escapeshellarg($path)
        );
        exec($cmd);
    }

    /** Repli portable : export SQL généré en pur PHP via PDO, sans dépendance shell. */
    private function backupWithPdo(string $path): void
    {
        $pdo = \App\Core\Database::connection();
        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        $sql = "-- Sauvegarde générée le " . date('Y-m-d H:i:s') . "\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        foreach ($tables as $table) {
            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
            $sql .= $createStmt['Create Table'] . ";\n\n";

            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $columns = implode('`, `', array_keys($row));
                $values = implode(', ', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values($row)));
                $sql .= "INSERT INTO `{$table}` (`{$columns}`) VALUES ({$values});\n";
            }
            $sql .= "\n";
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        file_put_contents($path, $sql);
    }

    public function listBackups(): array
    {
        $files = glob(rtrim($this->backupDir, '/') . '/*.sql') ?: [];
        rsort($files);
        return array_map(fn($f) => ['name' => basename($f), 'size' => filesize($f), 'date' => filemtime($f)], $files);
    }
}
