<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat AI</title>
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div id="chat-container">
        <div id="chat-box"></div>
        <div id="input-container">
            <textarea id="user-input" placeholder="Type your message..." rows="1"></textarea>
            <button id="clear-btn" title="Hapus riwayat chat dan cache">🗑️</button>
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
    <script src="js/app.js"></script>
</body>
</html>
