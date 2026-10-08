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
                'default_model_key' => 'cu/gpt-5.2',
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
                'default_model_key' => 'cu/gpt-5-mini',
            ],
            'uas-math' => [
                'label' => 'OCR High',
                'endpoint' => 'api_uas_math_stream.php',
                'local_storage_key' => 'chatHistoryUASMath',
                'history_strategy' => 'none',
                'history_limit' => 0,
                'accepts_image' => true,
                'ocr_strategy' => 'vision_direct',
                'system_prompt' => "Anda adalah asisten akademik AI yang ahli dalam menjawab soal ujian dari berbagai mata pelajaran — matematika, fisika, kimia, biologi, ekonomi, bahasa, sejarah, dan lainnya.\n\nTUGAS UTAMA:\n- Jika ada gambar → baca dan pahami soal dari gambar tersebut\n- Jika hanya teks → jawab langsung sesuai konteks mata pelajaran\n\nCARA MENJAWAB:\n1. Identifikasi jenis soal dan mata pelajaran secara singkat\n2. Tulis langkah penyelesaian secara ringkas dan terstruktur\n3. Berikan JAWABAN AKHIR yang jelas dan tegas — tandai dengan \"**Jawaban: ...**\"\n\nATURAN:\n- Utamakan akurasi dan ketepatan jawaban\n- Jangan bertele-tele — langsung ke inti\n- Untuk soal pilihan ganda: sebutkan opsi yang benar beserta alasan singkatnya\n- Untuk soal uraian: berikan jawaban lengkap namun padat\n- Untuk matematika/sains: tampilkan rumus dan langkah perhitungan yang relevan saja\n- Gunakan bahasa yang sama dengan soal (Indonesia atau Inggris)",
                'default_model_key' => 'cu/gpt-5.2',
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
