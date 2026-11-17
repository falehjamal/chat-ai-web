INSERT INTO ai_providers (provider_key, label, driver, base_url, api_key_env_var, is_active)
VALUES ('openai', 'OpenAI', 'openai_compatible', 'https://api.openai.com/v1', 'OPENAI_API_KEY', 1)
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    driver = VALUES(driver),
    base_url = VALUES(base_url),
    api_key_env_var = VALUES(api_key_env_var),
    is_active = VALUES(is_active);

INSERT INTO ai_models (provider_id, model_key, api_model, label, temperature, max_tokens, use_max_completion_tokens, supports_vision, is_active)
SELECT p.id, 'gpt-5.2', 'gpt-5.2', 'GPT-5.2', 0.30, 16384, 1, 1, 1
FROM ai_providers p
WHERE p.provider_key = 'openai'
ON DUPLICATE KEY UPDATE
    provider_id = VALUES(provider_id),
    api_model = VALUES(api_model),
    label = VALUES(label),
    max_tokens = VALUES(max_tokens),
    use_max_completion_tokens = VALUES(use_max_completion_tokens),
    supports_vision = VALUES(supports_vision),
    is_active = VALUES(is_active);

INSERT INTO ai_models (provider_id, model_key, api_model, label, temperature, max_tokens, use_max_completion_tokens, supports_vision, is_active)
SELECT p.id, 'gpt-5-mini', 'gpt-5-mini', 'GPT-5 mini', 0.30, 16384, 1, 1, 1
FROM ai_providers p
WHERE p.provider_key = 'openai'
ON DUPLICATE KEY UPDATE
    provider_id = VALUES(provider_id),
    api_model = VALUES(api_model),
    label = VALUES(label),
    max_tokens = VALUES(max_tokens),
    use_max_completion_tokens = VALUES(use_max_completion_tokens),
    supports_vision = VALUES(supports_vision),
    is_active = VALUES(is_active);

UPDATE mode_bindings mb
INNER JOIN ai_models m ON m.model_key = 'gpt-5.2'
SET mb.model_id = m.id, mb.reasoning_effort = 'low'
WHERE mb.mode_key = 'default';

UPDATE mode_bindings mb
INNER JOIN ai_models m ON m.model_key = 'gpt-5-mini'
SET mb.model_id = m.id, mb.reasoning_effort = 'high'
WHERE mb.mode_key = 'uas';

UPDATE mode_bindings mb
INNER JOIN ai_models m ON m.model_key = 'gpt-5.2'
SET mb.model_id = m.id, mb.reasoning_effort = 'high'
WHERE mb.mode_key = 'uas-math';

DELETE m FROM ai_models m
INNER JOIN ai_providers p ON p.id = m.provider_id
WHERE p.provider_key <> 'openai';

DELETE FROM ai_providers WHERE provider_key <> 'openai';
