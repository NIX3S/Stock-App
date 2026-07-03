<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Domain\CustomField\CustomFieldService;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductService;
use App\Domain\Stock\StockService;

final class ProductController
{
    public function __construct(
        private ProductRepository $repository = new ProductRepository(),
        private ProductService $service = new ProductService(),
        private CustomFieldService $customFields = new CustomFieldService(),
        private StockService $stockService = new StockService()
    ) {
    }

    public function index(Request $request): void
    {
        Response::view('products/index', []);
    }

    public function create(Request $request): void
    {
        Response::view('products/form', [
            'product' => null,
            'customFields' => $this->customFields->definitionsFor('product'),
        ]);
    }

    public function store(Request $request): void
    {
        Csrf::verifyRequestOrFail($request);

        $validator = new Validator($request->all());
        $validator->required('name', 'Le nom')->numeric('min_stock_threshold', 'Le seuil minimum');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Response::redirect('/products/create');
        }

        $photoPath = $this->handlePhotoUpload($request);

        $id = $this->service->create([
            'name' => $request->input('name'),
            'reference' => $request->input('reference'),
            'barcode' => $request->input('barcode') ?: null,
            'category_id' => $request->input('category_id') ?: null,
            'description' => $request->input('description'),
            'unit' => $request->input('unit', 'unité'),
            'min_stock_threshold' => (int) $request->input('min_stock_threshold', 0),
            'photo_path' => $photoPath,
        ], (int) Session::get('user_id'));

        $this->customFields->saveValues('product', $id, (array) $request->input('custom_fields', []));

        Session::flash('success', 'Produit créé.');
        Response::redirect('/products/' . $id);
    }

    public function show(Request $request, string $id): void
    {
        $product = $this->repository->findById((int) $id);
        if (!$product) {
            http_response_code(404);
            Response::view('errors/404', [], 'app');
        }

        Response::view('products/show', [
            'product' => $product,
            'customFieldValues' => $this->customFields->valuesFor('product', (int) $id),
            'stockEntries' => $this->stockService->entriesForProduct((int) $id),
        ]);
    }

    public function edit(Request $request, string $id): void
    {
        $product = $this->repository->findById((int) $id);
        if (!$product) {
            http_response_code(404);
            Response::view('errors/404', [], 'app');
        }
        Response::view('products/form', [
            'product' => $product,
            'customFields' => $this->customFields->definitionsFor('product'),
        ]);
    }

    public function update(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);

        $photoPath = $this->handlePhotoUpload($request);
        $data = [
            'name' => $request->input('name'),
            'reference' => $request->input('reference'),
            'barcode' => $request->input('barcode') ?: null,
            'category_id' => $request->input('category_id') ?: null,
            'description' => $request->input('description'),
            'unit' => $request->input('unit', 'unité'),
            'min_stock_threshold' => (int) $request->input('min_stock_threshold', 0),
        ];
        if ($photoPath) {
            $data['photo_path'] = $photoPath;
        }

        $this->service->update((int) $id, $data, (int) Session::get('user_id'));
        $this->customFields->saveValues('product', (int) $id, (array) $request->input('custom_fields', []));

        Session::flash('success', 'Produit mis à jour.');
        Response::redirect('/products/' . $id);
    }

    public function archive(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);
        $this->service->archive((int) $id, (int) Session::get('user_id'));
        Response::redirect('/products');
    }

    public function reactivate(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);
        $this->service->reactivate((int) $id, (int) Session::get('user_id'));
        Response::redirect('/products');
    }

    private function handlePhotoUpload(Request $request): ?string
    {
        $file = $request->file('photo');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowedTypes[$mime]) || $file['size'] > 5 * 1024 * 1024) {
            return null;
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
        $destination = dirname(__DIR__, 3) . '/public/uploads/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return 'uploads/' . $filename;
        }
        return null;
    }
}
