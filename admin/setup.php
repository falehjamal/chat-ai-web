<?php

use App\Core\Csrf;
use App\Core\View;
use App\Modules\Admin\Application\AdminAuthService;
use App\Modules\Admin\Application\AuditLogService;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$auth = new AdminAuthService();
if ($auth->hasAnyAdmin()) {
    View::redirect('/admin/login.php');
}

$errorMessage = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        Csrf::requireValid($_POST['_csrf'] ?? '');
        $adminId = $auth->createInitialAdmin(
            $_POST['username'] ?? '',
            $_POST['password'] ?? '',
            $_POST['display_name'] ?? ''
        );

        (new AuditLogService())->log($adminId, 'admin_users', $adminId, 'create_initial_admin', null, [
            'username' => strtolower(trim($_POST['username'] ?? '')),
            'display_name' => trim($_POST['display_name'] ?? ''),
        ]);

        View::redirect('/admin/index.php');
    } catch (Throwable $throwable) {
        $errorMessage = $throwable->getMessage();
    }
}

View::render('admin/setup', [
    'pageTitle' => 'Setup Admin',
    'isAuthPage' => true,
    'csrfToken' => Csrf::token(),
    'errorMessage' => $errorMessage,
], 'admin/layout');
