<?php

namespace App\Modules\Chat\Application;

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
            'providerBaseUrl' => rtrim($resolved['base_url'], '/'),
            'providerApiKeyEnvVar' => $resolved['api_key_env_var'],
        ];
    }

    public function frontendRuntimeConfig()
    {
        $modes = [];
        foreach (PublicChatContract::modes() as $modeKey => $mode) {
            $modes[$modeKey] = $this->resolve($modeKey);
        }

        return PublicChatContract::frontendRuntimeConfig(
            $modes,
            $this->configRepository->runtimeModels()
        );
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
            'modelLabel' => strtoupper($legacy['default_model_key']),
            'apiModel' => $legacy['default_model_key'],
            'temperature' => 0.3,
            'maxTokens' => 4096,
            'useMaxCompletionTokens' => true,
            'supportsVision' => $modeKey === 'uas-math',
            'providerKey' => 'openai',
            'providerLabel' => 'OpenAI',
            'providerDriver' => 'openai_compatible',
            'providerBaseUrl' => 'https://api.openai.com/v1',
            'providerApiKeyEnvVar' => 'OPENAI_API_KEY',
        ];
    }
}
