<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Domain\Category\CategoryRepository;

final class CategoryController
{
    public function __construct(private CategoryRepository $repository = new CategoryRepository())
    {
    }

    public function index(Request $request): void
    {
        Response::view('categories/index', [
            'categories' => $this->repository->all(),
        ]);
    }

    public function store(Request $request): void
    {
        Csrf::verifyRequestOrFail($request);

        $validator = new Validator($request->all());
        $validator->required('name', 'Le nom');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Response::redirect('/categories');
        }

        $parentId = $request->input('parent_id') ? (int) $request->input('parent_id') : null;
        $id       = $this->repository->create((string) $request->input('name'), $parentId);

        Logger::record('category.create', (int) Session::get('user_id'), 'category', $id);
        Session::flash('success', 'Catégorie créée.');
        Response::redirect('/categories');
    }

    public function update(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);

        $validator = new Validator($request->all());
        $validator->required('name', 'Le nom');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Response::redirect('/categories');
        }

        $parentId = $request->input('parent_id') ? (int) $request->input('parent_id') : null;
        $this->repository->update((int) $id, (string) $request->input('name'), $parentId);

        Logger::record('category.update', (int) Session::get('user_id'), 'category', (int) $id);
        Session::flash('success', 'Catégorie mise à jour.');
        Response::redirect('/categories');
    }

    public function destroy(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);
        $this->repository->delete((int) $id);
        Logger::record('category.delete', (int) Session::get('user_id'), 'category', (int) $id);
        Session::flash('success', 'Catégorie supprimée (les produits liés sont maintenant sans catégorie).');
        Response::redirect('/categories');
    }
}
