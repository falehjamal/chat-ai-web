ALTER TABLE mode_bindings
    ADD COLUMN reasoning_effort VARCHAR(20) NULL DEFAULT NULL AFTER ocr_strategy;
