<?php

declare(strict_types=1);

namespace App\Http\Api;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Stock\StockEntryRepository;

final class StockApiController
{
    public function __construct(private StockEntryRepository $repository = new StockEntryRepository())
    {
    }

    public function listEntries(Request $request): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(200, max(10, (int) $request->query('per_page', 25)));
        $sortColumn = (string) $request->query('sort', 'entry_date');
        $sortDir = (string) $request->query('dir', 'desc');

        $filters = [
            'product_id'         => $request->query('product_id'),
            'expiring_soon_days' => $request->query('expiring_soon_days'),
            'search'             => $request->query('search'),
            'expiry_type'        => $request->query('expiry_type'),
            'in_stock'           => $request->query('in_stock'),
        ];

        $result = $this->repository->paginate($page, $perPage, array_filter($filters, fn($v) => $v !== null && $v !== ''), $sortColumn, $sortDir);

        Response::json([
            'data' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * Vérifie s'il existe déjà une entrée avec les mêmes infos (doublon potentiel).
     * GET /api/stock-entries/check-duplicate?product_id=X&expiry_date=Y&origin=Z
     */
    public function checkDuplicate(Request $request): void
    {
        $productId  = (int) $request->query('product_id', 0);
        $expiryDate = $request->query('expiry_date') ?: null;
        $origin     = $request->query('origin') ?: null;

        if (!$productId) {
            Response::json(['duplicate' => false]);
            return;
        }

        $existing = $this->repository->findDuplicate($productId, $expiryDate, $origin);
        if ($existing) {
            Response::json([
                'duplicate'        => true,
                'entry_id'         => $existing['id'],
                'product_name'     => $existing['product_name'],
                'current_quantity' => $existing['remaining_quantity'],
                'entry_date'       => $existing['entry_date'],
                'expiry_date'      => $existing['expiry_date'],
                'origin'           => $existing['origin'],
            ]);
        } else {
            Response::json(['duplicate' => false]);
        }
    }
}
