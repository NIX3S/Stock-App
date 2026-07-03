<?php

declare(strict_types=1);

namespace App\Domain\CustomField;

use App\Core\Logger;

final class CustomFieldService
{
    public function __construct(private CustomFieldRepository $repository = new CustomFieldRepository())
    {
    }

    /** @return CustomFieldDefinition[] */
    public function definitionsFor(string $entity): array
    {
        return array_map(
            fn(array $row) => CustomFieldDefinition::fromRow($row),
            $this->repository->activeForEntity($entity)
        );
    }

    public function addDefinition(string $entity, string $label, string $type, ?array $options, bool $required, int $byUserId): int
    {
        $key = $this->slugify($label);
        $id = $this->repository->create($entity, $key, $label, $type, $options, $required, 999);
        Logger::record('custom_field.create', $byUserId, 'custom_field_definition', $id, ['entity' => $entity, 'label' => $label]);
        return $id;
    }

    public function removeDefinition(int $id, int $byUserId): void
    {
        $this->repository->deactivate($id);
        Logger::record('custom_field.deactivate', $byUserId, 'custom_field_definition', $id);
    }

    public function deleteDefinition(int $id, int $byUserId): void
    {
        $this->repository->delete($id);
        Logger::record('custom_field.delete', $byUserId, 'custom_field_definition', $id);
    }

    /**
     * Enregistre les valeurs soumises pour une entité donnée à partir d'un
     * tableau de formulaire $_POST['custom_fields'][field_key] => valeur.
     */
    public function saveValues(string $entity, int $entityId, array $submittedValues): void
    {
        foreach ($this->definitionsFor($entity) as $definition) {
            if (array_key_exists($definition->fieldKey, $submittedValues)) {
                $this->repository->saveValue($definition->id, $entityId, $submittedValues[$definition->fieldKey], $definition->fieldType);
            }
        }
    }

    public function valuesFor(string $entity, int $entityId): array
    {
        return $this->repository->valuesForEntityRecord($entity, $entityId);
    }

    private function slugify(string $label): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $label), '_'));
        return substr($slug, 0, 80) . '_' . substr(bin2hex(random_bytes(2)), 0, 4);
    }
}
