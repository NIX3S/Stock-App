<?php

declare(strict_types=1);

namespace App\Domain\CustomField;

/**
 * Objet valeur immuable représentant une définition de champ personnalisé,
 * utilisé pour générer dynamiquement les formulaires (produits / entrées de stock).
 */
final class CustomFieldDefinition
{
    public function __construct(
        public readonly int $id,
        public readonly string $entity,
        public readonly string $fieldKey,
        public readonly string $label,
        public readonly string $fieldType,
        public readonly ?array $options,
        public readonly bool $isRequired,
        public readonly int $displayOrder
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            $row['entity'],
            $row['field_key'],
            $row['label'],
            $row['field_type'],
            $row['options_json'] ? json_decode($row['options_json'], true) : null,
            (bool) $row['is_required'],
            (int) $row['display_order']
        );
    }
}
