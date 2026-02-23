/**
 * Model Configuration Manager — Simplified
 * Model is hardcoded to gpt-5.2, no UI selector needed.
 */

class ModelConfigManager {
    constructor() {
        this.defaultModel = 'gpt-5.2';
        this.initialized = true;
    }

    async loadConfig() {
        // No-op: model is fixed
    }

    getDefaultModelForMode(_mode) {
        return this.defaultModel;
    }

    isValidModel(modelKey) {
        return modelKey === this.defaultModel;
    }
}

// Initialize global instances
const modelConfigManager = new ModelConfigManager();
window.APP_MODEL = 'gpt-5.2';
