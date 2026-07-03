<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\InvitationService;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Domain\Role\RoleRepository;

final class InvitationController
{
    public function __construct(
        private InvitationService $invitations = new InvitationService(),
        private RoleRepository $roles = new RoleRepository()
    ) {
    }

    /** Page publique d'acceptation d'invitation (UUID), pas de bouton "créer un compte" ailleurs. */
    public function showAccept(Request $request, string $uuid): void
    {
        $this->invitations->expireOutdated();
        $invitation = $this->invitations->validate($uuid);

        if (!$invitation) {
            Response::view('auth/invitation-invalid', [], 'guest');
        }

        Response::view('auth/accept-invitation', ['uuid' => $uuid, 'email' => $invitation['email']], 'guest');
    }

    public function accept(Request $request, string $uuid): void
    {
        Csrf::verifyRequestOrFail($request);

        $validator = new Validator($request->all());
        $validator->required('first_name', 'Le prénom')
            ->required('last_name', 'Le nom')
            ->required('password', 'Le mot de passe')
            ->passwordStrength('password', 'Le mot de passe');

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Response::redirect('/invitation/' . $uuid);
        }

        $success = $this->invitations->acceptAndCreateAccount(
            $uuid,
            (string) $request->input('first_name'),
            (string) $request->input('last_name'),
            (string) $request->input('password')
        );

        if (!$success) {
            Session::flash('error', 'Cette invitation n\'est plus valide.');
            Response::redirect('/login');
        }

        Session::flash('success', 'Compte créé avec succès. Vous pouvez vous connecter.');
        Response::redirect('/login');
    }

    /** Administration : liste + création d'invitations (nécessite invitation.manage). */
    public function index(Request $request): void
    {
        Response::view('users/invitations', [
            'invitations' => $this->invitations->listAll(),
            'roles' => $this->roles->all(),
        ]);
    }

    public function store(Request $request): void
    {
        Csrf::verifyRequestOrFail($request);

        $validator = new Validator($request->all());
        $validator->required('email', 'L\'e-mail')->email('email', 'L\'e-mail')->required('role_id', 'Le rôle');

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Response::redirect('/users/invitations');
        }

        $this->invitations->create(
            (string) $request->input('email'),
            (int) $request->input('role_id'),
            (int) Session::get('user_id')
        );

        Session::flash('success', 'Invitation envoyée.');
        Response::redirect('/users/invitations');
    }

    public function revoke(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);
        $this->invitations->revoke((int) $id, (int) Session::get('user_id'));
        Response::redirect('/users/invitations');
    }
}
