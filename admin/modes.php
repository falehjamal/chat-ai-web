<?php

use App\Core\Csrf;
use App\Core\View;
use App\Modules\Admin\Application\AdminAuthService;
use App\Modules\Admin\Application\AuditLogService;
use App\Modules\Admin\Infrastructure\AIConfigRepository;
use App\Modules\Chat\Application\ModeConfigResolver;
use App\Modules\Chat\Domain\PublicChatContract;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$auth = new AdminAuthService();
if (!$auth->hasAnyAdmin()) {
    View::redirect('/admin/setup.php');
}
$auth->requireAuth('/admin/login.php');

$repository = new AIConfigRepository();
$auditLog = new AuditLogService();
$modeResolver = new ModeConfigResolver();
$currentUser = $auth->currentUser();
$successMessage = null;
$errorMessage = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        Csrf::requireValid($_POST['_csrf'] ?? '');

        $modeKey = $_POST['mode_key'] ?? 'default';
        if (!PublicChatContract::isValidMode($modeKey)) {
            throw new RuntimeException('Mode tidak dikenal.');
        }

        $selectedModel = $repository->findModel((int) ($_POST['model_id'] ?? 0));
        if (!$selectedModel) {
            throw new RuntimeException('Model binding tidak ditemukan.');
        }

        if ($modeKey === 'uas-math' && empty($selectedModel['supports_vision'])) {
            throw new RuntimeException('Mode `uas-math` hanya boleh memakai model yang mendukung vision.');
        }

        $before = $repository->modeBindings()[$modeKey] ?? null;
        $repository->saveModeBinding($modeKey, $_POST);
        $after = $repository->resolvedModeConfig($modeKey);
        $auditLog->log($currentUser['id'], 'mode_bindings', $modeKey, $before ? 'update' : 'create', $before, $after);
        $successMessage = 'Mode binding `' . $modeKey . '` berhasil disimpan.';
    } catch (Throwable $throwable) {
        $errorMessage = $throwable->getMessage();
    }
}

View::render('admin/modes', [
    'pageTitle' => 'Mode Bindings',
    'pageSubtitle' => 'Map tiap mode publik ke provider, model, prompt, dan policy runtime.',
    'currentPage' => 'modes',
    'currentUser' => $currentUser,
    'csrfToken' => Csrf::token(),
    'models' => $repository->allModels(),
    'runtimeModes' => $modeResolver->frontendRuntimeConfig()['modes'],
    'successMessage' => $successMessage,
    'errorMessage' => $errorMessage,
], 'admin/layout');
