<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\Role\PermissionRepository;
use App\Domain\Role\RoleRepository;

final class RoleController
{
    public function __construct(
        private RoleRepository $roles = new RoleRepository(),
        private PermissionRepository $permissions = new PermissionRepository()
    ) {
    }

    public function index(Request $request): void
    {
        $roles = $this->roles->all();
        $permissionsByRole = [];
        foreach ($roles as $role) {
            $permissionsByRole[$role['id']] = $this->permissions->permissionsForRole((int) $role['id']);
        }

        Response::view('roles/index', [
            'roles' => $roles,
            'allPermissions' => $this->permissions->allPermissions(),
            'permissionsByRole' => $permissionsByRole,
        ]);
    }

    public function store(Request $request): void
    {
        Csrf::verifyRequestOrFail($request);
        $name = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', (string) $request->input('name', '')));
        $this->roles->create($name, (string) $request->input('label'));
        Session::flash('success', 'Rôle créé.');
        Response::redirect('/roles');
    }

    public function updatePermissions(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);
        $permissionIds = (array) $request->input('permission_ids', []);
        $this->permissions->setRolePermissions((int) $id, $permissionIds);
        Session::flash('success', 'Permissions mises à jour.');
        Response::redirect('/roles');
    }

    public function destroy(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);
        $deleted = $this->roles->delete((int) $id);
        Session::flash($deleted ? 'success' : 'error', $deleted ? 'Rôle supprimé.' : 'Impossible de supprimer un rôle système.');
        Response::redirect('/roles');
    }
}
