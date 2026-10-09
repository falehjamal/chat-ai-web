INSERT INTO ai_models (provider_id, model_key, api_model, label, temperature, max_tokens, use_max_completion_tokens, supports_vision, is_active)
SELECT p.id, 'ag/gemini-3.8-flash-high', 'ag/gemini-3.8-flash-high', 'ag/gemini-3.8-flash-high', 0.30, 16384, 0, 1, 1
FROM ai_providers p
WHERE p.provider_key = '9router'
ON DUPLICATE KEY UPDATE
    api_model = VALUES(api_model),
    label = VALUES(label),
    supports_vision = 1,
    is_active = 1;

UPDATE mode_bindings mb
INNER JOIN ai_models current_model ON current_model.id = mb.model_id
INNER JOIN ai_models vision_model ON vision_model.model_key = 'ag/gemini-3.8-flash-high'
SET mb.model_id = vision_model.id
WHERE mb.mode_key = 'uas-math'
  AND current_model.model_key LIKE 'cu/%';
