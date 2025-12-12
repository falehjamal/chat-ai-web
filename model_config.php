<?php
/**
 * Konfigurasi Model Terpusat
 * 
 * File ini berisi konfigurasi semua model AI yang tersedia dalam aplikasi.
 * Penambahan atau pengeditan model cukup dilakukan di file ini dan akan
 * otomatis berdampak ke semua modul yang menggunakan model tersebut.
 */

class ModelConfig {
    /**
     * Daftar semua model yang tersedia
     * Format: 'key' => [
     *     'name' => 'Nama yang ditampilkan',
     *     'description' => 'Deskripsi model',
     *     'api_model' => 'Nama model untuk API',
     *     'max_tokens' => 'Maksimal token untuk model ini',
     *     'temperature' => 'Suhu default untuk model ini',
     *     'enabled' => true/false,
     *     'recommended_for' => ['mode1', 'mode2'], // Mode yang direkomendasikan
     *     'pricing_tier' => 'low|medium|high', // Tingkat biaya
     *     'price_per_1m_tokens' => 'Harga per 1 juta token dalam USD'
     * ]
     */
    private static $models = [
        'gpt-5.2' => [
            'name' => 'GPT-5.2',
            'description' => 'Model Paling Canggih (Default untuk semua mode)',
            'api_model' => 'gpt-5.2',
            'max_tokens' => 4096,
            'temperature' => 0.3,
            'enabled' => true,
            'recommended_for' => ['default', 'uas', 'uas-math', 'ocr'],
            'pricing_tier' => 'high',
            'price_per_1m_tokens' => '$18.00',
            'use_max_completion_tokens' => true
        ],
        'gpt-5.1' => [
            'name' => 'GPT-5.1',
            'description' => 'Model Terbaru & Akurat (Alternatif)',
            'api_model' => 'gpt-5.1',
            'max_tokens' => 4096,
            'temperature' => 0.3,
            'enabled' => true,
            'recommended_for' => ['default', 'uas', 'uas-math', 'ocr'],
            'pricing_tier' => 'high',
            'price_per_1m_tokens' => '$15.00',
            'use_max_completion_tokens' => true
        ],
        'gpt-5-nano' => [
            'name' => 'GPT-5 Nano',
            'description' => 'Model ringan dan ekonomis',
            'api_model' => 'gpt-5-nano-2025-08-07',
            'max_tokens' => 2048,
            'temperature' => 1,
            'enabled' => true,
            'recommended_for' => ['default'],
            'pricing_tier' => 'low',
            'price_per_1m_tokens' => '$0.50',
            'use_max_completion_tokens' => true
        ],
    ];


    /**
     * Mendapatkan semua model yang aktif
     */
    public static function getActiveModels() {
        return array_filter(self::$models, function($model) {
            return $model['enabled'];
        });
    }

    /**
     * Mendapatkan model berdasarkan key
     */
    public static function getModel($key) {
        return self::$models[$key] ?? null;
    }

    /**
     * Mendapatkan nama API model berdasarkan key
     */
    public static function getApiModelName($key) {
        $model = self::getModel($key);
        return $model ? $model['api_model'] : 'gpt-5.2'; // fallback
    }

    /**
     * Mendapatkan konfigurasi untuk API berdasarkan key
     */
    public static function getApiConfig($key) {
        $model = self::getModel($key);
        if (!$model) {
            // Fallback configuration - gunakan gpt-5.2 sebagai default
            return [
                'model' => 'gpt-5.2',
                'max_tokens' => 4096,
                'temperature' => 0.3,
                'use_max_completion_tokens' => true
            ];
        }

        return [
            'model' => $model['api_model'],
            'max_tokens' => $model['max_tokens'],
            'temperature' => $model['temperature'],
            'use_max_completion_tokens' => $model['use_max_completion_tokens'] ?? false
        ];
    }

    /**
     * Mendapatkan model yang direkomendasikan untuk mode tertentu
     */
    public static function getRecommendedModels($mode) {
        $recommended = [];
        foreach (self::getActiveModels() as $key => $model) {
            if (in_array($mode, $model['recommended_for'])) {
                $recommended[$key] = $model;
            }
        }
        return $recommended;
    }

    /**
     * Mendapatkan model default untuk mode tertentu
     */
    public static function getDefaultModelForMode($mode) {
        $recommended = self::getRecommendedModels($mode);
        
        // Return first recommended model, or fallback
        if (!empty($recommended)) {
            return array_keys($recommended)[0];
        }

        // Fallback berdasarkan mode - semua mode menggunakan gpt-5.2 sebagai default
        switch ($mode) {
            case 'uas-math':
                return 'gpt-5.2';
            case 'uas':
                return 'gpt-5.2';
            default:
                return 'gpt-5.2';
        }
    }

    /**
     * Validasi apakah model key valid dan aktif
     */
    public static function isValidModel($key) {
        $model = self::getModel($key);
        return $model && $model['enabled'];
    }

    /**
     * Mendapatkan daftar model untuk HTML select options
     * Diurutkan dari termurah hingga termahal
     */
    public static function getHtmlOptions($selectedModel = null) {
        $activeModels = self::getActiveModels();
        
        // Urutkan berdasarkan harga (dari termurah ke termahal)
        uasort($activeModels, function($a, $b) {
            $priceA = floatval(str_replace(['$', ','], '', $a['price_per_1m_tokens']));
            $priceB = floatval(str_replace(['$', ','], '', $b['price_per_1m_tokens']));
            return $priceA - $priceB;
        });
        
        $options = '';
        foreach ($activeModels as $key => $model) {
            $selected = ($key === $selectedModel) ? 'selected' : '';
            $options .= sprintf(
                '<option value="%s" %s>%s - %s %s</option>',
                htmlspecialchars($key),
                $selected,
                htmlspecialchars($model['name']),
                htmlspecialchars($model['description']),
                htmlspecialchars($model['price_per_1m_tokens'])
            );
        }
        return $options;
    }

    /**
     * Mendapatkan ikon berdasarkan tingkat biaya
     */
    private static function getPricingIcon($tier) {
        switch ($tier) {
            case 'low': return '💰';
            case 'medium': return '💰💰';
            case 'high': return '💰💰💰';
            default: return '';
        }
    }

    /**
     * Mendapatkan konfigurasi untuk JavaScript
     */
    public static function getJavaScriptConfig() {
        $jsConfig = [];
        foreach (self::getActiveModels() as $key => $model) {
            $jsConfig[$key] = [
                'name' => $model['name'],
                'description' => $model['description'],
                'recommended_for' => $model['recommended_for'],
                'pricing_tier' => $model['pricing_tier'],
                'price_per_1m_tokens' => $model['price_per_1m_tokens']
            ];
        }
        return $jsConfig;
    }

    /**
     * Menambah model baru (untuk pengembangan)
     */
    public static function addModel($key, $config) {
        self::$models[$key] = array_merge([
            'name' => '',
            'description' => '',
            'api_model' => $key,
            'max_tokens' => 500,
            'temperature' => 0.7,
            'enabled' => true,
            'recommended_for' => ['default'],
            'pricing_tier' => 'medium'
        ], $config);
    }

    /**
     * Mengupdate konfigurasi model yang sudah ada
     */
    public static function updateModel($key, $updates) {
        if (isset(self::$models[$key])) {
            self::$models[$key] = array_merge(self::$models[$key], $updates);
            return true;
        }
        return false;
    }

    /**
     * Menonaktifkan model
     */
    public static function disableModel($key) {
        return self::updateModel($key, ['enabled' => false]);
    }

    /**
     * Mengaktifkan model
     */
    public static function enableModel($key) {
        return self::updateModel($key, ['enabled' => true]);
    }
    
    /**
     * Mendapatkan semua model (termasuk yang nonaktif)
     */
    public static function getAllModels() {
        return self::$models;
    }

    /**
     * Estimasi jumlah token dari teks (approximate)
     * 1 token ≈ 4 karakter untuk bahasa Inggris
     * 1 token ≈ 2-3 karakter untuk bahasa Indonesia
     */
    public static function estimateTokenCount($text) {
        if (empty($text)) {
            return 0;
        }
        
        // Remove extra whitespace and normalize
        $text = trim(preg_replace('/\s+/', ' ', $text));
        
        // Rough estimation: 1 token per 3.5 characters (average for mixed languages)
        $charCount = mb_strlen($text, 'UTF-8');
        $estimatedTokens = ceil($charCount / 3.5);
        
        return max(1, $estimatedTokens); // Minimum 1 token
    }

    /**
     * Estimasi total token untuk conversation (user + response)
     */
    public static function estimateConversationTokens($userMessage, $botResponse) {
        $userTokens = self::estimateTokenCount($userMessage);
        $responseTokens = self::estimateTokenCount($botResponse);
        
        // Add some overhead for system messages and formatting
        $overhead = 10;
        
        return $userTokens + $responseTokens + $overhead;
    }
}
