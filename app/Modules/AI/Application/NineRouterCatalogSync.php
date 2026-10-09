<?php

namespace App\Modules\AI\Application;

use App\Core\DatabaseManager;
use App\Core\Env;
use App\Core\PublicException;
use App\Modules\Chat\Domain\PublicChatContract;
use PDO;

class NineRouterCatalogSync
{
    public function sync()
    {
        $baseUrl = rtrim((string) Env::get('NINEROUTER_URL', 'http://127.0.0.1:20128/v1'), '/');
        $apiKey = (string) Env::get('NINEROUTER_KEY', '');
        $catalog = $this->fetchCatalog($baseUrl, $apiKey);

        $pdo = DatabaseManager::connection();
        $existingBindings = $pdo->query(
            'SELECT mb.mode_key, mb.system_prompt, mb.history_strategy, mb.history_limit, mb.accepts_image, mb.ocr_strategy, m.model_key
             FROM mode_bindings mb
             LEFT JOIN ai_models m ON m.id = mb.model_id'
        )->fetchAll(PDO::FETCH_ASSOC);

        $pdo->beginTransaction();

        try {
            $pdo->exec('DELETE FROM mode_bindings');
            $pdo->exec('DELETE FROM ai_models');
            $pdo->exec('DELETE FROM ai_providers');

            $providerStmt = $pdo->prepare(
                'INSERT INTO ai_providers (provider_key, label, driver, base_url, api_key_env_var, is_active)
                 VALUES (?, ?, ?, ?, ?, 1)'
            );
            $providerStmt->execute(['9router', '9router', 'openai_compatible', $baseUrl, 'NINEROUTER_KEY']);
            $providerId = (int) $pdo->lastInsertId();

            $insertModel = $pdo->prepare(
                'INSERT INTO ai_models (provider_id, model_key, api_model, label, temperature, max_tokens, use_max_completion_tokens, supports_vision, is_active)
                 VALUES (?, ?, ?, ?, 0.30, ?, 0, ?, 1)'
            );

            $count = 0;
            foreach ($catalog as $model) {
                $id = trim((string) ($model['id'] ?? ''));
                if ($id === '' || strlen($id) > 100) {
                    continue;
                }

                $capabilities = is_array($model['capabilities'] ?? null) ? $model['capabilities'] : [];
                $maxTokens = (int) ($model['max_completion_tokens'] ?? $capabilities['maxOutput'] ?? 4096);
                if ($maxTokens < 1024) {
                    $maxTokens = 1024;
                }
                if ($maxTokens > 16384) {
                    $maxTokens = 16384;
                }

                $insertModel->execute([
                    $providerId,
                    $id,
                    $id,
                    $id,
                    $maxTokens,
                    !empty($capabilities['vision']) ? 1 : 0,
                ]);
                $count++;
            }

            if ($count === 0) {
                throw new PublicException('Katalog model 9router kosong.');
            }

            $this->restoreBindings($pdo, $existingBindings);
            $pdo->commit();
        } catch (\Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($throwable instanceof PublicException) {
                throw $throwable;
            }

            throw new PublicException('Gagal menyimpan katalog 9router.');
        }

        return $count;
    }

    private function fetchCatalog($baseUrl, $apiKey)
    {
        $ch = curl_init($baseUrl . '/models');
        $headers = ['Accept: application/json'];
        if ($apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false || $curlError !== '') {
            throw new PublicException('Tidak bisa menghubungi 9router.');
        }

        $decoded = json_decode($body, true);
        if ($httpCode !== 200 || !is_array($decoded['data'] ?? null)) {
            throw new PublicException('9router menolak permintaan daftar model.');
        }

        return $decoded['data'];
    }

    private function restoreBindings(PDO $pdo, array $existingBindings)
    {
        $indexed = [];
        foreach ($existingBindings as $binding) {
            $indexed[$binding['mode_key']] = $binding;
        }

        $defaults = [
            'default' => 'cu/gpt-5.2',
            'uas' => 'cu/gpt-5-mini',
            'uas-math' => 'cu/gpt-5.2',
        ];

        $findModel = $pdo->prepare('SELECT id, supports_vision FROM ai_models WHERE model_key = ?');
        $insert = $pdo->prepare(
            'INSERT INTO mode_bindings (mode_key, model_id, system_prompt, history_strategy, history_limit, accepts_image, ocr_strategy)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );

        foreach (PublicChatContract::modes() as $modeKey => $mode) {
            $previous = $indexed[$modeKey] ?? null;
            $savedModelKey = trim((string) ($previous['model_key'] ?? ''));
            $modelKey = $savedModelKey !== '' ? $savedModelKey : ($defaults[$modeKey] ?? 'cu/gpt-5.2');

            if ($modeKey === 'uas-math' && ($modelKey === '' || strpos($modelKey, 'cu/') === 0)) {
                $visionModelKey = $this->preferredImageModelKey($pdo);
                if ($visionModelKey !== null) {
                    $modelKey = $visionModelKey;
                }
            }

            $findModel->execute([$modelKey]);
            $model = $findModel->fetch(PDO::FETCH_ASSOC);

            if (!$model) {
                $model = $pdo->query(
                    'SELECT id, supports_vision FROM ai_models ORDER BY supports_vision DESC, id ASC LIMIT 1'
                )->fetch(PDO::FETCH_ASSOC);
            }

            if (!$model) {
                throw new PublicException('Tidak ada model 9router yang bisa diikat ke mode.');
            }

            $insert->execute([
                $modeKey,
                (int) $model['id'],
                $previous['system_prompt'] ?? $mode['system_prompt'],
                $previous['history_strategy'] ?? $mode['history_strategy'],
                (int) ($previous['history_limit'] ?? $mode['history_limit']),
                (int) ($previous['accepts_image'] ?? ($mode['accepts_image'] ? 1 : 0)),
                $previous['ocr_strategy'] ?? $mode['ocr_strategy'],
            ]);
        }
    }

    private function preferredImageModelKey(PDO $pdo)
    {
        $preferred = [
            'ag/gemini-3.8-flash-high',
            'ag/gemini-3.8-flash',
            'ag/gemini-3.6-flash-high',
        ];
        $find = $pdo->prepare('SELECT model_key FROM ai_models WHERE model_key = ? AND is_active = 1 LIMIT 1');

        foreach ($preferred as $modelKey) {
            $find->execute([$modelKey]);
            $found = $find->fetchColumn();
            if ($found) {
                return (string) $found;
            }
        }

        $found = $pdo->query(
            "SELECT model_key FROM ai_models WHERE model_key LIKE 'ag/%' AND supports_vision = 1 AND is_active = 1 ORDER BY model_key ASC LIMIT 1"
        )->fetchColumn();

        return $found ? (string) $found : null;
    }
}
