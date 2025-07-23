<?php
/**
 * API endpoint untuk mendapatkan konfigurasi model untuk JavaScript
 */

// Include model configuration
require_once 'model_config.php';

// Set JSON header
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Mendapatkan konfigurasi model untuk JavaScript
    $config = [
        'models' => ModelConfig::getJavaScriptConfig(),
        'defaults' => [
            'default' => ModelConfig::getDefaultModelForMode('default'),
            'uas' => ModelConfig::getDefaultModelForMode('uas'),
            'uas-math' => ModelConfig::getDefaultModelForMode('uas-math')
        ],
        'active_models' => array_keys(ModelConfig::getActiveModels())
    ];

    echo json_encode($config, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load model configuration',
        'message' => $e->getMessage()
    ]);
}
?>
