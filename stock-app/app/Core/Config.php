<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Point d'accès unique à la configuration. Le fichier app/Config/config.php
 * n'est exécuté qu'une seule fois (via le test self::$data === null) quel que
 * soit le nombre de classes qui appellent Config::all() et dans quel ordre :
 * cela évite à la fois la redéclaration de fonctions et le piège de
 * require_once qui ne retourne la valeur du fichier que lors de son premier appel.
 */
final class Config
{
    private static ?array $data = null;

    public static function all(): array
    {
        if (self::$data === null) {
            self::$data = require __DIR__ . '/../Config/config.php';
        }
        return self::$data;
    }

    public static function get(string $dotPath, mixed $default = null): mixed
    {
        $value = self::all();
        foreach (explode('.', $dotPath) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
