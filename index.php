<?php
// Set timezone to Asia/Jakarta (GMT+7)
date_default_timezone_set('Asia/Jakarta');

// Include model configuration
require_once 'model_config.php';

// Get default model for default mode
$defaultModel = ModelConfig::getDefaultModelForMode('default');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat AI</title>
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/style.css">
    
    <!-- MathJax for math formula rendering -->
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['\\(', '\\)'], ['$', '$']],
                displayMath: [['\\[', '\\]'], ['$$', '$$']],
                processEscapes: true,
                processEnvironments: true
            },
            options: {
                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],
                ignoreHtmlClass: 'tex2jax_ignore',
                processHtmlClass: 'tex2jax_process'
            }
        };
    </script>
    
    <!-- Marked.js for markdown parsing -->
    <script src="https://cdn.jsdelivr.net/npm/marked@9.1.6/marked.min.js"></script>
</head>
<body>
    <div id="mode-selector">
        <button id="mode-default" class="mode-btn active">Mode Chat</button>
        <button id="mode-uas" class="mode-btn">Mode OCR Low</button>
        <button id="mode-uas-math" class="mode-btn">Mode OCR High</button>
    </div>
    
    <div id="chat-controls">
        <div id="model-selector">
            <label for="gpt-model">Model :</label>
            <select id="gpt-model" class="model-select">
                <?= ModelConfig::getHtmlOptions($defaultModel) ?>
            </select>
        </div>
        <div id="clear-data-container">
            <button id="clear-cache-btn" title="Hapus semua cache dan localStorage">🗑️ Clear Data</button>
        </div>
    </div>
    
    <div id="chat-container">
        <div id="chat-box"></div>
        <div id="input-container">
            <input type="file" id="image-input" accept="image/*" style="display: none;">
            <div id="image-preview-container" style="display: none;">
                <img id="image-preview" src="" alt="Preview">
                <button id="remove-image">×</button>
            </div>
            <textarea id="user-input" placeholder="Type your message..." rows="1"></textarea>
            <button id="image-btn" title="Upload gambar untuk Mode OCR High" style="display: none;">📸</button>
            <button id="clear-btn" title="Klik: Hapus mode saat ini | Klik kanan atau double-click: Hapus semua mode">🗑️</button>
            <button id="send-btn">Send</button>
        </div>
        <div id="ocr-progress">
            <div class="progress-container">
                <div class="progress-info">
                    <div class="progress-text" id="ocr-progress-text">Memulai proses...</div>
                    <div class="progress-percentage" id="ocr-progress-percentage">0%</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="ocr-progress-fill"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Local JavaScript Files -->
    <script src="js/jquery.min.js"></script>
    <script src="js/tesseract.min.js"></script>
    <script src="js/markdown-math.js"></script>
    <script src="js/model-config.js"></script>
    <script src="js/streaming.js"></script>
    <script src="js/app.js"></script>
</body>
</html>
