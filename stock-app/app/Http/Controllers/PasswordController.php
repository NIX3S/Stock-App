<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\PasswordResetService;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Domain\User\UserRepository;

final class PasswordController
{
    public function __construct(
        private PasswordResetService $service = new PasswordResetService(),
        private UserRepository $users = new UserRepository()
    ) {
    }

    public function showForgot(Request $request): void
    {
        Response::view('auth/forgot-password', ['message' => Session::getFlash('success')], 'guest');
    }

    public function sendResetLink(Request $request): void
    {
        Csrf::verifyRequestOrFail($request);

        $link = $this->service->requestReset((string) $request->input('email', ''));

        if ($link !== null) {
            // Mail non envoyé (sendmail absent / dev) : affiche le lien sur la page
            Session::flash('dev_reset_link', $link);
            Session::flash('success', 'E-mail non disponible sur ce serveur. Utilisez le lien ci-dessous directement.');
        } else {
            Session::flash('success', 'Si cette adresse existe, un e-mail de réinitialisation a été envoyé.');
        }

        Response::redirect('/password/forgot');
    }

    public function showReset(Request $request, string $token): void
    {
        if (!$this->service->validateToken($token)) {
            Response::view('auth/invitation-invalid', ['message' => 'Ce lien de réinitialisation est invalide ou expiré.'], 'guest');
        }
        Response::view('auth/reset-password', ['token' => $token], 'guest');
    }

    public function reset(Request $request, string $token): void
    {
        Csrf::verifyRequestOrFail($request);

        $validator = new Validator($request->all());
        $validator->required('password', 'Le mot de passe')->passwordStrength('password', 'Le mot de passe');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Response::redirect('/password/reset/' . $token);
        }

        $success = $this->service->resetPassword($token, (string) $request->input('password'));
        if (!$success) {
            Session::flash('error', 'Ce lien n\'est plus valide.');
            Response::redirect('/login');
        }

        Session::flash('success', 'Mot de passe modifié. Vous pouvez vous connecter.');
        Response::redirect('/login');
    }

    /** Changement obligatoire après une réinitialisation par un administrateur. */
    public function showChangeRequired(Request $request): void
    {
        Response::view('auth/change-required', [], 'guest');
    }

    public function changeRequired(Request $request): void
    {
        Csrf::verifyRequestOrFail($request);
        $validator = new Validator($request->all());
        $validator->required('password', 'Le mot de passe')->passwordStrength('password', 'Le mot de passe');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Response::redirect('/password/change-required');
        }

        $this->users->updatePasswordHash((int) Session::get('user_id'), password_hash((string) $request->input('password'), PASSWORD_DEFAULT));
        Session::set('must_change_password', false);
        Response::redirect('/dashboard');
    }
}
