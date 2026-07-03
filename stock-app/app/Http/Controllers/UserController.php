<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Domain\Role\RoleRepository;
use App\Domain\User\UserRepository;
use App\Domain\User\UserService;

final class UserController
{
    public function __construct(
        private UserRepository $repository = new UserRepository(),
        private UserService $service = new UserService(),
        private RoleRepository $roles = new RoleRepository()
    ) {
    }

    public function index(Request $request): void
    {
        Response::view('users/index', ['roles' => $this->roles->all()]);
    }

    public function show(Request $request, string $id): void
    {
        $user = $this->repository->findById((int) $id);
        if (!$user) {
            http_response_code(404);
            Response::view('errors/404', [], 'app');
        }
        Response::view('users/show', ['user' => $user, 'roles' => $this->roles->all()]);
    }

    public function update(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);

        $validator = new Validator($request->all());
        $validator->required('first_name', 'Le prénom')->required('last_name', 'Le nom')
            ->required('email', 'L\'e-mail')->email('email', 'L\'e-mail');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Response::redirect('/users/' . $id);
        }

        $this->service->update((int) $id, [
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'email' => mb_strtolower((string) $request->input('email')),
        ], (int) Session::get('user_id'));

        Session::flash('success', 'Utilisateur mis à jour.');
        Response::redirect('/users/' . $id);
    }

    public function changeRole(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);
        $this->service->changeRole((int) $id, (int) $request->input('role_id'), (int) Session::get('user_id'));
        Session::flash('success', 'Rôle modifié.');
        Response::redirect('/users/' . $id);
    }

    public function suspend(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);
        $this->service->suspend((int) $id, (int) Session::get('user_id'));
        Response::redirect('/users/' . $id);
    }

    public function reactivate(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);
        $this->service->reactivate((int) $id, (int) Session::get('user_id'));
        Response::redirect('/users/' . $id);
    }

    public function resetPassword(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);
        $temporary = $this->service->resetPasswordByAdmin((int) $id, (int) Session::get('user_id'));
        Session::flash('success', 'Mot de passe temporaire généré : ' . $temporary . ' (communiquez-le à l\'utilisateur de façon sécurisée).');
        Response::redirect('/users/' . $id);
    }

    public function destroy(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);
        $this->service->delete((int) $id, (int) Session::get('user_id'));
        Response::redirect('/users');
    }
}
