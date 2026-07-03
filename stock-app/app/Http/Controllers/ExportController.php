<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Logger;
use App\Core\Request;
use App\Core\Session;
use App\Domain\Product\ProductRepository;

final class ExportController
{
    public function __construct(private ProductRepository $products = new ProductRepository())
    {
    }

    public function productsCsv(Request $request): void
    {
        $result = $this->products->paginate(1, 100000, [], 'name', 'asc');
        Logger::record('export.csv', (int) Session::get('user_id'), 'product');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="produits.csv"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM pour compatibilité Excel
        fputcsv($out, ['Nom', 'Référence', 'Code-barres', 'Catégorie', 'Unité', 'Stock total', 'Seuil min.', 'Statut'], ';');
        foreach ($result['data'] as $product) {
            fputcsv($out, [
                $product['name'],
                $product['reference'],
                $product['barcode'],
                $product['category_name'],
                $product['unit'],
                $product['total_stock'],
                $product['min_stock_threshold'],
                $product['status'],
            ], ';');
        }
        fclose($out);
        exit;
    }

    /** Export "Excel" via une table HTML servie avec un type MIME .xls — lisible nativement par Excel sans dépendance. */
    public function productsXlsx(Request $request): void
    {
        $result = $this->products->paginate(1, 100000, [], 'name', 'asc');
        Logger::record('export.xlsx', (int) Session::get('user_id'), 'product');

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="produits.xls"');

        echo '<table border="1"><tr><th>Nom</th><th>Référence</th><th>Code-barres</th><th>Catégorie</th><th>Unité</th><th>Stock total</th><th>Seuil min.</th><th>Statut</th></tr>';
        foreach ($result['data'] as $p) {
            printf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars((string) $p['name']),
                htmlspecialchars((string) $p['reference']),
                htmlspecialchars((string) $p['barcode']),
                htmlspecialchars((string) $p['category_name']),
                htmlspecialchars((string) $p['unit']),
                htmlspecialchars((string) $p['total_stock']),
                htmlspecialchars((string) $p['min_stock_threshold']),
                htmlspecialchars((string) $p['status'])
            );
        }
        echo '</table>';
        exit;
    }
}
