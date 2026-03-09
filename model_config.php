<?php

use App\Modules\Admin\Infrastructure\AIConfigRepository;
use App\Modules\Chat\Application\ModeConfigResolver;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'bootstrap.php';

class ModelConfig
{
    public static function getApiConfig($key = 'gpt-5.2')
    {
        $repository = new AIConfigRepository();
        $model = $repository->findModelByKey($key);

        if (!$model) {
            return [
                'model' => 'gpt-5.2',
                'max_tokens' => 4096,
                'temperature' => 0.3,
                'use_max_completion_tokens' => true,
            ];
        }

        return [
            'model' => $model['api_model'],
            'max_tokens' => (int) $model['max_tokens'],
            'temperature' => (float) $model['temperature'],
            'use_max_completion_tokens' => (bool) $model['use_max_completion_tokens'],
        ];
    }

    public static function getDefaultModelForMode($mode = 'default')
    {
        $resolver = new ModeConfigResolver();
        return $resolver->resolve($mode)['modelKey'];
    }

    public static function isValidModel($key)
    {
        $repository = new AIConfigRepository();
        return $repository->findModelByKey($key) !== null;
    }

    public static function estimateTokenCount($text)
    {
        if (empty($text)) {
            return 0;
        }

        $text = trim(preg_replace('/\s+/', ' ', $text));
        return max(1, (int) ceil(mb_strlen($text, 'UTF-8') / 3.5));
    }

    public static function estimateConversationTokens($userMessage, $botResponse)
    {
        return self::estimateTokenCount($userMessage)
            + self::estimateTokenCount($botResponse)
            + 10;
    }
}
