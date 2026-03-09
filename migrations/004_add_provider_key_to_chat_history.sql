ALTER TABLE chat_history
    ADD COLUMN provider_key VARCHAR(100) NULL AFTER model;
