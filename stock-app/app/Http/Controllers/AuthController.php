<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\AuthService;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AuthController
{
    public function __construct(private AuthService $auth = new AuthService())
    {
    }

    public function showLogin(Request $request): void
    {
        if (Session::has('user_id')) {
            Response::redirect('/dashboard');
        }
        Response::view('auth/login', ['error' => Session::getFlash('error')], 'guest');
    }

    public function login(Request $request): void
    {
        Csrf::verifyRequestOrFail($request);

        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');

        $result = $this->auth->attempt($email, $password);

        if (!$result['success']) {
            Session::flash('error', $result['message']);
            Response::redirect('/login');
        }

        if (Session::get('must_change_password')) {
            Response::redirect('/password/change-required');
        }

        Response::redirect('/dashboard');
    }

    public function logout(Request $request): void
    {
        $this->auth->logout();
        Response::redirect('/login');
    }
}
