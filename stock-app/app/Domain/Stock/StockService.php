<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use App\Core\Database;
use App\Core\Logger;
use App\Domain\CustomField\CustomFieldService;
use App\Domain\Product\ProductRepository;
use PDO;

final class StockService
{
    private PDO $pdo;

    public function __construct(
        private StockEntryRepository $entries = new StockEntryRepository(),
        private StockMovementRepository $movements = new StockMovementRepository(),
        private ProductRepository $products = new ProductRepository(),
        private CustomFieldService $customFields = new CustomFieldService()
    ) {
        $this->pdo = Database::connection();
    }

    /**
     * Crée une entrée de stock. $customFieldValues est un tableau associatif
     * field_key => valeur, généré dynamiquement par le formulaire (section 3.4
     * de l'architecture) — aucune modification de code nécessaire pour de
     * nouveaux champs.
     */
    public function updateEntry(int $entryId, array $data, int $byUserId): void
    {
        $this->entries->update($entryId, $data);
        Logger::record('stock.entry.update', $byUserId, 'stock_entry', $entryId, ['fields' => array_keys($data)]);
    }

    public function recordEntry(array $data, int $byUserId, array $customFieldValues = []): int
    {
        $data['created_by'] = $byUserId;
        $entryId = $this->entries->create($data);

        $this->movements->insert('entry', (int) $data['product_id'], $entryId, (int) $data['quantity'], $byUserId, $data['comment'] ?? null);

        if ($customFieldValues) {
            $this->customFields->saveValues('stock_entry', $entryId, $customFieldValues);
        }

        Logger::record('stock.entry.create', $byUserId, 'stock_entry', $entryId, [
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
        ]);

        return $entryId;
    }

    /**
     * Règle métier centrale : l'utilisateur scanne un produit, le logiciel ne
     * choisit JAMAIS automatiquement une autre entrée (pas de FEFO automatique).
     * Si une autre entrée du même produit a une échéance plus proche, on retourne
     * un avertissement que le contrôleur affiche, mais la sortie est appliquée
     * sur l'entrée explicitement désignée par $stockEntryId.
     *
     * @return array{movement_id: int, warning: ?string}
     */
    public function recordExitFromScannedEntry(int $stockEntryId, int $quantity, int $byUserId, ?string $comment = null): array
    {
        $entry = $this->entries->findById($stockEntryId);
        if (!$entry) {
            throw new \InvalidArgumentException('Entrée de stock introuvable.');
        }
        if ($entry['remaining_quantity'] < $quantity) {
            throw new \InvalidArgumentException('Quantité demandée supérieure au stock restant sur cette entrée.');
        }

        $this->pdo->beginTransaction();
        try {
            $this->entries->decrementRemaining($stockEntryId, $quantity);
            $movementId = $this->movements->insert('exit', (int) $entry['product_id'], $stockEntryId, $quantity, $byUserId, $comment);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        Logger::record('stock.exit.create', $byUserId, 'stock_entry', $stockEntryId, [
            'product_id' => $entry['product_id'],
            'quantity' => $quantity,
        ]);

        // Vérifie s'il existe une autre entrée du même produit qui expire plus tôt,
        // sans jamais forcer son utilisation — simple information pour l'utilisateur.
        $earlier = $this->entries->earliestExpiryExcluding((int) $entry['product_id'], $stockEntryId);
        $warning = null;
        if ($earlier && (!$entry['expiry_date'] || $earlier['expiry_date'] < $entry['expiry_date'])) {
            $warning = sprintf(
                "Attention, une autre entrée de ce produit expire plus tôt (le %s).",
                (new \DateTime($earlier['expiry_date']))->format('d/m/Y')
            );
        }

        return ['movement_id' => $movementId, 'warning' => $warning];
    }

    public function entriesForProduct(int $productId): array
    {
        return $this->entries->availableForProduct($productId);
    }

    public function expiringSoon(int $days = 7): array
    {
        return $this->entries->expiringSoon($days);
    }
}
