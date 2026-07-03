<?php

declare(strict_types=1);

namespace App\Http\Api;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use PDO;

/**
 * Persiste les préférences d'affichage des tableaux par utilisateur :
 * colonnes visibles, ordre, filtres, tri. Consommé par modules/datatable.js
 * au chargement et à chaque modification (debounce côté client).
 */
final class UserPreferenceApiController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function get(Request $request, string $tableKey): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM user_table_preferences WHERE user_id = :user_id AND table_key = :table_key');
        $stmt->execute(['user_id' => Session::get('user_id'), 'table_key' => $tableKey]);
        $pref = $stmt->fetch();

        if (!$pref) {
            Response::json(['preferences' => null]);
        }

        Response::json([
            'preferences' => [
                'visible_columns' => json_decode($pref['visible_columns_json'] ?? '[]', true),
                'column_order' => json_decode($pref['column_order_json'] ?? '[]', true),
                'filters' => json_decode($pref['filters_json'] ?? '{}', true),
                'sort' => json_decode($pref['sort_json'] ?? '{}', true),
            ],
        ]);
    }

    public function save(Request $request, string $tableKey): void
    {
        Csrf::verifyRequestOrFail($request);

        $userId = Session::get('user_id');
        $data = $request->all();

        $stmt = $this->pdo->prepare(
            'INSERT INTO user_table_preferences (user_id, table_key, visible_columns_json, column_order_json, filters_json, sort_json, updated_at)
             VALUES (:user_id, :table_key, :visible_columns, :column_order, :filters, :sort, NOW())
             ON DUPLICATE KEY UPDATE
                visible_columns_json = VALUES(visible_columns_json),
                column_order_json = VALUES(column_order_json),
                filters_json = VALUES(filters_json),
                sort_json = VALUES(sort_json),
                updated_at = NOW()'
        );
        $stmt->execute([
            'user_id' => $userId,
            'table_key' => $tableKey,
            'visible_columns' => json_encode($data['visible_columns'] ?? [], JSON_UNESCAPED_UNICODE),
            'column_order' => json_encode($data['column_order'] ?? [], JSON_UNESCAPED_UNICODE),
            'filters' => json_encode($data['filters'] ?? [], JSON_UNESCAPED_UNICODE),
            'sort' => json_encode($data['sort'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);

        Response::json(['success' => true]);
    }
}
