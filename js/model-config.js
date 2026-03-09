/**
 * Runtime model configuration manager.
 * Source of truth now comes from backend runtime config.
 */

class ModelConfigManager {
    constructor() {
        this.runtimeConfig = window.APP_RUNTIME_CONFIG || {};
        this.defaultModel = (this.runtimeConfig.modes && this.runtimeConfig.modes.default && this.runtimeConfig.modes.default.modelKey) || 'gpt-5.2';
        this.initialized = false;
    }

    async loadConfig() {
        this.initialized = true;
        return this.runtimeConfig;
    }

    getRuntimeConfig() {
        return this.runtimeConfig;
    }

    getModeConfig(mode) {
        return (this.runtimeConfig.modes && this.runtimeConfig.modes[mode]) || null;
    }

    getDefaultModelForMode(mode) {
        const modeConfig = this.getModeConfig(mode);
        return (modeConfig && modeConfig.modelKey) || this.defaultModel;
    }

    isValidModel(modelKey) {
        const models = this.runtimeConfig.models || {};
        return !!models[modelKey] || modelKey === this.defaultModel;
    }
}

const modelConfigManager = new ModelConfigManager();
window.modelConfigManager = modelConfigManager;
window.APP_MODEL = modelConfigManager.getDefaultModelForMode('default');
