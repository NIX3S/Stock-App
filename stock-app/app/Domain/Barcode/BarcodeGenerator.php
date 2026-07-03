<?php

declare(strict_types=1);

namespace App\Domain\Barcode;

/**
 * Génère le rendu SVG d'un code-barres Code128 (sous-ensemble B, suffisant
 * pour des codes alphanumériques courts type référence interne). Utilisé par
 * LabelPrintService pour l'impression d'étiquettes sans dépendance externe lourde.
 */
final class BarcodeGenerator
{
    private const CODE128B = [
        ' ' => '11011001100', '!' => '11001101100', '"' => '11001100110', '#' => '10010011000',
        '$' => '10010001100', '%' => '10001001100', '&' => '10011001000', '\'' => '10011000100',
        '(' => '10001100100', ')' => '11001001000', '*' => '11001000100', '+' => '11000100100',
        ',' => '10110011100', '-' => '10011011100', '.' => '10011001110', '/' => '10111001100',
        '0' => '10011101100', '1' => '10011100110', '2' => '11001110010', '3' => '11001011100',
        '4' => '11001001110', '5' => '11011100100', '6' => '11001110100', '7' => '11101101110',
        '8' => '11101001100', '9' => '11100101100', 'A' => '11100100110', 'B' => '11101100100',
        'C' => '11100110100', 'D' => '11100110010', 'E' => '11011011000', 'F' => '11011000110',
        'G' => '11000110110', 'H' => '10100011000', 'I' => '10001011000', 'J' => '10001000110',
        'K' => '10110001000', 'L' => '10001101000', 'M' => '10001100010', 'N' => '11010001000',
        'O' => '11000101000', 'P' => '11000100010', 'Q' => '10110111000', 'R' => '10110001110',
        'S' => '10001101110', 'T' => '10111011000', 'U' => '10111000110', 'V' => '10001110110',
        'W' => '11101110110', 'X' => '11010001110', 'Y' => '11000101110', 'Z' => '11011101000',
    ];
    private const START_B = '11010010000';
    private const STOP = '1100011101011';

    /**
     * Retourne un SVG. Seuls les caractères du sous-ensemble (lettres majuscules,
     * chiffres, espace et quelques symboles) sont supportés : suffisant pour des
     * codes générés en interne (préfixe + chiffres).
     */
    public function toSvg(string $code, int $width = 200, int $height = 60): string
    {
        $code = strtoupper($code);
        $values = array_map(fn($c) => array_search($c, array_keys(self::CODE128B), true), str_split($code));

        $bars = self::START_B;
        $sum = 104; // valeur START B
        foreach (str_split($code) as $i => $char) {
            if (!isset(self::CODE128B[$char])) {
                continue;
            }
            $bars .= self::CODE128B[$char];
            $codeValue = array_search($char, array_keys(self::CODE128B), true);
            $sum += $codeValue * ($i + 1);
        }
        $checksum = $sum % 103;
        $checksumChar = array_keys(self::CODE128B)[$checksum] ?? null;
        if ($checksumChar !== null) {
            $bars .= self::CODE128B[$checksumChar];
        }
        $bars .= self::STOP;

        $barWidth = $width / strlen($bars);
        $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 {$width} {$height}\">";
        $x = 0;
        foreach (str_split($bars) as $bit) {
            if ($bit === '1') {
                $svg .= "<rect x=\"{$x}\" y=\"0\" width=\"{$barWidth}\" height=\"" . ($height - 14) . "\" fill=\"#000\"/>";
            }
            $x += $barWidth;
        }
        $svg .= "<text x=\"" . ($width / 2) . "\" y=\"{$height}\" text-anchor=\"middle\" font-size=\"11\" font-family=\"monospace\">{$code}</text>";
        $svg .= '</svg>';

        return $svg;
    }
}
