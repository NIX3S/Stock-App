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
use App\Domain\Stock\StockService;

final class StockEntryController
{
    public function __construct(
        private StockService       $service      = new StockService(),
        private ProductRepository  $products     = new ProductRepository(),
        private CustomFieldService $customFields = new CustomFieldService()
    ) {
    }

    public function edit(Request $request, string $id): void
    {
        $entry = (new \App\Domain\Stock\StockEntryRepository())->findById((int) $id);
        if (!$entry) {
            http_response_code(404);
            Response::view('errors/404', [], 'app');
        }
        $product = $this->products->findById((int) $entry['product_id']);
        Response::view('stock-entries/edit', [
            'entry'   => $entry,
            'product' => $product,
        ]);
    }

    public function update(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);

        $data = [];
        foreach (['expiry_date', 'expiry_type', 'origin', 'comment', 'entry_date'] as $field) {
            $data[$field] = $request->input($field);
        }
        // Correction de quantité restante (optionnel, champ présent dans le formulaire)
        if ($request->input('remaining_quantity') !== null) {
            $data['remaining_quantity'] = (int) $request->input('remaining_quantity');
        }

        $this->service->updateEntry((int) $id, $data, (int) Session::get('user_id'));
        Session::flash('success', 'Entrée de stock mise à jour.');
        Response::redirect('/stock-entries');
    }

    public function index(Request $request): void
    {
        Response::view('stock-entries/index', []);
    }

    public function create(Request $request): void
    {
        Response::view('stock-entries/form', [
            'customFields'    => $this->customFields->definitionsFor('stock_entry'),
            'presetProductId' => $request->query('product_id'),
        ]);
    }

    public function store(Request $request): void
    {
        Csrf::verifyRequestOrFail($request);

        $validator = new Validator($request->all());
        $validator
            ->required('product_id', 'Le produit')
            ->required('quantity',   'La quantité')
            ->numeric('quantity',    'La quantité')
            ->required('entry_date', 'La date d\'entrée')
            ->date('entry_date',     'La date d\'entrée');

        if ($request->input('expiry_date')) {
            $validator->date('expiry_date', 'La date d\'échéance');
        }

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Response::redirect('/stock-entries/create');
        }

        $productId = (int) $request->input('product_id');

        $entryId = $this->service->recordEntry([
            'product_id'  => $productId,
            'quantity'    => (int) $request->input('quantity'),
            'entry_date'  => $request->input('entry_date'),
            'expiry_date' => $request->input('expiry_date') ?: null,
            'expiry_type' => $request->input('expiry_type') ?: null,
            'origin'      => $request->input('origin'),
            'comment'     => $request->input('comment'),
        ], (int) Session::get('user_id'), (array) $request->input('custom_fields', []));

        $action = $request->input('action', 'save');

        if ($action === 'save_and_print') {
            // Redirige vers la génération d'étiquette pour ce produit précis
            Session::flash('success', 'Entrée enregistrée. Voici l\'étiquette à imprimer.');
            Response::redirect('/print/label-single?product_id=' . $productId);
        }

        Session::flash('success', 'Entrée de stock enregistrée.');
        Response::redirect('/stock-entries');
    }
}
