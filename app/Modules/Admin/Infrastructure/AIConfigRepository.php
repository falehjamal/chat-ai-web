<?php

namespace App\Modules\Admin\Infrastructure;

use App\Core\DatabaseManager;
use App\Modules\Chat\Domain\PublicChatContract;
use PDO;

class AIConfigRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = DatabaseManager::connection();
    }

    public function allProviders()
    {
        $stmt = $this->pdo->query('SELECT * FROM ai_providers ORDER BY provider_key ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findProvider($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ai_providers WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function saveProvider(array $payload)
    {
        $payload['provider_key'] = $this->normalizeKey($payload['provider_key'] ?? '');

        if (!empty($payload['id'])) {
            $stmt = $this->pdo->prepare(
                'UPDATE ai_providers
                 SET provider_key = ?, label = ?, driver = ?, base_url = ?, api_key_env_var = ?, is_active = ?
                 WHERE id = ?'
            );
            $stmt->execute([
                $payload['provider_key'],
                $payload['label'],
                $payload['driver'],
                rtrim($payload['base_url'], '/'),
                strtoupper($payload['api_key_env_var']),
                !empty($payload['is_active']) ? 1 : 0,
                (int) $payload['id'],
            ]);

            return (int) $payload['id'];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO ai_providers (provider_key, label, driver, base_url, api_key_env_var, is_active)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $payload['provider_key'],
            $payload['label'],
            $payload['driver'],
            rtrim($payload['base_url'], '/'),
            strtoupper($payload['api_key_env_var']),
            !empty($payload['is_active']) ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function allModels()
    {
        $stmt = $this->pdo->query(
            'SELECT m.*, p.provider_key, p.label AS provider_label
             FROM ai_models m
             INNER JOIN ai_providers p ON p.id = m.provider_id
             ORDER BY p.provider_key ASC, m.model_key ASC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activeModels()
    {
        $stmt = $this->pdo->query(
            'SELECT m.*, p.provider_key, p.label AS provider_label, p.driver, p.base_url, p.api_key_env_var, p.is_active AS provider_is_active
             FROM ai_models m
             INNER JOIN ai_providers p ON p.id = m.provider_id
             WHERE m.is_active = 1 AND p.is_active = 1
             ORDER BY p.provider_key ASC, m.model_key ASC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findModel($id)
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.*, p.provider_key, p.label AS provider_label
             FROM ai_models m
             INNER JOIN ai_providers p ON p.id = m.provider_id
             WHERE m.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findModelByKey($modelKey)
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.*, p.provider_key, p.label AS provider_label, p.driver, p.base_url, p.api_key_env_var
             FROM ai_models m
             INNER JOIN ai_providers p ON p.id = m.provider_id
             WHERE m.model_key = ?'
        );
        $stmt->execute([$modelKey]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function saveModel(array $payload)
    {
        $payload['model_key'] = $this->normalizeKey($payload['model_key'] ?? '');

        if (!empty($payload['id'])) {
            $stmt = $this->pdo->prepare(
                'UPDATE ai_models
                 SET provider_id = ?, model_key = ?, api_model = ?, label = ?, temperature = ?, max_tokens = ?, use_max_completion_tokens = ?, supports_vision = ?, is_active = ?
                 WHERE id = ?'
            );
            $stmt->execute([
                (int) $payload['provider_id'],
                $payload['model_key'],
                $payload['api_model'],
                $payload['label'],
                (float) $payload['temperature'],
                (int) $payload['max_tokens'],
                !empty($payload['use_max_completion_tokens']) ? 1 : 0,
                !empty($payload['supports_vision']) ? 1 : 0,
                !empty($payload['is_active']) ? 1 : 0,
                (int) $payload['id'],
            ]);

            return (int) $payload['id'];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO ai_models (provider_id, model_key, api_model, label, temperature, max_tokens, use_max_completion_tokens, supports_vision, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (int) $payload['provider_id'],
            $payload['model_key'],
            $payload['api_model'],
            $payload['label'],
            (float) $payload['temperature'],
            (int) $payload['max_tokens'],
            !empty($payload['use_max_completion_tokens']) ? 1 : 0,
            !empty($payload['supports_vision']) ? 1 : 0,
            !empty($payload['is_active']) ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function modeBindings()
    {
        $stmt = $this->pdo->query(
            'SELECT mb.*, m.model_key, m.label AS model_label, m.supports_vision, p.provider_key, p.label AS provider_label
             FROM mode_bindings mb
             INNER JOIN ai_models m ON m.id = mb.model_id
             INNER JOIN ai_providers p ON p.id = m.provider_id
             ORDER BY mb.mode_key ASC'
        );

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['mode_key']] = $row;
        }

        return $indexed;
    }

    public function saveModeBinding($modeKey, array $payload)
    {
        if (!PublicChatContract::isValidMode($modeKey)) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO mode_bindings (mode_key, model_id, system_prompt, history_strategy, history_limit, accepts_image, ocr_strategy)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                model_id = VALUES(model_id),
                system_prompt = VALUES(system_prompt),
                history_strategy = VALUES(history_strategy),
                history_limit = VALUES(history_limit),
                accepts_image = VALUES(accepts_image),
                ocr_strategy = VALUES(ocr_strategy)'
        );

        return $stmt->execute([
            $modeKey,
            (int) $payload['model_id'],
            $payload['system_prompt'],
            $payload['history_strategy'],
            (int) $payload['history_limit'],
            !empty($payload['accepts_image']) ? 1 : 0,
            $payload['ocr_strategy'],
        ]);
    }

    public function resolvedModeConfig($modeKey)
    {
        $stmt = $this->pdo->prepare(
            'SELECT mb.mode_key, mb.system_prompt, mb.history_strategy, mb.history_limit, mb.accepts_image, mb.ocr_strategy,
                    m.id AS model_id, m.model_key, m.api_model, m.label AS model_label, m.temperature, m.max_tokens,
                    m.use_max_completion_tokens, m.supports_vision, p.id AS provider_id, p.provider_key, p.label AS provider_label,
                    p.driver, p.base_url, p.api_key_env_var
             FROM mode_bindings mb
             INNER JOIN ai_models m ON m.id = mb.model_id
             INNER JOIN ai_providers p ON p.id = m.provider_id
             WHERE mb.mode_key = ? AND m.is_active = 1 AND p.is_active = 1'
        );
        $stmt->execute([$modeKey]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function runtimeModes()
    {
        $resolved = [];
        foreach (PublicChatContract::modes() as $modeKey => $mode) {
            $resolved[$modeKey] = $this->resolvedModeConfig($modeKey);
        }

        return $resolved;
    }

    public function runtimeModels()
    {
        $models = [];
        foreach ($this->activeModels() as $model) {
            $models[$model['model_key']] = [
                'modelKey' => $model['model_key'],
                'label' => $model['label'],
                'providerKey' => $model['provider_key'],
                'supportsVision' => (bool) $model['supports_vision'],
                'temperature' => (float) $model['temperature'],
                'maxTokens' => (int) $model['max_tokens'],
            ];
        }

        return $models;
    }

    private function normalizeKey($value)
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9\-_.]+/', '-', $value);
        return trim($value, '-');
    }
}
