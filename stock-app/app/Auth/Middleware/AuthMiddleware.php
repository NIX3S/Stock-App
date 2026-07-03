<?php

declare(strict_types=1);

namespace App\Auth\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware
{
    public function handle(Request $request): void
    {
        Session::start();

        if (!Session::has('user_id')) {
            if ($request->isAjax()) {
                Response::json(['error' => 'unauthenticated'], 401);
            }
            Response::redirect('/login');
        }
    }
}
