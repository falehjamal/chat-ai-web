<?php

use App\Core\Csrf;
use App\Core\ErrorPresenter;
use App\Core\View;
use App\Modules\Admin\Application\AdminAuthService;
use App\Modules\Admin\Application\AuditLogService;
use App\Modules\Admin\Infrastructure\AIConfigRepository;
use App\Modules\AI\Application\NineRouterCatalogSync;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$auth = new AdminAuthService();
if (!$auth->hasAnyAdmin()) {
    View::redirect('/admin/setup.php');
}
$auth->requireAuth('/admin/login.php');

$repository = new AIConfigRepository();
$auditLog = new AuditLogService();
$currentUser = $auth->currentUser();
$successMessage = null;
$errorMessage = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        Csrf::requireValid($_POST['_csrf'] ?? '');
        if (($_POST['action'] ?? '') === 'sync') {
            $count = (new NineRouterCatalogSync())->sync();
            $auditLog->log($currentUser['id'], 'ai_models', '9router', 'sync', null, ['count' => $count]);
            $successMessage = 'Katalog 9router disinkronkan. ' . $count . ' model aktif.';
        } else {
        $before = !empty($_POST['id']) ? $repository->findModel((int) $_POST['id']) : null;
        $modelId = $repository->saveModel($_POST);
        $after = $repository->findModel($modelId);
        $auditLog->log($currentUser['id'], 'ai_models', $modelId, $before ? 'update' : 'create', $before, $after);
        $successMessage = 'Model berhasil disimpan.';
        }
    } catch (Throwable $throwable) {
        $errorMessage = ErrorPresenter::message($throwable);
    }
}

$editingModel = null;
if (!empty($_GET['edit'])) {
    $editingModel = $repository->findModel((int) $_GET['edit']);
}

View::render('admin/models', [
    'pageTitle' => 'Model 9router',
    'pageSubtitle' => 'Model diambil dari 9router lokal. Sinkronkan ulang jika katalog di router berubah.',
    'currentPage' => 'models',
    'currentUser' => $currentUser,
    'csrfToken' => Csrf::token(),
    'providers' => $repository->allProviders(),
    'models' => $repository->allModels(),
    'editingModel' => $editingModel,
    'successMessage' => $successMessage,
    'errorMessage' => $errorMessage,
], 'admin/layout');
