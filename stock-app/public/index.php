<?php

declare(strict_types=1);

// Librairies tierces (PHPMailer, etc.)
$vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

// Autoloader PSR-4 de l'application
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require $path;
    }
});

use App\Auth\Middleware\AuthMiddleware;
use App\Auth\Middleware\PermissionMiddleware;
use App\Core\Config;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Http\Api\ProductApiController;
use App\Http\Api\StockApiController;
use App\Http\Api\UserPreferenceApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\StockEntryController;
use App\Http\Controllers\StockExitController;
use App\Http\Controllers\UserController;

$config = Config::all();

if ($config['app']['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

Session::start();

$auth = new AuthMiddleware();
$perm = fn(string $code) => new PermissionMiddleware($code);

$router = new Router();

// --- Routes publiques (jamais de page publique en dehors de celles-ci) ---
$router->get('/login', fn(Request $r) => (new AuthController())->showLogin($r));
$router->post('/login', fn(Request $r) => (new AuthController())->login($r));
$router->get('/logout', fn(Request $r) => (new AuthController())->logout($r), [$auth]);

$router->get('/invitation/{uuid}', fn(Request $r, string $u) => (new InvitationController())->showAccept($r, $u));
$router->post('/invitation/{uuid}', fn(Request $r, string $u) => (new InvitationController())->accept($r, $u));

$router->get('/password/forgot', fn(Request $r) => (new PasswordController())->showForgot($r));
$router->post('/password/forgot', fn(Request $r) => (new PasswordController())->sendResetLink($r));
$router->get('/password/reset/{token}', fn(Request $r, string $t) => (new PasswordController())->showReset($r, $t));
$router->post('/password/reset/{token}', fn(Request $r, string $t) => (new PasswordController())->reset($r, $t));
$router->get('/password/change-required', fn(Request $r) => (new PasswordController())->showChangeRequired($r), [$auth]);
$router->post('/password/change-required', fn(Request $r) => (new PasswordController())->changeRequired($r), [$auth]);

// --- Dashboard ---
$router->get('/dashboard', fn(Request $r) => (new DashboardController())->index($r), [$auth, $perm('dashboard.view')]);
$router->get('/', fn(Request $r) => \App\Core\Response::redirect('/dashboard'), [$auth]);

// --- Produits ---
$router->get('/products', fn(Request $r) => (new ProductController())->index($r), [$auth, $perm('product.view')]);
$router->get('/products/create', fn(Request $r) => (new ProductController())->create($r), [$auth, $perm('product.create')]);
$router->post('/products', fn(Request $r) => (new ProductController())->store($r), [$auth, $perm('product.create')]);
$router->get('/products/{id}', fn(Request $r, string $id) => (new ProductController())->show($r, $id), [$auth, $perm('product.view')]);
$router->get('/products/{id}/edit', fn(Request $r, string $id) => (new ProductController())->edit($r, $id), [$auth, $perm('product.edit')]);
$router->post('/products/{id}', fn(Request $r, string $id) => (new ProductController())->update($r, $id), [$auth, $perm('product.edit')]);
$router->post('/products/{id}/archive', fn(Request $r, string $id) => (new ProductController())->archive($r, $id), [$auth, $perm('product.archive')]);
$router->post('/products/{id}/reactivate', fn(Request $r, string $id) => (new ProductController())->reactivate($r, $id), [$auth, $perm('product.archive')]);

// --- Entrées de stock ---
$router->get('/stock-entries', fn(Request $r) => (new StockEntryController())->index($r), [$auth, $perm('stock.entry.view')]);
$router->get('/stock-entries/create', fn(Request $r) => (new StockEntryController())->create($r), [$auth, $perm('stock.entry.create')]);
$router->post('/stock-entries', fn(Request $r) => (new StockEntryController())->store($r), [$auth, $perm('stock.entry.create')]);
$router->get('/stock-entries/{id}/edit', fn(Request $r, string $id) => (new StockEntryController())->edit($r, $id), [$auth, $perm('stock.entry.create')]);
$router->post('/stock-entries/{id}/edit', fn(Request $r, string $id) => (new StockEntryController())->update($r, $id), [$auth, $perm('stock.entry.create')]);
$router->get('/stock-entries/{id}/print-label', fn(Request $r, string $id) => (new StockEntryController())->printLabel($r, $id), [$auth, $perm('stock.entry.create')]);

// --- Catégories ---
$router->get('/categories', fn(Request $r) => (new CategoryController())->index($r), [$auth, $perm('product.view')]);
$router->post('/categories', fn(Request $r) => (new CategoryController())->store($r), [$auth, $perm('product.create')]);
$router->post('/categories/{id}', fn(Request $r, string $id) => (new CategoryController())->update($r, $id), [$auth, $perm('product.edit')]);
$router->post('/categories/{id}/delete', fn(Request $r, string $id) => (new CategoryController())->destroy($r, $id), [$auth, $perm('product.archive')]);

// --- Sorties de stock / scanner ---
$router->get('/stock-exits', fn(Request $r) => (new StockExitController())->index($r), [$auth, $perm('stock.exit.view')]);
$router->get('/stock-exits/lookup', fn(Request $r) => (new StockExitController())->lookupByBarcode($r), [$auth, $perm('stock.exit.create')]);
$router->post('/stock-exits', fn(Request $r) => (new StockExitController())->store($r), [$auth, $perm('stock.exit.create')]);
$router->get('/scanner', fn(Request $r) => (new ScannerController())->index($r), [$auth, $perm('product.view')]);
$router->get('/scanner/lookup', fn(Request $r) => (new ScannerController())->lookup($r), [$auth, $perm('product.view')]);

// --- Utilisateurs / invitations / rôles ---
$router->get('/users', fn(Request $r) => (new UserController())->index($r), [$auth, $perm('user.manage')]);
$router->get('/users/invitations', fn(Request $r) => (new InvitationController())->index($r), [$auth, $perm('invitation.manage')]);
$router->post('/users/invitations', fn(Request $r) => (new InvitationController())->store($r), [$auth, $perm('invitation.manage')]);
$router->post('/users/invitations/{id}/revoke', fn(Request $r, string $id) => (new InvitationController())->revoke($r, $id), [$auth, $perm('invitation.manage')]);
$router->get('/users/{id}', fn(Request $r, string $id) => (new UserController())->show($r, $id), [$auth, $perm('user.manage')]);
$router->post('/users/{id}', fn(Request $r, string $id) => (new UserController())->update($r, $id), [$auth, $perm('user.manage')]);
$router->post('/users/{id}/role', fn(Request $r, string $id) => (new UserController())->changeRole($r, $id), [$auth, $perm('user.manage')]);
$router->post('/users/{id}/suspend', fn(Request $r, string $id) => (new UserController())->suspend($r, $id), [$auth, $perm('user.manage')]);
$router->post('/users/{id}/reactivate', fn(Request $r, string $id) => (new UserController())->reactivate($r, $id), [$auth, $perm('user.manage')]);
$router->post('/users/{id}/reset-password', fn(Request $r, string $id) => (new UserController())->resetPassword($r, $id), [$auth, $perm('user.manage')]);
$router->post('/users/{id}/delete', fn(Request $r, string $id) => (new UserController())->destroy($r, $id), [$auth, $perm('user.manage')]);

$router->get('/roles', fn(Request $r) => (new RoleController())->index($r), [$auth, $perm('role.manage')]);
$router->post('/roles', fn(Request $r) => (new RoleController())->store($r), [$auth, $perm('role.manage')]);
$router->post('/roles/{id}/permissions', fn(Request $r, string $id) => (new RoleController())->updatePermissions($r, $id), [$auth, $perm('role.manage')]);
$router->post('/roles/{id}/delete', fn(Request $r, string $id) => (new RoleController())->destroy($r, $id), [$auth, $perm('role.manage')]);

// --- Logs ---
$router->get('/logs', fn(Request $r) => (new LogController())->index($r), [$auth, $perm('log.view')]);

// --- Impressions / exports ---
$router->get('/print', fn(Request $r) => (new PrintController())->index($r), [$auth, $perm('print.manage')]);
$router->get('/print/inventory', fn(Request $r) => (new PrintController())->inventory($r), [$auth, $perm('print.manage')]);
$router->get('/print/products', fn(Request $r) => (new PrintController())->productList($r), [$auth, $perm('print.manage')]);
$router->get('/print/expiring', fn(Request $r) => (new PrintController())->expiringList($r), [$auth, $perm('print.manage')]);
$router->get('/print/label-single', fn(Request $r) => (new PrintController())->labelSingle($r), [$auth, $perm('print.manage')]);
$router->get('/print/labels', fn(Request $r) => (new PrintController())->labels($r), [$auth, $perm('print.manage')]);
$router->post('/print/labels', fn(Request $r) => (new PrintController())->labels($r), [$auth, $perm('print.manage')]);
$router->get('/export/products.csv', fn(Request $r) => (new ExportController())->productsCsv($r), [$auth, $perm('export.csv')]);
$router->get('/export/products.xlsx', fn(Request $r) => (new ExportController())->productsXlsx($r), [$auth, $perm('export.xlsx')]);

// --- Paramètres (champs personnalisés, sauvegardes) ---
$router->get('/settings', fn(Request $r) => (new SettingsController())->index($r), [$auth, $perm('settings.manage')]);
$router->post('/settings/custom-fields', fn(Request $r) => (new SettingsController())->addCustomField($r), [$auth, $perm('settings.manage')]);
$router->post('/settings/custom-fields/{id}/remove', fn(Request $r, string $id) => (new SettingsController())->removeCustomField($r, $id), [$auth, $perm('settings.manage')]);
$router->post('/settings/custom-fields/{id}/delete', fn(Request $r, string $id) => (new SettingsController())->deleteCustomField($r, $id), [$auth, $perm('settings.manage')]);
$router->post('/settings/backup', fn(Request $r) => (new SettingsController())->runBackup($r), [$auth, $perm('backup.manage')]);
$router->get('/settings/backup/{filename}', fn(Request $r, string $f) => (new SettingsController())->downloadBackup($r, $f), [$auth, $perm('backup.manage')]);

// --- API JSON (datatables, préférences, scanner) ---
$router->get('/api/products', fn(Request $r) => (new ProductApiController())->list($r), [$auth, $perm('product.view')]);
$router->get('/api/stock-entries', fn(Request $r) => (new StockApiController())->listEntries($r), [$auth, $perm('stock.entry.view')]);
$router->get('/api/preferences/{tableKey}', fn(Request $r, string $k) => (new UserPreferenceApiController())->get($r, $k), [$auth]);
$router->post('/api/preferences/{tableKey}', fn(Request $r, string $k) => (new UserPreferenceApiController())->save($r, $k), [$auth]);

$router->dispatch(new Request());
