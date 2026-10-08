UPDATE ai_providers
SET provider_key = '9router',
    label = '9router',
    driver = 'openai_compatible',
    base_url = 'http://127.0.0.1:20128/v1',
    api_key_env_var = 'NINEROUTER_KEY',
    is_active = 1
WHERE provider_key = 'openai';
