<?php

declare(strict_types=1);

namespace App\Domain\Label;

use App\Domain\Barcode\BarcodeGenerator;

/**
 * Génère une page HTML imprimable (A4, @media print) contenant une grille
 * d'étiquettes configurable. Volontairement en HTML/CSS plutôt qu'en PDF
 * binaire pour rester sans dépendance lourde ; voir le README pour l'option
 * d'une librairie PDF si un rendu plus contrôlé est nécessaire.
 */
final class LabelPrintService
{
    public function __construct(private BarcodeGenerator $barcodeGenerator = new BarcodeGenerator())
    {
    }

    /**
     * @param array $products Liste de ['name' => ..., 'reference' => ..., 'barcode' => ...]
     * @param int $copiesPerProduct Nombre d'étiquettes par produit
     * @param int $columns Nombre de colonnes sur la page A4
     * @param int $rows Nombre de lignes sur la page A4
     */
    public function renderLabelSheet(array $products, int $copiesPerProduct, int $columns, int $rows): string
    {
        $labels = [];
        foreach ($products as $product) {
            for ($i = 0; $i < $copiesPerProduct; $i++) {
                $labels[] = $product;
            }
        }

        $html = '<div class="label-grid" style="display:grid;grid-template-columns:repeat(' . $columns . ',1fr);">';
        foreach ($labels as $product) {
            $svg = $this->barcodeGenerator->toSvg($product['barcode'] ?? '', 180, 50);
            $name = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
            $reference = htmlspecialchars($product['reference'] ?? '', ENT_QUOTES, 'UTF-8');
            $html .= "<div class=\"label-cell\">"
                   . "<div class=\"label-name\">{$name}</div>"
                   . "<div class=\"label-barcode\">{$svg}</div>"
                   . "<div class=\"label-ref\">{$reference}</div>"
                   . "</div>";
        }
        $html .= '</div>';

        return $html;
    }
}
