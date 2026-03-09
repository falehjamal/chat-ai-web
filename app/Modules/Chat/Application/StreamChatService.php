<?php

namespace App\Modules\Chat\Application;

use App\Core\Request;
use App\Core\SseEmitter;
use App\Modules\AI\Application\ProviderRegistry;
use App\Modules\Chat\Domain\PublicChatContract;
use App\Modules\Chat\Infrastructure\ChatHistoryRepository;
use Exception;

class StreamChatService
{
    private $modeKey;
    private $request;
    private $emitter;
    private $modeResolver;
    private $providerRegistry;
    private $historyRepository;

    public function __construct($modeKey)
    {
        $this->modeKey = PublicChatContract::isValidMode($modeKey) ? $modeKey : 'default';
        $this->request = new Request();
        $this->emitter = new SseEmitter();
        $this->modeResolver = new ModeConfigResolver();
        $this->providerRegistry = new ProviderRegistry();
        $this->historyRepository = new ChatHistoryRepository();
    }

    public function handle()
    {
        $this->emitter->start();

        try {
            $modeConfig = $this->modeResolver->resolve($this->modeKey);
            $payload = $this->payload($modeConfig);
            $messages = $this->buildMessages($modeConfig, $payload['message'], $payload['history'], $payload['image']);
            $provider = $this->providerRegistry->resolve($modeConfig['providerDriver']);
            $completed = false;

            $this->emitter->send(['type' => 'typing_start', 'mode' => $this->modeKey], 'status');

            if ($this->modeKey === 'uas-math') {
                $this->emitter->send(['status' => 'Menganalisis gambar dan memproses soal matematika...'], 'status');
            }

            $provider->stream(
                $modeConfig,
                $messages,
                function ($content, $fullText) {
                    $this->emitter->send([
                        'type' => 'chunk',
                        'content' => $content,
                        'full_text' => $fullText,
                        'mode' => $this->modeKey,
                    ], 'chunk');
                },
                function ($fullText) use (&$completed, $payload, $modeConfig) {
                    if ($completed) {
                        return;
                    }

                    $completed = true;
                    $this->emitter->send(['type' => 'typing_end', 'full_text' => $fullText], 'status');
                    $this->persistConversation($payload['message_for_log'], $fullText, $modeConfig);
                    $this->emitter->send([
                        'type' => 'complete',
                        'mode' => $this->modeKey,
                        'status' => 'success',
                    ], 'complete');
                }
            );
        } catch (Exception $exception) {
            $this->emitter->send(['error' => $exception->getMessage()], 'error');
        }
    }

    private function payload(array $modeConfig)
    {
        $json = $this->request->json();

        if (!empty($json)) {
            $message = trim((string) ($json['message'] ?? ''));
            $history = is_array($json['history'] ?? null) ? $json['history'] : [];
            $image = (string) ($json['image'] ?? '');
        } else {
            $message = trim((string) $this->request->query('message', ''));
            $history = json_decode((string) $this->request->query('history', '[]'), true);
            $history = is_array($history) ? $history : [];
            $image = (string) $this->request->query('image', '');
        }

        if ($modeConfig['acceptsImage']) {
            if ($message === '' && $image === '') {
                throw new Exception('Mode OCR High memerlukan gambar atau pesan teks');
            }
        } elseif ($message === '') {
            throw new Exception('No message provided');
        }

        return [
            'message' => $message,
            'message_for_log' => $message !== '' ? $message : 'Gambar soal matematika',
            'history' => $history,
            'image' => $image,
        ];
    }

    private function buildMessages(array $modeConfig, $message, array $history, $imageBase64)
    {
        $messages = [
            [
                'role' => 'system',
                'content' => $modeConfig['systemPrompt'],
            ],
        ];

        if ($modeConfig['historyStrategy'] === 'recent_window' && !empty($history)) {
            $historyWindow = array_slice($this->normalizeHistory($history), -1 * max(0, (int) $modeConfig['historyLimit']));
            foreach ($historyWindow as $historyMessage) {
                $messages[] = $historyMessage;
            }
        }

        if ($modeConfig['acceptsImage'] && $imageBase64 !== '') {
            $messages[] = [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $message !== '' ? $message : 'Tolong analisis dan selesaikan soal dalam gambar ini.',
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $imageBase64,
                            'detail' => 'high',
                        ],
                    ],
                ],
            ];

            return $messages;
        }

        $messages[] = [
            'role' => 'user',
            'content' => $message !== '' ? $message : 'Tolong bantu saya dengan soal ini.',
        ];

        return $messages;
    }

    private function normalizeHistory(array $history)
    {
        $normalized = [];

        foreach ($history as $item) {
            if (!is_array($item) || !isset($item['sender']) || !isset($item['text'])) {
                continue;
            }

            $text = trim((string) $item['text']);
            if ($text === '') {
                continue;
            }

            $normalized[] = [
                'role' => $item['sender'] === 'user' ? 'user' : 'assistant',
                'content' => $text,
            ];
        }

        return $normalized;
    }

    private function persistConversation($userMessage, $response, array $modeConfig)
    {
        $this->historyRepository->save(
            $userMessage,
            $response,
            $this->getRealIpAddress(),
            $this->modeKey,
            $this->estimateConversationTokens($userMessage, $response),
            $modeConfig['modelKey'],
            $modeConfig['providerKey']
        );
    }

    private function estimateConversationTokens($userMessage, $botResponse)
    {
        return $this->estimateTokenCount($userMessage) + $this->estimateTokenCount($botResponse) + 10;
    }

    private function estimateTokenCount($text)
    {
        if (!$text) {
            return 0;
        }

        $text = trim(preg_replace('/\s+/', ' ', $text));
        return max(1, (int) ceil(mb_strlen($text, 'UTF-8') / 3.5));
    }

    private function getRealIpAddress()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $candidate = trim($ips[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return 'unknown';
    }
}
