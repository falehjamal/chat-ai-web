<?php

namespace App\Modules\AI\Infrastructure;

use App\Core\Env;
use App\Modules\AI\Contracts\ProviderAdapterInterface;
use Exception;

class OpenAICompatibleProvider implements ProviderAdapterInterface
{
    public function stream(array $modeConfig, array $messages, callable $onChunk, callable $onComplete)
    {
        $apiKeyEnvVar = $modeConfig['providerApiKeyEnvVar'];
        $apiKey = Env::get($apiKeyEnvVar);

        if (!$apiKey || $apiKey === 'your_openai_api_key_here') {
            throw new Exception('API key provider tidak dikonfigurasi dengan benar pada env `' . $apiKeyEnvVar . '`.');
        }

        $payload = [
            'model' => $modeConfig['apiModel'],
            'messages' => $messages,
            'stream' => true,
            'temperature' => (float) $modeConfig['temperature'],
        ];

        if (!empty($modeConfig['useMaxCompletionTokens'])) {
            $payload['max_completion_tokens'] = (int) $modeConfig['maxTokens'];
        } else {
            $payload['max_tokens'] = (int) $modeConfig['maxTokens'];
        }

        $fullResponse = '';
        $rawApiResponse = '';
        $done = false;
        $url = rtrim($modeConfig['providerBaseUrl'], '/') . '/chat/completions';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'User-Agent: ChatAI-Web/3.0',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$fullResponse, &$rawApiResponse, &$done, $onChunk, $onComplete) {
            if (strlen($rawApiResponse) < 50000) {
                $rawApiResponse .= $chunk;
            }

            $lines = explode("\n", $chunk);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, 'data: ') !== 0) {
                    continue;
                }

                $payload = trim(substr($line, 6));
                if ($payload === '[DONE]') {
                    $done = true;
                    $onComplete($fullResponse);
                    continue;
                }

                $json = json_decode($payload, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }

                if (isset($json['choices'][0]['delta']['content'])) {
                    $content = $json['choices'][0]['delta']['content'];
                    $fullResponse .= $content;
                    $onChunk($content, $fullResponse);
                }
            }

            return strlen($chunk);
        });

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('Network error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            $details = null;
            $decoded = json_decode($rawApiResponse, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['error'])) {
                $details = $decoded['error']['message'] ?? json_encode($decoded['error']);
            } elseif ($rawApiResponse !== '') {
                $details = mb_substr(trim($rawApiResponse), 0, 2000, 'UTF-8');
            }

            throw new Exception('API request failed with status: ' . $httpCode . ($details ? ' - ' . $details : ''));
        }

        if (!$done && $fullResponse !== '') {
            $onComplete($fullResponse);
        }

        if ($fullResponse === '') {
            throw new Exception('No response received from AI');
        }

        return $fullResponse;
    }
}
