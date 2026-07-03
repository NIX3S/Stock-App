<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

final class LogController
{
    public function index(Request $request): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT al.*, u.first_name, u.last_name
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll();

        $total = (int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();

        Response::view('logs/index', [
            'logs' => $logs,
            'page' => $page,
            'totalPages' => (int) ceil($total / $perPage),
        ]);
    }
}
