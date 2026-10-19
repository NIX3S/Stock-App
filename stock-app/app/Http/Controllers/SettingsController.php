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

    public function restoreBackup(Request $request): void
    {
        Csrf::verifyRequestOrFail($request);

        $uploadedFile = $_FILES['backup_file'] ?? null;
        if (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Erreur lors de l\'upload du fichier.');
            Response::redirect('/settings');
        }

        $tmpPath  = $uploadedFile['tmp_name'];
        $origName = basename($uploadedFile['name']);

        // Vérification extension
        if (!str_ends_with(strtolower($origName), '.sql')) {
            Session::flash('error', 'Seuls les fichiers .sql sont acceptés.');
            Response::redirect('/settings');
        }

        // Vérification taille max : 50 Mo
        if ($uploadedFile['size'] > 50 * 1024 * 1024) {
            Session::flash('error', 'Fichier trop volumineux (max 50 Mo).');
            Response::redirect('/settings');
        }

        // Vérification contenu : doit commencer par du SQL reconnaissable
        $firstBytes = file_get_contents($tmpPath, false, null, 0, 512);
        if (!preg_match('/^\s*(--|CREATE|INSERT|SET|DROP|USE|BEGIN|LOCK|UNLOCK|\/\*)/i', (string) $firstBytes)) {
            Session::flash('error', 'Le fichier ne ressemble pas à un dump SQL valide.');
            Response::redirect('/settings');
        }

        // Copie dans le dossier backups avant restore
        $destName = 'restore_' . date('Y-m-d_His') . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', $origName);
        $destPath = __DIR__ . '/../../../database/backups/' . $destName;
        if (!move_uploaded_file($tmpPath, $destPath)) {
            Session::flash('error', 'Impossible de sauvegarder le fichier uploadé.');
            Response::redirect('/settings');
        }

        $success = $this->backups->restore($destPath, (int) Session::get('user_id'));

        if ($success) {
            Session::flash('success', 'Base de données restaurée avec succès depuis « ' . htmlspecialchars($origName) . ' ».');
        } else {
            Session::flash('error', 'La restauration a échoué. Vérifiez les logs ou le fichier SQL.');
        }

        Response::redirect('/settings');
    }

    public function deleteBackup(Request $request, string $filename): void
    {
        Csrf::verifyRequestOrFail($request);
        $deleted = $this->backups->deleteBackup(basename($filename));
        if ($deleted) {
            Session::flash('success', 'Sauvegarde supprimée.');
        } else {
            Session::flash('error', 'Impossible de supprimer la sauvegarde.');
        }
        Response::redirect('/settings');
    }
}
