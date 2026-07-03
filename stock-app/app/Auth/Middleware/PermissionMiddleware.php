<?php

declare(strict_types=1);

namespace App\Auth\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\Role\PermissionRepository;

/**
 * Middleware paramétré : instancié avec le code de permission requis par la route.
 * Exemple d'usage dans les routes : new PermissionMiddleware('product.create')
 */
final class PermissionMiddleware
{
    public function __construct(private string $permissionCode)
    {
    }

    public function handle(Request $request): void
    {
        $userId = Session::get('user_id');
        $roleId = Session::get('role_id');

        $repository = new PermissionRepository();
        if (!$roleId || !$repository->roleHasPermission((int) $roleId, $this->permissionCode)) {
            if ($request->isAjax()) {
                Response::json(['error' => 'forbidden'], 403);
            }
            http_response_code(403);
            Response::view('errors/403', [], 'app');
        }
    }
}
