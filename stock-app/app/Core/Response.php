<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Rend une vue PHP dans le layout principal, en passant des données
     * sous forme de variables nommées (extract contrôlé, pas de eval).
     */
    public static function view(string $view, array $data = [], string $layout = 'app'): never
    {
        extract($data, EXTR_SKIP);
        $viewPath = dirname(__DIR__) . '/View/' . $view . '.php';
        if (!file_exists($viewPath)) {
            http_response_code(500);
            die("Vue introuvable : {$view}");
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require dirname(__DIR__) . '/View/layouts/' . $layout . '.php';
        exit;
    }

    public static function partial(string $view, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        $viewPath = dirname(__DIR__) . '/View/' . $view . '.php';
        ob_start();
        require $viewPath;
        return ob_get_clean();
    }
}
