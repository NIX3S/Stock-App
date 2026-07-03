<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Domain\Product\ProductRepository;
use App\Domain\Stock\StockEntryRepository;
use App\Domain\Stock\StockMovementRepository;

final class DashboardController
{
    public function __construct(
        private ProductRepository $products = new ProductRepository(),
        private StockEntryRepository $entries = new StockEntryRepository(),
        private StockMovementRepository $movements = new StockMovementRepository()
    ) {
    }

    public function index(Request $request): void
    {
        Response::view('dashboard/index', [
            'productCount' => $this->products->countActive(),
            'totalStock' => $this->products->totalStockQuantity(),
            'belowMinimum' => $this->products->belowMinimum(),
            'expiringSoon' => $this->entries->expiringSoon(7),
            'recentEntries' => $this->movements->recent('entry', 8),
            'recentExits' => $this->movements->recent('exit', 8),
            'recentLogins' => Logger::recent(8),
        ]);
    }
}
