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
                    'gpt-3.5-turbo': {
                        name: 'GPT-3.5 Turbo',
                        description: 'Cepat & Ekonomis',
                        recommended_for: ['default'],
                        pricing_tier: 'low'
                    },
                    'gpt-4o': {
                        name: 'GPT-4o', 
                        description: 'Pintar & Fleksibel',
                        recommended_for: ['default', 'uas', 'uas-math'],
                        pricing_tier: 'medium'
                    },
                    'gpt-4.1': {
                        name: 'GPT-4.1',
                        description: 'Akurasi Tinggi',
                        recommended_for: ['uas-math'],
                        pricing_tier: 'high'
                    }
                },
                defaults: {
                    default: 'gpt-3.5-turbo',
                    uas: 'gpt-4o',
                    'uas-math': 'gpt-4.1'
                },
                active_models: ['gpt-3.5-turbo', 'gpt-4o', 'gpt-4.1']
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
            return mode === 'uas-math' ? 'gpt-4.1' : 
                   mode === 'uas' ? 'gpt-4o' : 'gpt-3.5-turbo';
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
                // Tambahkan indikator visual untuk model yang direkomendasikan
                const currentText = $option.text();
                if (!currentText.includes('⭐')) {
                    $option.text('⭐ ' + currentText);
                }
            } else {
                $option.removeClass('recommended-model');
                const currentText = $option.text();
                $option.text(currentText.replace('⭐ ', ''));
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
