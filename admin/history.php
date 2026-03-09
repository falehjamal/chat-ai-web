<?php

use App\Core\View;
use App\Modules\Admin\Application\AdminAuthService;
use App\Modules\History\Application\HistoryService;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$auth = new AdminAuthService();
if (!$auth->hasAnyAdmin()) {
    View::redirect('/admin/setup.php');
}
$auth->requireAuth('/admin/login.php');

$historyService = new HistoryService();
$filters = [
    'page' => max(1, (int) ($_GET['page'] ?? 1)),
    'limit' => max(10, min(100, (int) ($_GET['limit'] ?? 20))),
    'ip' => $_GET['ip'] ?? null,
    'mode' => $_GET['mode'] ?? null,
    'start_date' => $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days')),
    'end_date' => $_GET['end_date'] ?? date('Y-m-d'),
];

$history = $historyService->paginated($filters);
$stats = $historyService->stats($filters['start_date'], $filters['end_date']);

View::render('admin/history', [
    'pageTitle' => 'History',
    'pageSubtitle' => 'Riwayat chat kini diproteksi oleh auth admin dan diambil dari module history.',
    'currentPage' => 'history',
    'currentUser' => $auth->currentUser(),
    'filters' => $filters,
    'history' => $history,
    'stats' => $stats,
], 'admin/layout');
