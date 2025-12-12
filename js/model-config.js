/**
 * Model Configuration Manager untuk JavaScript
 * 
 * File ini mengelola konfigurasi model di sisi client dan menyinkronkan
 * dengan konfigurasi server melalui API.
 */

class ModelConfigManager {
    constructor() {
        this.config = null;
        this.initialized = false;
    }

    /**
     * Memuat konfigurasi model dari server
     */
    async loadConfig() {
        try {
            const response = await fetch('api_model_config.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            this.config = await response.json();
            this.initialized = true;
            console.log('Model configuration loaded:', this.config);
        } catch (error) {
            console.error('Failed to load model configuration:', error);
            // Fallback ke konfigurasi default
            this.config = {
                models: {
                    'gpt-5.2': {
                        name: 'GPT-5.2',
                        description: 'Model Paling Canggih',
                        recommended_for: ['default', 'uas', 'uas-math', 'ocr'],
                        pricing_tier: 'high',
                        price_per_1m_tokens: '$18.00'
                    },
                    'gpt-5.1': {
                        name: 'GPT-5.1',
                        description: 'Model Terbaru & Akurat',
                        recommended_for: ['default', 'uas', 'uas-math', 'ocr'],
                        pricing_tier: 'high',
                        price_per_1m_tokens: '$15.00'
                    },
                    'gpt-5-nano': {
                        name: 'GPT-5 Nano',
                        description: 'Ringan & Ekonomis',
                        recommended_for: ['default'],
                        pricing_tier: 'low',
                        price_per_1m_tokens: '$0.50'
                    }
                },
                defaults: {
                    default: 'gpt-5.2',
                    uas: 'gpt-5.2',
                    'uas-math': 'gpt-5.2'
                },
                active_models: ['gpt-5.2', 'gpt-5.1', 'gpt-5-nano']
            };
            this.initialized = true;
        }
    }

    /**
     * Memastikan konfigurasi sudah dimuat
     */
    async ensureLoaded() {
        if (!this.initialized) {
            await this.loadConfig();
        }
    }

    /**
     * Mendapatkan model default untuk mode tertentu
     */
    getDefaultModelForMode(mode) {
        if (!this.initialized) {
            console.warn('Model config not loaded, using fallback');
            return 'gpt-5.2'; // Default ke gpt-5.2 untuk semua mode
        }
        return this.config.defaults[mode] || this.config.defaults.default;
    }

    /**
     * Mendapatkan semua model yang aktif
     */
    getActiveModels() {
        if (!this.initialized) return [];
        return this.config.active_models || [];
    }

    /**
     * Mendapatkan informasi model
     */
    getModelInfo(modelKey) {
        if (!this.initialized) return null;
        return this.config.models[modelKey] || null;
    }

    /**
     * Mendapatkan model yang direkomendasikan untuk mode tertentu
     */
    getRecommendedModels(mode) {
        if (!this.initialized) return [];
        
        const recommended = [];
        for (const [key, model] of Object.entries(this.config.models)) {
            if (model.recommended_for && model.recommended_for.includes(mode)) {
                recommended.push(key);
            }
        }
        return recommended;
    }

    /**
     * Memeriksa apakah model valid dan aktif
     */
    isValidModel(modelKey) {
        if (!this.initialized) return true; // Fallback
        return this.config.active_models.includes(modelKey);
    }

    /**
     * Mendapatkan ikon berdasarkan tier pricing
     */
    getPricingIcon(tier) {
        switch (tier) {
            case 'low': return '💰';
            case 'medium': return '💰💰';
            case 'high': return '💰💰💰';
            default: return '';
        }
    }

    /**
     * Memperbarui dropdown select dengan model yang tersedia
     * Mengurutkan berdasarkan harga dari termurah ke termahal
     */
    updateModelSelect(selectElement, selectedModel = null) {
        if (!this.initialized) {
            console.warn('Model config not loaded, cannot update select');
            return;
        }

        const $select = $(selectElement);
        $select.empty();

        // Buat array model dengan key untuk sorting
        const modelsArray = [];
        for (const modelKey of this.config.active_models) {
            const model = this.config.models[modelKey];
            if (model) {
                modelsArray.push({
                    key: modelKey,
                    ...model
                });
            }
        }

        // Urutkan berdasarkan harga (dari termurah ke termahal)
        modelsArray.sort((a, b) => {
            const priceA = parseFloat(a.price_per_1m_tokens.replace(/[$,]/g, ''));
            const priceB = parseFloat(b.price_per_1m_tokens.replace(/[$,]/g, ''));
            return priceA - priceB;
        });

        // Tambahkan option ke select
        for (const model of modelsArray) {
            const option = $('<option>', {
                value: model.key,
                text: `${model.name} — ${model.price_per_1m_tokens} per 1jt token`,
                selected: model.key === selectedModel
            });
            $select.append(option);
        }
    }

    /**
     * Menandai model yang direkomendasikan untuk mode tertentu
     */
    highlightRecommendedModels(selectElement, mode) {
        if (!this.initialized) return;

        const $select = $(selectElement);
        const recommended = this.getRecommendedModels(mode);

        $select.find('option').each(function() {
            const $option = $(this);
            const modelKey = $option.val();
            
            if (recommended.includes(modelKey)) {
                $option.addClass('recommended-model');
                $option.attr('data-recommended', 'true');
            } else {
                $option.removeClass('recommended-model');
                $option.removeAttr('data-recommended');
            }
        });
    }

    /**
     * Mendapatkan konfigurasi lengkap (untuk debugging)
     */
    getFullConfig() {
        return this.config;
    }
}

// Instance global
window.modelConfigManager = new ModelConfigManager();
