<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\Backup\BackupService;
use App\Domain\CustomField\CustomFieldRepository;
use App\Domain\CustomField\CustomFieldService;

final class SettingsController
{
    public function __construct(
        private CustomFieldService $customFields = new CustomFieldService(),
        private CustomFieldRepository $customFieldRepo = new CustomFieldRepository(),
        private BackupService $backups = new BackupService(__DIR__ . '/../../../database/backups')
    ) {
    }

    public function index(Request $request): void
    {
        Response::view('settings/index', [
            'productFields' => $this->customFieldRepo->allForEntity('product'),
            'stockEntryFields' => $this->customFieldRepo->allForEntity('stock_entry'),
            'backups' => $this->backups->listBackups(),
        ]);
    }

    public function addCustomField(Request $request): void
    {
        Csrf::verifyRequestOrFail($request);

        $options = $request->input('options');
        $optionsArray = $options ? array_map('trim', explode(',', (string) $options)) : null;

        $this->customFields->addDefinition(
            (string) $request->input('entity'),
            (string) $request->input('label'),
            (string) $request->input('field_type'),
            $optionsArray,
            (bool) $request->input('is_required', false),
            (int) Session::get('user_id')
        );

        Session::flash('success', 'Champ personnalisé ajouté.');
        Response::redirect('/settings');
    }

    public function removeCustomField(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);
        $this->customFields->removeDefinition((int) $id, (int) Session::get('user_id'));
        Response::redirect('/settings');
    }

    public function deleteCustomField(Request $request, string $id): void
    {
        Csrf::verifyRequestOrFail($request);
        $this->customFields->deleteDefinition((int) $id, (int) Session::get('user_id'));
        Session::flash('success', 'Champ supprimé définitivement.');
        Response::redirect('/settings');
    }

    public function runBackup(Request $request): void
    {
        Csrf::verifyRequestOrFail($request);
        $this->backups->run((int) Session::get('user_id'));
        Session::flash('success', 'Sauvegarde effectuée.');
        Response::redirect('/settings');
    }

    public function downloadBackup(Request $request, string $filename): void
    {
        $safeName = basename($filename);
        $path = __DIR__ . '/../../../database/backups/' . $safeName;
        if (!str_ends_with($safeName, '.sql') || !file_exists($path)) {
            http_response_code(404);
            exit('Fichier introuvable.');
        }
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $safeName . '"');
        readfile($path);
        exit;
    }
}
