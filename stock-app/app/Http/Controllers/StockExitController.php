<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\Product\ProductRepository;
use App\Domain\Stock\StockService;

final class StockExitController
{
    public function __construct(
        private StockService $service = new StockService(),
        private ProductRepository $products = new ProductRepository()
    ) {
    }

    public function index(Request $request): void
    {
        Response::view('stock-exits/index', []);
    }

    /**
     * Appelée en AJAX par scanner.js après lecture d'un code-barres : retourne
     * la fiche produit et ses entrées disponibles (triées par échéance), sans
     * jamais choisir une entrée à la place de l'utilisateur.
     */
    public function lookupByBarcode(Request $request): void
    {
        $barcode = (string) $request->input('barcode', '');
        $product = $this->products->findByBarcode($barcode);

        if (!$product) {
            Response::json(['found' => false], 404);
        }

        Response::json([
            'found' => true,
            'product' => $product,
            'entries' => $this->service->entriesForProduct((int) $product['id']),
        ]);
    }

    public function store(Request $request): void
    {
        Csrf::verifyRequestOrFail($request);

        $stockEntryId = (int) $request->input('stock_entry_id', 0);
        $quantity = (int) $request->input('quantity', 0);

        if ($stockEntryId <= 0 || $quantity <= 0) {
            Response::json(['success' => false, 'message' => 'Données invalides.'], 422);
        }

        try {
            $result = $this->service->recordExitFromScannedEntry(
                $stockEntryId,
                $quantity,
                (int) Session::get('user_id'),
                $request->input('comment')
            );
        } catch (\InvalidArgumentException $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        Response::json(['success' => true, 'warning' => $result['warning']]);
    }
}
