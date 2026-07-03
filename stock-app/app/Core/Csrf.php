<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (!Session::has(self::SESSION_KEY)) {
            Session::set(self::SESSION_KEY, bin2hex(random_bytes(32)));
        }
        return Session::get(self::SESSION_KEY);
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">";
    }

    public static function validate(?string $token): bool
    {
        if (!$token || !Session::has(self::SESSION_KEY)) {
            return false;
        }
        return hash_equals(Session::get(self::SESSION_KEY), $token);
    }

    /**
     * À appeler en début de tout contrôleur traitant une requête POST/PUT/DELETE.
     * Termine la requête avec un code 419 si le jeton est invalide.
     */
    public static function verifyRequestOrFail(Request $request): void
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }
        $token = $request->input('csrf_token') ?? $request->header('X-CSRF-Token');
        if (!self::validate($token)) {
            http_response_code(419);
            die('Jeton de sécurité invalide ou expiré. Veuillez rafraîchir la page.');
        }
    }
}
