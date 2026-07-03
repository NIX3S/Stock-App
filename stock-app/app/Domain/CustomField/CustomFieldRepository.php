<?php

declare(strict_types=1);

namespace App\Domain\CustomField;

use App\Core\Database;
use PDO;

/**
 * Cœur du système d'extensibilité : permet d'ajouter des champs à une entrée
 * de stock ou à un produit (ex: "Personne responsable", "Température",
 * "Emplacement") sans migration SQL ni modification de code, via une
 * interface d'administration (SettingsController).
 */
final class CustomFieldRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function activeForEntity(string $entity): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM custom_field_definitions WHERE entity = :entity AND is_active = 1 ORDER BY display_order, label'
        );
        $stmt->execute(['entity' => $entity]);
        return $stmt->fetchAll();
    }

    public function allForEntity(string $entity): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM custom_field_definitions WHERE entity = :entity ORDER BY display_order, label');
        $stmt->execute(['entity' => $entity]);
        return $stmt->fetchAll();
    }

    public function create(string $entity, string $key, string $label, string $type, ?array $options, bool $required, int $order): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO custom_field_definitions (entity, field_key, label, field_type, options_json, is_required, display_order, is_active, created_at)
             VALUES (:entity, :key, :label, :type, :options, :required, :order, 1, NOW())'
        );
        $stmt->execute([
            'entity' => $entity,
            'key' => $key,
            'label' => $label,
            'type' => $type,
            'options' => $options ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
            'required' => $required ? 1 : 0,
            'order' => $order,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function deactivate(int $id): void
    {
        $this->pdo->prepare('UPDATE custom_field_definitions SET is_active = 0 WHERE id = :id')->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        // Supprime aussi toutes les valeurs liées (ON DELETE CASCADE sur custom_field_values)
        $this->pdo->prepare('DELETE FROM custom_field_definitions WHERE id = :id')->execute(['id' => $id]);
    }

    public function valuesForEntityRecord(string $entity, int $entityId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.field_key, d.field_type, d.label, v.value_text, v.value_number, v.value_date
             FROM custom_field_definitions d
             LEFT JOIN custom_field_values v ON v.definition_id = d.id AND v.entity_id = :entity_id
             WHERE d.entity = :entity AND d.is_active = 1
             ORDER BY d.display_order'
        );
        $stmt->execute(['entity' => $entity, 'entity_id' => $entityId]);
        return $stmt->fetchAll();
    }

    public function saveValue(int $definitionId, int $entityId, mixed $value, string $type): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM custom_field_values WHERE definition_id = :def AND entity_id = :entity LIMIT 1');
        $stmt->execute(['def' => $definitionId, 'entity' => $entityId]);
        $existing = $stmt->fetch();

        $columns = ['value_text' => null, 'value_number' => null, 'value_date' => null];
        match ($type) {
            'number' => $columns['value_number'] = $value,
            'date' => $columns['value_date'] = $value,
            'boolean' => $columns['value_text'] = $value ? '1' : '0',
            default => $columns['value_text'] = $value,
        };

        if ($existing) {
            $this->pdo->prepare(
                'UPDATE custom_field_values SET value_text = :value_text, value_number = :value_number, value_date = :value_date WHERE id = :id'
            )->execute([...$columns, 'id' => $existing['id']]);
        } else {
            $this->pdo->prepare(
                'INSERT INTO custom_field_values (definition_id, entity_id, value_text, value_number, value_date)
                 VALUES (:definition_id, :entity_id, :value_text, :value_number, :value_date)'
            )->execute([...$columns, 'definition_id' => $definitionId, 'entity_id' => $entityId]);
        }
    }
}
