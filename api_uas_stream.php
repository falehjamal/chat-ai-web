<?php

use App\Modules\Chat\Application\StreamChatService;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'bootstrap.php';

(new StreamChatService('uas'))->handle();
