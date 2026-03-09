<?php

namespace App\Modules\Chat\Domain;

class PublicChatContract
{
    public static function modes()
    {
        return [
            'default' => [
                'label' => 'Chat',
                'endpoint' => 'api_stream.php',
                'local_storage_key' => 'chatHistoryDefault',
                'history_strategy' => 'recent_window',
                'history_limit' => 7,
                'accepts_image' => false,
                'ocr_strategy' => 'client_extract_text',
                'system_prompt' => 'Kamu adalah asisten virtual yang ceria, informatif, dan ramah. Kamu dibuat oleh developer bernama Ahmad Faleh Jamaluddin.',
                'default_model_key' => 'gpt-5.2',
            ],
            'uas' => [
                'label' => 'OCR Low',
                'endpoint' => 'api_uas_stream.php',
                'local_storage_key' => 'chatHistoryUAS',
                'history_strategy' => 'none',
                'history_limit' => 0,
                'accepts_image' => false,
                'ocr_strategy' => 'client_extract_text',
                'system_prompt' => 'Anda adalah asisten AI yang membantu mahasiswa menjawab soal. Berikan jawaban singkat, dan relevan. Sebelum menjawab, pikirkan dulu kemungkinan jawaban secara runtut, lalu simpulkan jawaban akhir secara singkat padat dan jelas.',
                'default_model_key' => 'gpt-5.2',
            ],
            'uas-math' => [
                'label' => 'OCR High',
                'endpoint' => 'api_uas_math_stream.php',
                'local_storage_key' => 'chatHistoryUASMath',
                'history_strategy' => 'none',
                'history_limit' => 0,
                'accepts_image' => true,
                'ocr_strategy' => 'vision_direct',
                'system_prompt' => "Anda adalah asisten AI yang dapat membantu menyelesaikan soal dari berbagai mata pelajaran, dengan keahlian utama dalam matematika dan pemecahan soal. Tugas Anda meliputi:\n1. Jika terdapat gambar: Analisis isi gambar untuk mengidentifikasi dan memahami soal yang diberikan.\n2. Jika hanya teks: Jawab pertanyaan secara langsung sesuai konteks mata pelajaran.\n3. Identifikasi jenis soal — terutama untuk matematika (misalnya aljabar, kalkulus, geometri, dll.), namun juga relevan untuk bidang lain seperti fisika, kimia, atau bahasa.\n4. Berikan jawaban akhir yang akurat dan dapat dipertanggungjawabkan.\n5. Jelaskan secara singkat, lalu langsung berikan jawaban akhir secara to the point.",
                'default_model_key' => 'gpt-5.2',
            ],
        ];
    }

    public static function sseEvents()
    {
        return ['status', 'chunk', 'complete', 'error'];
    }

    public static function requestFields()
    {
        return [
            'default' => ['message', 'history', 'model'],
            'uas' => ['message', 'model'],
            'uas-math' => ['message', 'model', 'image'],
        ];
    }

    public static function localStorageKeys()
    {
        $keys = [];
        foreach (self::modes() as $modeKey => $mode) {
            $keys[$modeKey] = $mode['local_storage_key'];
        }

        return $keys;
    }

    public static function isValidMode($modeKey)
    {
        return isset(self::modes()[$modeKey]);
    }

    public static function mode($modeKey)
    {
        $modes = self::modes();
        return $modes[$modeKey] ?? $modes['default'];
    }

    public static function frontendRuntimeConfig(array $resolvedModes, array $models)
    {
        return [
            'defaultMode' => 'default',
            'sseEvents' => self::sseEvents(),
            'localStorageKeys' => self::localStorageKeys(),
            'modes' => $resolvedModes,
            'models' => $models,
        ];
    }
}
