<?php

namespace App\Core;

class View
{
    public static function render($template, array $data = [], $layout = null)
    {
        $viewsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views';
        $templatePath = $viewsPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $template) . '.php';

        if (!is_file($templatePath)) {
            throw new \RuntimeException('View tidak ditemukan: ' . $template);
        }

        if ($layout === null) {
            extract($data, EXTR_SKIP);
            include $templatePath;
            return;
        }

        $layoutPath = $viewsPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $layout) . '.php';
        if (!is_file($layoutPath)) {
            throw new \RuntimeException('Layout tidak ditemukan: ' . $layout);
        }

        $viewData = $data;
        $contentTemplate = $templatePath;
        include $layoutPath;
    }

    public static function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }
}
