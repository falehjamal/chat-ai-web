<?php
/**
 * Konfigurasi Model Terpusat
 * 
 * Semua mode menggunakan GPT-5.2 sebagai default.
 * File ini menyediakan konfigurasi API untuk backend streaming endpoints.
 */

class ModelConfig {

    private static $models = [
        'gpt-5.2' => [
            'api_model' => 'gpt-5.2',
            'max_tokens' => 4096,
            'temperature' => 0.3,
            'use_max_completion_tokens' => true
        ],
    ];

    /**
     * Mendapatkan konfigurasi untuk API berdasarkan key
     */
    public static function getApiConfig($key = 'gpt-5.2') {
        $model = self::$models[$key] ?? null;
        if (!$model) {
            $model = self::$models['gpt-5.2'];
        }

        return [
            'model' => $model['api_model'],
            'max_tokens' => $model['max_tokens'],
            'temperature' => $model['temperature'],
            'use_max_completion_tokens' => $model['use_max_completion_tokens'] ?? false
        ];
    }

    /**
     * Mendapatkan model default — selalu gpt-5.2
     */
    public static function getDefaultModelForMode($mode = 'default') {
        return 'gpt-5.2';
    }

    /**
     * Validasi apakah model key valid
     */
    public static function isValidModel($key) {
        return isset(self::$models[$key]);
    }

    /**
     * Estimasi jumlah token dari teks (approximate)
     */
    public static function estimateTokenCount($text) {
        if (empty($text)) return 0;
        $text = trim(preg_replace('/\s+/', ' ', $text));
        $charCount = mb_strlen($text, 'UTF-8');
        return max(1, (int) ceil($charCount / 3.5));
    }

    /**
     * Estimasi total token untuk conversation (user + response)
     */
    public static function estimateConversationTokens($userMessage, $botResponse) {
        return self::estimateTokenCount($userMessage)
             + self::estimateTokenCount($botResponse)
             + 10; // overhead
    }
}
