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

        $useResponsesApi = $this->requiresResponsesApi($modeConfig['apiModel']);
        $payload = $useResponsesApi
            ? $this->buildResponsesPayload($modeConfig, $messages)
            : $this->buildChatCompletionsPayload($modeConfig, $messages);

        $endpoint = $useResponsesApi ? '/responses' : '/chat/completions';
        $url = rtrim($modeConfig['providerBaseUrl'], '/') . $endpoint;

        $fullResponse = '';
        $rawApiResponse = '';
        $done = false;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'User-Agent: ChatAI-Web/3.0',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (
            &$fullResponse,
            &$rawApiResponse,
            &$done,
            $useResponsesApi,
            $onChunk,
            $onComplete
        ) {
            if (strlen($rawApiResponse) < 50000) {
                $rawApiResponse .= $chunk;
            }

            $lines = explode("\n", $chunk);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, 'data: ') !== 0) {
                    continue;
                }

                $data = trim(substr($line, 6));
                if (!$useResponsesApi && $data === '[DONE]') {
                    $done = true;
                    $onComplete($fullResponse);
                    continue;
                }

                $json = json_decode($data, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }

                $content = $this->extractStreamDelta($json, $useResponsesApi);
                if ($content !== null && $content !== '') {
                    $fullResponse .= $content;
                    $onChunk($content, $fullResponse);
                }

                if ($useResponsesApi && ($json['type'] ?? '') === 'response.completed') {
                    $done = true;
                    $onComplete($fullResponse);
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

    private function requiresResponsesApi($apiModel)
    {
        return stripos($apiModel, '-pro') !== false || stripos($apiModel, 'codex') !== false;
    }

    private function isReasoningModel($apiModel)
    {
        $model = strtolower(trim($apiModel));

        if (strpos($model, 'gpt-5-chat') !== false) {
            return false;
        }

        return (bool) preg_match('/^(o[134]|gpt-5)/', $model);
    }

    private function supportsTemperature($apiModel)
    {
        return !$this->isReasoningModel($apiModel);
    }

    private function buildChatCompletionsPayload(array $modeConfig, array $messages)
    {
        $payload = [
            'model' => $modeConfig['apiModel'],
            'messages' => $messages,
            'stream' => true,
        ];

        if ($this->supportsTemperature($modeConfig['apiModel'])) {
            $payload['temperature'] = (float) $modeConfig['temperature'];
        }

        if (!empty($modeConfig['useMaxCompletionTokens'])) {
            $payload['max_completion_tokens'] = (int) $modeConfig['maxTokens'];
            if ($this->isReasoningModel($modeConfig['apiModel'])) {
                $payload['reasoning_effort'] = 'high';
            }
        } else {
            $payload['max_tokens'] = (int) $modeConfig['maxTokens'];
        }

        return $payload;
    }

    private function buildResponsesPayload(array $modeConfig, array $messages)
    {
        $payload = [
            'model' => $modeConfig['apiModel'],
            'input' => $this->normalizeMessagesForResponsesApi($messages),
            'stream' => true,
            'max_output_tokens' => (int) $modeConfig['maxTokens'],
        ];

        if (!empty($modeConfig['useMaxCompletionTokens']) && $this->isReasoningModel($modeConfig['apiModel'])) {
            $payload['reasoning'] = ['effort' => 'high'];
        }

        return $payload;
    }

    private function normalizeMessagesForResponsesApi(array $messages)
    {
        $normalized = [];

        foreach ($messages as $message) {
            if (!is_array($message) || !isset($message['role'])) {
                continue;
            }

            $content = $message['content'] ?? '';
            if (!is_array($content)) {
                $normalized[] = [
                    'role' => $message['role'],
                    'content' => $content,
                ];
                continue;
            }

            $blocks = [];
            foreach ($content as $block) {
                if (!is_array($block) || !isset($block['type'])) {
                    continue;
                }

                if ($block['type'] === 'text' && isset($block['text'])) {
                    $blocks[] = [
                        'type' => 'input_text',
                        'text' => $block['text'],
                    ];
                    continue;
                }

                if ($block['type'] === 'image_url' && isset($block['image_url']['url'])) {
                    $imageBlock = [
                        'type' => 'input_image',
                        'image_url' => $block['image_url']['url'],
                    ];

                    if (!empty($block['image_url']['detail'])) {
                        $imageBlock['detail'] = $block['image_url']['detail'];
                    }

                    $blocks[] = $imageBlock;
                }
            }

            $normalized[] = [
                'role' => $message['role'],
                'content' => $blocks,
            ];
        }

        return $normalized;
    }

    private function extractStreamDelta(array $json, $useResponsesApi)
    {
        if ($useResponsesApi) {
            if (($json['type'] ?? '') === 'response.output_text.delta' && isset($json['delta'])) {
                return $json['delta'];
            }

            return null;
        }

        if (isset($json['choices'][0]['delta']['content'])) {
            return $json['choices'][0]['delta']['content'];
        }

        return null;
    }
}
