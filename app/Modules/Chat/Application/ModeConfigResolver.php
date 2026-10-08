<?php

namespace App\Modules\Chat\Application;

use App\Core\Env;
use App\Modules\Admin\Infrastructure\AIConfigRepository;
use App\Modules\Chat\Domain\PublicChatContract;

class ModeConfigResolver
{
    private $configRepository;

    public function __construct()
    {
        $this->configRepository = new AIConfigRepository();
    }

    public function resolve($modeKey)
    {
        $legacy = PublicChatContract::mode($modeKey);
        $resolved = $this->configRepository->resolvedModeConfig($modeKey);

        if (!$resolved) {
            return $this->buildLegacyFallback($modeKey, $legacy);
        }

        return [
            'modeKey' => $modeKey,
            'label' => $legacy['label'],
            'endpoint' => $legacy['endpoint'],
            'localStorageKey' => $legacy['local_storage_key'],
            'historyStrategy' => $resolved['history_strategy'],
            'historyLimit' => (int) $resolved['history_limit'],
            'acceptsImage' => (bool) $resolved['accepts_image'],
            'ocrStrategy' => $resolved['ocr_strategy'],
            'systemPrompt' => $resolved['system_prompt'],
            'modelKey' => $resolved['model_key'],
            'modelLabel' => $resolved['model_label'],
            'apiModel' => $resolved['api_model'],
            'temperature' => (float) $resolved['temperature'],
            'maxTokens' => (int) $resolved['max_tokens'],
            'useMaxCompletionTokens' => (bool) $resolved['use_max_completion_tokens'],
            'supportsVision' => (bool) $resolved['supports_vision'],
            'providerKey' => $resolved['provider_key'],
            'providerLabel' => $resolved['provider_label'],
            'providerDriver' => $resolved['driver'],
            'providerBaseUrl' => $this->providerBaseUrl($resolved),
            'providerApiKeyEnvVar' => $this->providerApiKeyEnvVar($resolved),
        ];
    }

    public function frontendRuntimeConfig()
    {
        $modes = [];
        foreach (array_keys(PublicChatContract::modes()) as $modeKey) {
            $modes[$modeKey] = $this->resolve($modeKey);
        }

        return PublicChatContract::frontendRuntimeConfig($modes, []);
    }

    public function publicRuntimeConfig()
    {
        $modes = [];
        foreach (array_keys(PublicChatContract::modes()) as $modeKey) {
            $resolved = $this->resolve($modeKey);
            $modes[$modeKey] = [
                'label' => $resolved['label'],
                'endpoint' => $resolved['endpoint'],
                'localStorageKey' => $resolved['localStorageKey'],
                'historyLimit' => $resolved['historyLimit'],
                'modelKey' => $resolved['modelKey'],
            ];
        }

        return [
            'defaultMode' => 'default',
            'localStorageKeys' => PublicChatContract::localStorageKeys(),
            'modes' => $modes,
        ];
    }

    private function buildLegacyFallback($modeKey, array $legacy)
    {
        return [
            'modeKey' => $modeKey,
            'label' => $legacy['label'],
            'endpoint' => $legacy['endpoint'],
            'localStorageKey' => $legacy['local_storage_key'],
            'historyStrategy' => $legacy['history_strategy'],
            'historyLimit' => (int) $legacy['history_limit'],
            'acceptsImage' => (bool) $legacy['accepts_image'],
            'ocrStrategy' => $legacy['ocr_strategy'],
            'systemPrompt' => $legacy['system_prompt'],
            'modelKey' => $legacy['default_model_key'],
            'modelLabel' => $legacy['default_model_key'],
            'apiModel' => $legacy['default_model_key'],
            'temperature' => 0.3,
            'maxTokens' => 4096,
            'useMaxCompletionTokens' => false,
            'supportsVision' => $modeKey === 'uas-math',
            'providerKey' => '9router',
            'providerLabel' => '9router',
            'providerDriver' => 'openai_compatible',
            'providerBaseUrl' => rtrim((string) Env::get('NINEROUTER_URL', 'http://127.0.0.1:20128/v1'), '/'),
            'providerApiKeyEnvVar' => 'NINEROUTER_KEY',
        ];
    }

    private function providerBaseUrl(array $resolved)
    {
        if (($resolved['provider_key'] ?? '') === '9router') {
            $fromEnv = trim((string) Env::get('NINEROUTER_URL', ''));
            if ($fromEnv !== '') {
                return rtrim($fromEnv, '/');
            }
        }

        return rtrim($resolved['base_url'], '/');
    }

    private function providerApiKeyEnvVar(array $resolved)
    {
        if (($resolved['provider_key'] ?? '') === '9router') {
            return 'NINEROUTER_KEY';
        }

        return $resolved['api_key_env_var'];
    }
}
