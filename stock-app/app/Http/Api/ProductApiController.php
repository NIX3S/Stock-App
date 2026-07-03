<?php

declare(strict_types=1);

namespace App\Http\Api;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Product\ProductRepository;

final class ProductApiController
{
    public function __construct(private ProductRepository $repository = new ProductRepository())
    {
    }

    /**
     * Endpoint server-side processing consommé par datatable.js : pagination,
     * tri et filtres traités en SQL pour rester performant à plusieurs
     * milliers de produits.
     */
    public function list(Request $request): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(200, max(10, (int) $request->query('per_page', 25)));
        $sortColumn = (string) $request->query('sort', 'name');
        $sortDir = (string) $request->query('dir', 'asc');

        $filters = [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'category_id' => $request->query('category_id'),
            'low_stock' => $request->query('low_stock'),
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
