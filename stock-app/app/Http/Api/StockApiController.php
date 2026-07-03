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
            'product_id' => $request->query('product_id'),
            'expiring_soon_days' => $request->query('expiring_soon_days'),
        ];

        $result = $this->repository->paginate($page, $perPage, array_filter($filters, fn($v) => $v !== null && $v !== ''), $sortColumn, $sortDir);

        Response::json([
            'data' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }
}
