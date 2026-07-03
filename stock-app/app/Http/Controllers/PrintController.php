<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\Label\LabelPrintService;
use App\Domain\Product\ProductRepository;
use App\Domain\Stock\StockEntryRepository;

final class PrintController
{
    public function __construct(
        private ProductRepository $products = new ProductRepository(),
        private StockEntryRepository $entries = new StockEntryRepository(),
        private LabelPrintService $labelService = new LabelPrintService()
    ) {
    }

    public function index(Request $request): void
    {
        Response::view('print/index', []);
    }

    public function inventory(Request $request): void
    {
        $result = $this->products->paginate(1, 100000, ['status' => 'active'], 'name', 'asc');
        Logger::record('print.inventory', (int) Session::get('user_id'));
        Response::view('print/inventory', ['products' => $result['data']], 'print');
    }

    public function productList(Request $request): void
    {
        $result = $this->products->paginate(1, 100000, [], 'name', 'asc');
        Logger::record('print.product_list', (int) Session::get('user_id'));
        Response::view('print/product-list', ['products' => $result['data']], 'print');
    }

    public function expiringList(Request $request): void
    {
        $entries = $this->entries->expiringSoon(30, 1000);
        Logger::record('print.expiring_list', (int) Session::get('user_id'));
        Response::view('print/expiring-list', ['entries' => $entries], 'print');
    }

    public function labels(Request $request): void
    {
        $productIds = (array) $request->input('product_ids', []);
        $copies = max(1, (int) $request->input('copies', 1));
        $columns = max(1, (int) $request->input('columns', 3));
        $rows = max(1, (int) $request->input('rows', 8));

        $products = array_filter(array_map(fn($id) => $this->products->findById((int) $id), $productIds));

        $html = $this->labelService->renderLabelSheet($products, $copies, $columns, $rows);
        Logger::record('print.labels', (int) Session::get('user_id'), null, null, ['count' => count($products) * $copies]);

        Response::view('print/labels', ['labelsHtml' => $html, 'rows' => $rows], 'print');
    }

    /**
     * Impression rapide d'une étiquette pour un seul produit (appelée après
     * "Enregistrer et imprimer" sur le formulaire d'entrée de stock).
     */
    public function labelSingle(Request $request): void
    {
        $productId = (int) $request->query('product_id', 0);
        $product   = $productId ? $this->products->findById($productId) : null;

        if (!$product) {
            Session::flash('error', 'Produit introuvable.');
            Response::redirect('/stock-entries');
        }

        $html = $this->labelService->renderLabelSheet([$product], 1, 3, 8);
        Logger::record('print.labels', (int) Session::get('user_id'), 'product', $productId);

        Response::view('print/label-single', [
            'product'    => $product,
            'labelsHtml' => $html,
        ], 'print');
    }
}