INSERT INTO ai_providers (provider_key, label, driver, base_url, api_key_env_var, is_active)
VALUES ('9router', '9router', 'openai_compatible', 'http://127.0.0.1:20128/v1', 'NINEROUTER_KEY', 1)
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    driver = VALUES(driver),
    base_url = VALUES(base_url),
    api_key_env_var = VALUES(api_key_env_var),
    is_active = VALUES(is_active);

INSERT INTO ai_models (provider_id, model_key, api_model, label, temperature, max_tokens, use_max_completion_tokens, supports_vision, is_active)
SELECT p.id, 'cu/gpt-5.2', 'cu/gpt-5.2', 'cu/gpt-5.2', 0.30, 16384, 0, 1, 1
FROM ai_providers p
WHERE p.provider_key = '9router'
ON DUPLICATE KEY UPDATE
    api_model = VALUES(api_model),
    label = VALUES(label),
    supports_vision = VALUES(supports_vision),
    is_active = VALUES(is_active);

INSERT INTO ai_models (provider_id, model_key, api_model, label, temperature, max_tokens, use_max_completion_tokens, supports_vision, is_active)
SELECT p.id, 'cu/gpt-5-mini', 'cu/gpt-5-mini', 'cu/gpt-5-mini', 0.30, 16384, 0, 1, 1
FROM ai_providers p
WHERE p.provider_key = '9router'
ON DUPLICATE KEY UPDATE
    api_model = VALUES(api_model),
    label = VALUES(label),
    supports_vision = VALUES(supports_vision),
    is_active = VALUES(is_active);

INSERT INTO mode_bindings (mode_key, model_id, system_prompt, history_strategy, history_limit, accepts_image, ocr_strategy)
SELECT 'default', m.id, 'Kamu adalah asisten virtual yang ceria, informatif, dan ramah. Kamu dibuat oleh developer bernama Ahmad Faleh Jamaluddin.', 'recent_window', 7, 0, 'client_extract_text'
FROM ai_models m
WHERE m.model_key = 'cu/gpt-5.2'
ON DUPLICATE KEY UPDATE model_id = model_id;

INSERT INTO mode_bindings (mode_key, model_id, system_prompt, history_strategy, history_limit, accepts_image, ocr_strategy)
SELECT 'uas', m.id, 'Anda adalah asisten AI yang membantu mahasiswa menjawab soal. Berikan jawaban singkat, dan relevan. Sebelum menjawab, pikirkan dulu kemungkinan jawaban secara runtut, lalu simpulkan jawaban akhir secara singkat padat dan jelas.', 'none', 0, 0, 'client_extract_text'
FROM ai_models m
WHERE m.model_key = 'cu/gpt-5-mini'
ON DUPLICATE KEY UPDATE model_id = model_id;

INSERT INTO mode_bindings (mode_key, model_id, system_prompt, history_strategy, history_limit, accepts_image, ocr_strategy)
SELECT 'uas-math', m.id, 'Anda adalah asisten akademik AI yang ahli dalam menjawab soal ujian dari berbagai mata pelajaran — matematika, fisika, kimia, biologi, ekonomi, bahasa, sejarah, dan lainnya.

TUGAS UTAMA:
- Jika ada gambar → baca dan pahami soal dari gambar tersebut
- Jika hanya teks → jawab langsung sesuai konteks mata pelajaran

CARA MENJAWAB:
1. Identifikasi jenis soal dan mata pelajaran secara singkat
2. Tulis langkah penyelesaian secara ringkas dan terstruktur
3. Berikan JAWABAN AKHIR yang jelas dan tegas — tandai dengan "**Jawaban: ...**"

ATURAN:
- Utamakan akurasi dan ketepatan jawaban
- Jangan bertele-tele — langsung ke inti
- Untuk soal pilihan ganda: sebutkan opsi yang benar beserta alasan singkatnya
- Untuk soal uraian: berikan jawaban lengkap namun padat
- Untuk matematika/sains: tampilkan rumus dan langkah perhitungan yang relevan saja
- Gunakan bahasa yang sama dengan soal (Indonesia atau Inggris)', 'none', 0, 1, 'vision_direct'
FROM ai_models m
WHERE m.model_key = 'cu/gpt-5.2'
ON DUPLICATE KEY UPDATE model_id = model_id;
