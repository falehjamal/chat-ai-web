<?php

use App\Core\Session;
use App\Core\View;
use App\Modules\Admin\Application\AdminAuthService;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$auth = new AdminAuthService();
$auth->logout();
Session::destroy();
View::redirect('/admin/login.php');
