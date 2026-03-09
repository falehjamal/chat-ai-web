<?php

use App\Core\Csrf;
use App\Core\View;
use App\Modules\Admin\Application\AdminAuthService;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$auth = new AdminAuthService();
if (!$auth->hasAnyAdmin()) {
    View::redirect('/admin/setup.php');
}

if ($auth->currentUser()) {
    View::redirect('/admin/index.php');
}

$errorMessage = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        Csrf::requireValid($_POST['_csrf'] ?? '');
        $success = $auth->attempt(trim($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''));
        if ($success) {
            View::redirect('/admin/index.php');
        }
        $errorMessage = 'Username atau password salah.';
    } catch (Throwable $throwable) {
        $errorMessage = $throwable->getMessage();
    }
}

View::render('admin/login', [
    'pageTitle' => 'Login Admin',
    'isAuthPage' => true,
    'csrfToken' => Csrf::token(),
    'errorMessage' => $errorMessage,
], 'admin/layout');
