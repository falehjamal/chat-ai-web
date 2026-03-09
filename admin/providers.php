<?php

use App\Core\Csrf;
use App\Core\View;
use App\Modules\Admin\Application\AdminAuthService;
use App\Modules\Admin\Application\AuditLogService;
use App\Modules\Admin\Infrastructure\AIConfigRepository;

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
        $before = !empty($_POST['id']) ? $repository->findProvider((int) $_POST['id']) : null;
        $providerId = $repository->saveProvider($_POST);
        $after = $repository->findProvider($providerId);
        $auditLog->log($currentUser['id'], 'ai_providers', $providerId, $before ? 'update' : 'create', $before, $after);
        $successMessage = 'Provider berhasil disimpan.';
    } catch (Throwable $throwable) {
        $errorMessage = $throwable->getMessage();
    }
}

$editingProvider = null;
if (!empty($_GET['edit'])) {
    $editingProvider = $repository->findProvider((int) $_GET['edit']);
}

View::render('admin/providers', [
    'pageTitle' => 'Providers',
    'pageSubtitle' => 'Kelola provider AI dan env key yang digunakan runtime.',
    'currentPage' => 'providers',
    'currentUser' => $currentUser,
    'csrfToken' => Csrf::token(),
    'providers' => $repository->allProviders(),
    'editingProvider' => $editingProvider,
    'successMessage' => $successMessage,
    'errorMessage' => $errorMessage,
], 'admin/layout');
