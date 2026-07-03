<?php

declare(strict_types=1);

namespace App\Domain\Barcode;

use App\Domain\Product\ProductRepository;

/**
 * Génère des codes-barres internes uniques pour les produits sans code
 * fabricant (dons, reconditionnés...). Format : préfixe "INT" + 10 chiffres,
 * compatible avec une génération Code128 à l'impression (voir LabelPrintService).
 */
final class BarcodeService
{
    public function __construct(private ProductRepository $products = new ProductRepository())
    {
    }

    public function generateInternalCode(): string
    {
        // Code numérique pur 12 chiffres (compatible lecteurs EAN), sans préfixe visible.
        // Préfixe interne "2" (plage EAN réservée aux codes internes selon GS1).
        do {
            $code = '2' . str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);
        } while ($this->products->findByBarcode($code) !== null);

        return $code;
    }
}
