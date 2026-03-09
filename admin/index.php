<?php

use App\Core\View;
use App\Modules\Admin\Application\AdminAuthService;
use App\Modules\Admin\Infrastructure\AIConfigRepository;
use App\Modules\Chat\Application\ModeConfigResolver;
use App\Modules\History\Application\HistoryService;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$auth = new AdminAuthService();
if (!$auth->hasAnyAdmin()) {
    View::redirect('/admin/setup.php');
}

$auth->requireAuth('/admin/login.php');

$historyService = new HistoryService();
$configRepository = new AIConfigRepository();
$modeResolver = new ModeConfigResolver();

$today = date('Y-m-d');
$last30Days = date('Y-m-d', strtotime('-30 days'));

View::render('admin/dashboard', [
    'pageTitle' => 'Dashboard',
    'pageSubtitle' => 'Ringkasan kontrak publik dan konfigurasi runtime yang sedang aktif.',
    'currentPage' => 'dashboard',
    'currentUser' => $auth->currentUser(),
    'stats' => $historyService->stats($last30Days, $today),
    'providers' => $configRepository->allProviders(),
    'models' => $configRepository->allModels(),
    'modeBindings' => $configRepository->modeBindings(),
    'runtimeModes' => $modeResolver->frontendRuntimeConfig()['modes'],
], 'admin/layout');
