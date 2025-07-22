<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat AI</title>
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/style.css">
    
    <!-- MathJax for math formula rendering -->
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
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
        <button id="mode-default" class="mode-btn active">Mode Default</button>
        <button id="mode-uas" class="mode-btn">Mode UAS</button>
        <button id="mode-uas-math" class="mode-btn">Mode UAS Matematika</button>
    </div>
    
    <div id="model-selector">
        <label for="gpt-model">Model :</label>
        <select id="gpt-model" class="model-select">
            <option value="gpt-3.5-turbo">
                GPT-3.5 Turbo — Cepat & Ekonomis (Cocok untuk obrolan ringan)
            </option>
            <option value="gpt-4o">
                GPT-4o — Pintar & Fleksibel (Ideal untuk tugas, UAS, dan esai)
            </option>
            <option value="gpt-4.1">
                GPT-4.1 — Akurasi Tinggi (Terbaik untuk matematika dan logika kompleks)
            </option>

        </select>
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
            <button id="image-btn" title="Upload gambar untuk Mode UAS Matematika" style="display: none;">📸</button>
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
    <script src="js/streaming.js"></script>
    <script src="js/app.js"></script>
</body>
</html>
