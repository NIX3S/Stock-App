<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Core\Logger;
use App\Domain\Barcode\BarcodeService;

final class ProductService
{
    public function __construct(
        private ProductRepository $repository = new ProductRepository(),
        private BarcodeService $barcodeService = new BarcodeService()
    ) {
    }

    public function create(array $data, int $byUserId): int
    {
        // Si aucun code-barres fabricant n'est fourni, on génère un code-barres interne unique.
        if (empty($data['barcode'])) {
            $data['barcode'] = $this->barcodeService->generateInternalCode();
            $data['barcode_type'] = 'internal';
        } else {
            $data['barcode_type'] = 'manufacturer';
        }

        $id = $this->repository->create($data);
        Logger::record('product.create', $byUserId, 'product', $id, ['name' => $data['name']]);
        return $id;
    }

    public function update(int $id, array $data, int $byUserId): void
    {
        $this->repository->update($id, $data);
        Logger::record('product.update', $byUserId, 'product', $id, ['fields' => array_keys($data)]);
    }

    public function archive(int $id, int $byUserId): void
    {
        $this->repository->archive($id);
        Logger::record('product.archive', $byUserId, 'product', $id);
    }

    public function reactivate(int $id, int $byUserId): void
    {
        $this->repository->reactivate($id);
        Logger::record('product.reactivate', $byUserId, 'product', $id);
    }
}
