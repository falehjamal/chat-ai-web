INSERT INTO ai_providers (provider_key, label, driver, base_url, api_key_env_var, is_active)
VALUES ('openai', 'OpenAI', 'openai_compatible', 'https://api.openai.com/v1', 'OPENAI_API_KEY', 1)
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    driver = VALUES(driver),
    base_url = VALUES(base_url),
    api_key_env_var = VALUES(api_key_env_var),
    is_active = VALUES(is_active);

INSERT INTO ai_models (provider_id, model_key, api_model, label, temperature, max_tokens, use_max_completion_tokens, supports_vision, is_active)
SELECT p.id, 'gpt-5.2', 'gpt-5.2', 'GPT-5.2', 0.30, 4096, 1, 1, 1
FROM ai_providers p
WHERE p.provider_key = 'openai'
ON DUPLICATE KEY UPDATE
    api_model = VALUES(api_model),
    label = VALUES(label),
    temperature = VALUES(temperature),
    max_tokens = VALUES(max_tokens),
    use_max_completion_tokens = VALUES(use_max_completion_tokens),
    supports_vision = VALUES(supports_vision),
    is_active = VALUES(is_active);

INSERT INTO mode_bindings (mode_key, model_id, system_prompt, history_strategy, history_limit, accepts_image, ocr_strategy)
SELECT 'default', m.id, 'Kamu adalah asisten virtual yang ceria, informatif, dan ramah. Kamu dibuat oleh developer bernama Ahmad Faleh Jamaluddin.', 'recent_window', 7, 0, 'client_extract_text'
FROM ai_models m
WHERE m.model_key = 'gpt-5.2'
ON DUPLICATE KEY UPDATE
    model_id = VALUES(model_id),
    system_prompt = VALUES(system_prompt),
    history_strategy = VALUES(history_strategy),
    history_limit = VALUES(history_limit),
    accepts_image = VALUES(accepts_image),
    ocr_strategy = VALUES(ocr_strategy);

INSERT INTO mode_bindings (mode_key, model_id, system_prompt, history_strategy, history_limit, accepts_image, ocr_strategy)
SELECT 'uas', m.id, 'Anda adalah asisten AI yang membantu mahasiswa menjawab soal. Berikan jawaban singkat, dan relevan. Sebelum menjawab, pikirkan dulu kemungkinan jawaban secara runtut, lalu simpulkan jawaban akhir secara singkat padat dan jelas.', 'none', 0, 0, 'client_extract_text'
FROM ai_models m
WHERE m.model_key = 'gpt-5.2'
ON DUPLICATE KEY UPDATE
    model_id = VALUES(model_id),
    system_prompt = VALUES(system_prompt),
    history_strategy = VALUES(history_strategy),
    history_limit = VALUES(history_limit),
    accepts_image = VALUES(accepts_image),
    ocr_strategy = VALUES(ocr_strategy);

INSERT INTO mode_bindings (mode_key, model_id, system_prompt, history_strategy, history_limit, accepts_image, ocr_strategy)
SELECT 'uas-math', m.id, 'Anda adalah asisten AI yang dapat membantu menyelesaikan soal dari berbagai mata pelajaran, dengan keahlian utama dalam matematika dan pemecahan soal. Tugas Anda meliputi:
1. Jika terdapat gambar: Analisis isi gambar untuk mengidentifikasi dan memahami soal yang diberikan.
2. Jika hanya teks: Jawab pertanyaan secara langsung sesuai konteks mata pelajaran.
3. Identifikasi jenis soal — terutama untuk matematika (misalnya aljabar, kalkulus, geometri, dll.), namun juga relevan untuk bidang lain seperti fisika, kimia, atau bahasa.
4. Berikan jawaban akhir yang akurat dan dapat dipertanggungjawabkan.
5. Jelaskan secara singkat, lalu langsung berikan jawaban akhir secara to the point.', 'none', 0, 1, 'vision_direct'
FROM ai_models m
WHERE m.model_key = 'gpt-5.2'
ON DUPLICATE KEY UPDATE
    model_id = VALUES(model_id),
    system_prompt = VALUES(system_prompt),
    history_strategy = VALUES(history_strategy),
    history_limit = VALUES(history_limit),
    accepts_image = VALUES(accepts_image),
    ocr_strategy = VALUES(ocr_strategy);
