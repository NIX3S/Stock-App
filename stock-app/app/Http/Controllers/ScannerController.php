<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Product\ProductRepository;

final class ScannerController
{
    public function __construct(private ProductRepository $products = new ProductRepository())
    {
    }

    /** Page de scan générique : affiche la fiche produit dès la lecture du code-barres. */
    public function index(Request $request): void
    {
        Response::view('stock-exits/scanner', []);
    }

    public function lookup(Request $request): void
    {
        $barcode = (string) $request->input('barcode', '');
        $product = $this->products->findByBarcode($barcode);

        if (!$product) {
            Response::json(['found' => false], 404);
        }

        Response::json(['found' => true, 'product' => $product]);
    }
}
