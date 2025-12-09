$(document).ready(async function() {
    const MAX_HISTORY = 7; // Maximum number of messages to keep for context
    
    // Initialize model configuration manager
    await modelConfigManager.loadConfig();
    
    // Current mode tracking
    let currentMode = 'default';
    let selectedModel = modelConfigManager.getDefaultModelForMode('default'); // Use centralized default
    let currentImage = null; // For storing current image in OCR High mode
    let currentImageId = null; // For storing current image ID
    
    // Debug: Check if Tesseract is available
    console.log('Tesseract available:', typeof Tesseract !== 'undefined');
    if (typeof Tesseract !== 'undefined') {
        console.log('Tesseract object:', Tesseract);
    }
    
    // Load separate chat histories from localStorage
    let chatHistoryDefault = JSON.parse(localStorage.getItem('chatHistoryDefault')) || [];
    let chatHistoryUAS = JSON.parse(localStorage.getItem('chatHistoryUAS')) || [];
    let chatHistoryUASMath = JSON.parse(localStorage.getItem('chatHistoryUASMath')) || [];
    
    // Get current chat history based on mode
    function getCurrentChatHistory() {
        switch(currentMode) {
            case 'uas': return chatHistoryUAS;
            case 'uas-math': return chatHistoryUASMath;
            default: return chatHistoryDefault;
        }
    }
    
    // Save chat history to localStorage
    function saveChatHistory() {
        switch(currentMode) {
            case 'uas':
                localStorage.setItem('chatHistoryUAS', JSON.stringify(chatHistoryUAS));
                break;
            case 'uas-math':
                localStorage.setItem('chatHistoryUASMath', JSON.stringify(chatHistoryUASMath));
                break;
            default:
                localStorage.setItem('chatHistoryDefault', JSON.stringify(chatHistoryDefault));
        }
    }
    
    // Cache jQuery objects
    const $chatBox = $('#chat-box');
    const $userInput = $('#user-input');
    const $sendBtn = $('#send-btn');
    const $ocrProgress = $('#ocr-progress');
    const $progressText = $('#ocr-progress-text');
    const $progressFill = $('#ocr-progress-fill');
    const $progressPercentage = $('#ocr-progress-percentage');
    const $modeDefault = $('#mode-default');
    const $modeUAS = $('#mode-uas');
    const $modeUASMath = $('#mode-uas-math');
    const $modelSelect = $('#gpt-model');
    const $imageInput = $('#image-input');
    const $imageBtn = $('#image-btn');
    const $imagePreviewContainer = $('#image-preview-container');
    const $imagePreview = $('#image-preview');
    const $removeImage = $('#remove-image');

    // Mode switching functionality
    $modeDefault.on('click', function() {
        if (currentMode !== 'default') {
            currentMode = 'default';
            updateModeAndModel();
        }
    });
    
    $modeUAS.on('click', function() {
        if (currentMode !== 'uas') {
            currentMode = 'uas';
            updateModeAndModel();
        }
    });
    
    $modeUASMath.on('click', function() {
        if (currentMode !== 'uas-math') {
            currentMode = 'uas-math';
            updateModeAndModel();
        }
    });

    // Function to update mode and automatically switch to recommended model
    function updateModeAndModel() {
        updateModeButtons();
        displayChatHistory();
        clearImagePreview();
        
        // Auto-select recommended model for the new mode
        const recommendedModel = modelConfigManager.getDefaultModelForMode(currentMode);
        if (recommendedModel && modelConfigManager.isValidModel(recommendedModel)) {
            selectedModel = recommendedModel;
            $modelSelect.val(selectedModel);
            
            // Save model preference to localStorage
            localStorage.setItem('selectedGPTModel', selectedModel);
            
            console.log(`Switched to ${currentMode} mode, selected model: ${selectedModel}`);
        }
        
        // Highlight recommended models for current mode
        modelConfigManager.highlightRecommendedModels($modelSelect, currentMode);
    }
    
    // Model selection handler
    $modelSelect.on('change', function() {
        selectedModel = $(this).val();
        console.log('Model selected:', selectedModel);
        
        // Save model preference to localStorage
        localStorage.setItem('selectedGPTModel', selectedModel);
    });
    
    function updateModeButtons() {
        $modeDefault.toggleClass('active', currentMode === 'default');
        $modeUAS.toggleClass('active', currentMode === 'uas');
        $modeUASMath.toggleClass('active', currentMode === 'uas-math');
        
        // Show/hide image button based on mode
        if (currentMode === 'uas-math') {
            $imageBtn.show();
            $userInput.attr('placeholder', 'Tanya soal matematika atau upload gambar soal...');
        } else {
            $imageBtn.hide();
            $imagePreviewContainer.hide();
            $userInput.attr('placeholder', 'Type your message...');
            currentImage = null;
            currentImageId = null;
        }
        
        // Update chat container mode indicator
        const $chatContainer = $('#chat-container');
        switch(currentMode) {
            case 'uas':
                $chatContainer.attr('data-mode', 'Mode OCR Low');
                break;
            case 'uas-math':
                $chatContainer.attr('data-mode', 'Mode OCR High');
                break;
            default:
                $chatContainer.attr('data-mode', 'Mode Chat');
        }
    }

    // Initialize
    displayChatHistory();
    autoResizeTextarea($userInput);
    updateModeButtons();
    
    // Initialize model dropdown and load saved preference
    modelConfigManager.updateModelSelect($modelSelect, selectedModel);
    const savedModel = localStorage.getItem('selectedGPTModel');
    if (savedModel && modelConfigManager.isValidModel(savedModel)) {
        selectedModel = savedModel;
        $modelSelect.val(savedModel);
    } else {
        // If saved model is invalid, use default for current mode
        selectedModel = modelConfigManager.getDefaultModelForMode(currentMode);
        $modelSelect.val(selectedModel);
    }
    
    // Highlight recommended models for current mode
    modelConfigManager.highlightRecommendedModels($modelSelect, currentMode);
    
    // Clean up any leftover typing indicators - removed as streaming handles this
    // hideTypingIndicator();

    // Image upload functionality for OCR High mode
    $imageBtn.on('click', function() {
        if (currentMode === 'uas-math') {
            $imageInput.click();
        }
    });

    $imageInput.on('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const imageData = event.target.result;
                
                if (currentMode === 'uas-math') {
                    // Mode OCR High: Langsung kirim gambar ke chat dan simpan ke localStorage
                    handleMathImageUpload(imageData);
                } else {
                    // Mode lain: Tampilkan preview seperti biasa
                    currentImage = imageData;
                    $imagePreview.attr('src', currentImage);
                    $imagePreviewContainer.show();
                }
            };
            reader.readAsDataURL(file);
        }
    });

    $removeImage.on('click', function() {
        clearImagePreview();
    });

    function clearImagePreview() {
        currentImage = null;
        currentImageId = null;
        $imagePreview.attr('src', '');
        $imagePreviewContainer.hide();
        $imageInput.val('');
    }

    // Handle image upload specifically for OCR High mode
    function handleMathImageUpload(imageData) {
        // Generate unique ID untuk gambar
        const imageId = Date.now().toString();
        
        // Simpan gambar ke localStorage
        localStorage.setItem(`math_image_${imageId}`, imageData);
        
        // Clear thumbnail preview immediately
        clearImagePreview();
        
        // Set current image dan imageId untuk dikirim ke AI
        currentImage = imageData;
        currentImageId = imageId;
        
        // Note: Jangan tambahkan gambar ke chat di sini, biarkan streaming yang menangani
        // untuk menghindari duplikasi
        
        // Auto-trigger OCR jika user input kosong
        const userMessage = $userInput.val().trim();
        if (!userMessage) {
            // Start OCR process in background
            performOCRForMathMode(imageData);
        }
    }

    // Add image to chat display
    function addImageToChat(imageData, imageId) {
        const $messageDiv = $('<div>').addClass('message user-message');
        const $messageContent = $('<div>').addClass('message-content');
        
        // Create image element with metadata
        const $imageElement = $('<img>')
            .attr('src', imageData)
            .attr('alt', 'Soal Matematika')
            .attr('data-image-id', imageId)
            .addClass('chat-image')
            .css({
                'max-width': '100%',
                'max-height': '300px',
                'border-radius': '8px',
                'margin': '10px 0',
                'cursor': 'pointer'
            });
        
        // Add click to enlarge functionality
        $imageElement.on('click', function() {
            openImageModal(imageData);
        });
        
        const $imageInfo = $('<div>')
            .addClass('image-info')
            .html('<small style="color: #666; font-style: italic;">📷 Gambar soal matematika dikirim</small>');
        
        const $timestamp = $('<div>')
            .addClass('message-time')
            .text(new Date().toLocaleTimeString('id-ID', { hour12: false }));
        
        $messageContent.append($imageElement).append($imageInfo);
        $messageDiv.append($messageContent).append($timestamp);
        $chatBox.append($messageDiv);
        
        scrollToBottom();
    }

    // Open image in modal for better viewing
    function openImageModal(imageData) {
        // Create modal if it doesn't exist
        let $modal = $('#image-modal');
        if ($modal.length === 0) {
            $modal = $('<div>')
                .attr('id', 'image-modal')
                .css({
                    'position': 'fixed',
                    'top': '0',
                    'left': '0',
                    'width': '100%',
                    'height': '100%',
                    'background': 'rgba(0,0,0,0.8)',
                    'display': 'flex',
                    'justify-content': 'center',
                    'align-items': 'center',
                    'z-index': '9999',
                    'cursor': 'pointer'
                })
                .html('<img style="max-width: 90%; max-height: 90%; border-radius: 8px;">')
                .on('click', function() {
                    $(this).hide();
                });
            $('body').append($modal);
        }
        
        $modal.find('img').attr('src', imageData);
        $modal.show();
    }

    // Load image from localStorage
    function loadImageFromLocalStorage(imageId) {
        if (!imageId) return null;
        
        // Try different possible keys
        const possibleKeys = [
            `math_image_${imageId}`,
            imageId,
            `image_${imageId}`
        ];
        
        for (const key of possibleKeys) {
            const imageData = localStorage.getItem(key);
            if (imageData) {
                return imageData;
            }
        }
        
        return null;
    }

    // Clear all math images from localStorage
    function clearMathImages() {
        try {
            const keysToRemove = [];
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && key.startsWith('math_image_')) {
                    keysToRemove.push(key);
                }
            }
            
            keysToRemove.forEach(key => {
                localStorage.removeItem(key);
                console.log('Removed math image:', key);
            });
        } catch (error) {
            console.error('Error clearing math images:', error);
        }
    }

    // Cleanup old images from localStorage (keep only recent ones)
    function cleanupOldImages() {
        try {
            const maxImages = 20; // Keep only last 20 images
            const imageKeys = [];
            
            // Find all image keys in localStorage
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && key.startsWith('math_image_')) {
                    const timestamp = key.replace('math_image_', '');
                    imageKeys.push({ key, timestamp: parseInt(timestamp) });
                }
            }
            
            // Sort by timestamp (newest first)
            imageKeys.sort((a, b) => b.timestamp - a.timestamp);
            
            // Remove old images beyond maxImages limit
            if (imageKeys.length > maxImages) {
                const imagesToRemove = imageKeys.slice(maxImages);
                imagesToRemove.forEach(item => {
                    localStorage.removeItem(item.key);
                    console.log('Removed old image:', item.key);
                });
            }
        } catch (error) {
            console.error('Error cleaning up old images:', error);
        }
    }

    // Cleanup old images on page load
    cleanupOldImages();

    // OCR processing specifically for OCR High mode
    async function performOCRForMathMode(imageData) {
        try {
            showOCRProgress();
            
            // Check if Tesseract is available
            if (typeof Tesseract === 'undefined') {
                console.warn('Tesseract tidak tersedia, melanjutkan tanpa OCR');
                hideOCRProgress();
                return;
            }

            const worker = Tesseract.createWorker({
                logger: m => {
                    console.log(m);
                    
                    if (m.status) {
                        $progressText.text(getProgressMessage(m.status));
                    }
                    
                    if (m.progress !== undefined) {
                        const percentage = Math.round(m.progress * 100);
                        $progressFill.css('width', percentage + '%');
                        $progressPercentage.text(percentage + '%');
                    }
                }
            });

            await worker.load();
            await worker.loadLanguage('ind+eng');
            await worker.initialize('ind+eng');
            
            const { data: { text } } = await worker.recognize(imageData);
            await worker.terminate();

            hideOCRProgress();
            
            // Clean up extracted text
            const cleanText = text.trim()
                .replace(/\n{3,}/g, '\n\n')
                .replace(/[ \t]+$/gm, '');
            
            if (cleanText) {
                $userInput.val(cleanText);
                autoResizeTextarea($userInput);
                $userInput.focus();
                
                // Optional: Auto-send jika OCR berhasil
                // setTimeout(() => sendMessage(), 1000);
            }

        } catch (error) {
            hideOCRProgress();
            console.error('OCR Error:', error);
            // Don't show alert in OCR High mode, just log the error
        }
    }

    function displayChatHistory() {
        $chatBox.empty();
        const chatHistory = getCurrentChatHistory();
        
        chatHistory.forEach(msg => {
            const $messageDiv = $('<div>').addClass('message');
            const $messageContent = $('<div>').addClass('message-content');
            const $messageText = $('<div>').addClass('message-text');
            
            if (msg.sender === 'bot') {
                $messageDiv.addClass('bot-message');
                
                // Format text untuk bot messages
                const formattedText = formatBotMessage(msg.text);
                
                // Add copy button for bot messages
                const messageWithCopyBtn = `
                    ${formattedText}
                    <button class="copy-btn" title="Copy response">📋</button>
                `;
                
                $messageContent.html(messageWithCopyBtn);
                
                // Setup copy functionality for this message
                setupCopyButtonForMessage($messageDiv, msg.text);
                
                // Render math if available
                if (window.markdownMathRenderer) {
                    markdownMathRenderer.renderMath($messageDiv[0]);
                }
                
                // Add timestamp if available
                if (msg.timestamp) {
                    const timestamp = new Date(msg.timestamp).toLocaleTimeString('id-ID', { hour12: false });
                    $messageContent.append(`<div class="timestamp">${timestamp}</div>`);
                }
            } else {
                $messageDiv.addClass('user-message');
                
                // User messages - handle image and text
                if (msg.hasImage) {
                    // Try to load image from localStorage
                    const imageData = loadImageFromLocalStorage(msg.imageId || msg.text);
                    
                    if (imageData) {
                        // Create image element
                        const $imageElement = $('<img>')
                            .attr('src', imageData)
                            .attr('alt', 'Soal Matematika')
                            .addClass('chat-image')
                            .on('click', function() {
                                openImageModal(imageData);
                            });
                        
                        $messageContent.append($imageElement);
                        
                        // Add image info
                        const $imageInfo = $('<div>')
                            .addClass('image-info')
                            .html('<small>📷 Gambar soal matematika</small>');
                        $messageContent.append($imageInfo);
                        
                        // Add text if available
                        if (msg.text && msg.text !== 'Gambar soal matematika') {
                            const $textDiv = $('<div>').addClass('message-text').text(msg.text);
                            $messageContent.append($textDiv);
                        }
                    } else {
                        // Fallback if image not found
                        $messageText.text(`📷 ${msg.text}`);
                        $messageContent.append($messageText);
                    }
                } else {
                    // Regular text message
                    $messageText.text(msg.text);
                    $messageContent.append($messageText);
                }
            }
            
            $messageDiv.append($messageContent);
            $chatBox.append($messageDiv);
        });
        
        scrollToBottom();
    }
    
    // Setup copy button functionality for a message
    function setupCopyButtonForMessage(messageElement, originalText) {
        const copyBtn = messageElement.find('.copy-btn');
        
        copyBtn.on('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            try {
                await navigator.clipboard.writeText(originalText);
                
                // Visual feedback
                const btn = $(this);
                const originalBtnText = btn.text();
                
                btn.addClass('copied')
                   .text('Copied!')
                   .css('background', '#10b981');
                
                setTimeout(() => {
                    btn.removeClass('copied')
                       .text(originalBtnText)
                       .css('background', '');
                }, 2000);
                
            } catch (err) {
                console.error('Failed to copy text: ', err);
                
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = originalText;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                // Visual feedback for fallback
                const btn = $(this);
                btn.text('Copied!').css('background', '#10b981');
                setTimeout(() => {
                    btn.text('📋 Copy').css('background', '');
                }, 2000);
            }
        });
    }
    
    function formatBotMessage(text) {
        // ALWAYS use the advanced markdown math renderer for ALL modes
        if (window.markdownMathRenderer) {
            console.log('🎨 Using frontend renderer for chat history formatting');
            return markdownMathRenderer.render(text);
        } else {
            // Enhanced fallback to handle all formatting in frontend
            console.log('⚠️ Using enhanced fallback formatting for chat history');
            return formatBotMessageEnhanced(text);
        }
    }
    
    function formatBotMessageEnhanced(text) {
        // Enhanced frontend formatting with ALL regex processing
        let formattedText = text
            // Clean up excessive line breaks first
            .replace(/\n{3,}/g, '\n\n')
            .replace(/\r\n/g, '\n')
            .replace(/\r/g, '\n')
            // Headers
            .replace(/^### (.*?)$/gm, '<h3>$1</h3>')
            .replace(/^## (.*?)$/gm, '<h2>$1</h2>')
            .replace(/^# (.*?)$/gm, '<h1>$1</h1>')
            // Bold and italic
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            // Code
            .replace(/`(.*?)`/g, '<code>$1</code>')
            // Horizontal rules
            .replace(/^---$/gm, '<hr>')
            // Lists
            .replace(/^[-*] (.+)$/gm, '<li>$1</li>')
            .replace(/(<li>.*<\/li>)/gs, '<ul>$1</ul>')
            // Blockquotes
            .replace(/^> (.+)$/gm, '<blockquote>$1</blockquote>')
            // Convert double line breaks to paragraph breaks
            .replace(/\n\n/g, '</p><p>')
            // Add opening and closing p tags
            .replace(/^/, '<p>')
            .replace(/$/, '</p>')
            // Convert remaining single line breaks to <br>
            .replace(/\n/g, '<br>')
            // Clean up empty paragraphs
            .replace(/<p>\s*<\/p>/g, '')
            .replace(/<p><\/p>/g, '');
        
        return formattedText;
    }
    
    function scrollToBottom() {
        $chatBox.scrollTop($chatBox[0].scrollHeight);
    }
    
    function showOCRProgress() {
        $progressText.text('Memulai proses...');
        $progressFill.css('width', '0%');
        $progressPercentage.text('0%');
        $ocrProgress.show();
    }
    
    function hideOCRProgress() {
        $ocrProgress.hide();
    }
    
    // Typing indicator functions removed - streaming handles real-time display
    // function showTypingIndicator() { ... }
    // function hideTypingIndicator() { ... }

    async function performOCR(imageData) {
        try {
            showOCRProgress();

            // Check if Tesseract is available
            if (typeof Tesseract === 'undefined') {
                throw new Error('Tesseract is not available');
            }

            // Use createWorker approach for v2.1.5
            const worker = Tesseract.createWorker({
                logger: m => {
                    console.log(m);
                    
                    // Update progress berdasarkan status
                    if (m.status) {
                        $progressText.text(getProgressMessage(m.status));
                    }
                    
                    // Update progress bar jika ada progress
                    if (m.progress !== undefined) {
                        const percentage = Math.round(m.progress * 100);
                        $progressFill.css('width', percentage + '%');
                        $progressPercentage.text(percentage + '%');
                    }
                }
            });

            await worker.load();
            await worker.loadLanguage('ind+eng');
            await worker.initialize('ind+eng');
            
            const { data: { text } } = await worker.recognize(imageData);
            await worker.terminate();

            hideOCRProgress();
            
            // Clean up the extracted text while preserving line breaks
            const cleanText = text.trim()
                .replace(/\n{3,}/g, '\n\n') // Replace 3+ consecutive newlines with 2
                .replace(/[ \t]+$/gm, ''); // Remove trailing spaces/tabs from each line
            
            if (cleanText) {
                $userInput.val(cleanText);
                autoResizeTextarea($userInput);
                $userInput.focus();
            } else {
                alert('Tidak dapat mengekstrak teks dari gambar. Pastikan gambar memiliki teks yang jelas.');
            }

        } catch (error) {
            hideOCRProgress();
            console.error('OCR Error:', error);
            alert('Terjadi kesalahan saat memproses gambar. Silakan coba lagi dengan error: ' + error.message);
        }
    }

    function getProgressMessage(status) {
        const messages = {
            'loading tesseract core': 'Memuat Tesseract...',
            'initializing tesseract': 'Menginisialisasi Tesseract...',
            'loading language traineddata': 'Memuat data bahasa...',
            'initializing api': 'Menginisialisasi API...',
            'recognizing text': 'Mengenali teks...',
            'done': 'Selesai!'
        };
        return messages[status] || 'Memproses...';
    }
    
    function getRecentHistory() {
        const chatHistory = getCurrentChatHistory();
        return chatHistory.slice(-MAX_HISTORY);
    }
    
    // Auto-resize textarea functionality
    function autoResizeTextarea($textarea) {
        $textarea.css('height', 'auto');
        const scrollHeight = $textarea[0].scrollHeight;
        const maxHeight = 120; // Max height in pixels
        
        if (scrollHeight > maxHeight) {
            $textarea.css({
                'height': maxHeight + 'px',
                'overflow-y': 'auto'
            });
        } else {
            $textarea.css({
                'height': scrollHeight + 'px',
                'overflow-y': 'hidden'
            });
        }
    }

    function sendMessage() {
        const userMessage = $userInput.val().trim();
        
        // Validate selected model
        if (!modelConfigManager.isValidModel(selectedModel)) {
            alert(`Model '${selectedModel}' tidak valid atau tidak aktif. Silakan pilih model lain.`);
            $sendBtn.prop('disabled', false).removeClass('btn-loading');
            return;
        }
        
        // For OCR High mode, allow either image or text (not both required)
        if (currentMode === 'uas-math') {
            if (!currentImage && !userMessage) {
                alert('Mode OCR High memerlukan gambar atau pesan teks');
                return;
            }
        } else if (!userMessage) {
            return;
        }

        // Disable send button during streaming
        $sendBtn.prop('disabled', true).addClass('btn-loading');

        // Get current chat history based on mode
        let chatHistory = getCurrentChatHistory();
        
        // Clear input field immediately
        $userInput.val('');
        autoResizeTextarea($userInput);

        // Get recent chat history for context
        // OCR High mode: TANPA HISTORY - setiap chat independen
        const recentHistory = (currentMode === 'uas' || currentMode === 'uas-math') ? [] : getRecentHistory();
        
        // Check if streaming is available
        const useStreaming = window.StreamingChat;
        
        if (useStreaming) {
            // Use streaming for all modes
            const streamingChat = new StreamingChat();
            let streamEndpoint;
            
            switch(currentMode) {
                case 'uas':
                    streamEndpoint = 'api_uas_stream.php';
                    break;
                case 'uas-math':
                    streamEndpoint = 'api_uas_math_stream.php';
                    break;
                default:
                    streamEndpoint = 'api_stream.php';
            }
            
            // For OCR High mode, send image data as well
            if (currentMode === 'uas-math') {
                console.log('🟣 Sending OCR High mode message');
                console.log('📸 Image data:', currentImage ? 'Present (' + currentImage.length + ' chars)' : 'No image');
                console.log('💬 Message:', userMessage || 'No message');
                
                // Always show the image in streaming chat, but skip duplicate user message if image was uploaded without text
                const skipUserMessage = false; // Always show in streaming to display image properly
                
                // OCR High mode: Tanpa history, setiap chat independen
                streamingChat.sendMathMessageWithStreaming(userMessage, [], streamEndpoint, selectedModel, currentImage, (fullResponse) => {
                    console.log('✅ OCR High streaming completed');
                    console.log('📝 Full response length:', fullResponse ? fullResponse.length : 'No response');
                    
                    // Add messages to history after streaming completes
                    chatHistory = getCurrentChatHistory();
                    
                    // Add user message with image info to history
                    const userMessageForHistory = {
                        sender: 'user', 
                        text: userMessage || 'Gambar soal matematika',
                        hasImage: !!currentImage,
                        imageId: currentImageId,
                        timestamp: new Date().toISOString()
                    };
                    
                    chatHistory.push(userMessageForHistory);
                    chatHistory.push({ 
                        sender: 'bot', 
                        text: fullResponse,
                        timestamp: new Date().toISOString()
                    });
                    
                    // Update the appropriate history array
                    chatHistoryUASMath = chatHistory;
                    
                    saveChatHistory();
                    
                    // Clear image after sending
                    clearImagePreview();
                    
                    // Re-enable send button
                    $sendBtn.prop('disabled', false).removeClass('btn-loading');
                }, skipUserMessage);
            } else {
                streamingChat.sendMessageWithStreaming(userMessage, recentHistory, streamEndpoint, selectedModel, (fullResponse) => {
                    // Add messages to history after streaming completes
                    chatHistory = getCurrentChatHistory();
                    chatHistory.push({ sender: 'user', text: userMessage });
                    chatHistory.push({ sender: 'bot', text: fullResponse });
                    
                    // Update the appropriate history array
                    if (currentMode === 'uas') {
                        chatHistoryUAS = chatHistory;
                    } else {
                        chatHistoryDefault = chatHistory;
                    }
                    
                    saveChatHistory();
                    
                    // Re-enable send button
                    $sendBtn.prop('disabled', false).removeClass('btn-loading');
                });
            }
            
        } else {
            // Fallback to regular API (untuk mode yang tidak support streaming)
            
            // Add user message to current chat history
            const displayMessage = userMessage || (currentMode === 'uas-math' && currentImage ? 'Gambar soal matematika' : userMessage);
            chatHistory.push({ 
                sender: 'user', 
                text: displayMessage,
                hasImage: currentMode === 'uas-math' && !!currentImage 
            });
            
            // Update the appropriate history array
            switch(currentMode) {
                case 'uas':
                    chatHistoryUAS = chatHistory;
                    break;
                case 'uas-math':
                    chatHistoryUASMath = chatHistory;
                    break;
                default:
                    chatHistoryDefault = chatHistory;
            }
            
            saveChatHistory();
            displayChatHistory();

            // Typing indicator removed - streaming handles real-time display
            
            // Choose API endpoint based on mode
            let apiUrl;
            let requestData;
            
            switch(currentMode) {
                case 'uas':
                    apiUrl = 'api_uas_stream.php';
                    requestData = { 
                        message: userMessage,
                        history: recentHistory,
                        mode: currentMode,
                        model: selectedModel
                    };
                    break;
                case 'uas-math':
                    if (!currentImage) {
                        alert('Mode OCR High memerlukan gambar');
                        $sendBtn.prop('disabled', false).removeClass('btn-loading');
                        return;
                    }
                    apiUrl = 'api_uas_math_stream.php';
                    requestData = { 
                        message: userMessage,
                        history: recentHistory,
                        model: selectedModel,
                        image: currentImage
                    };
                    break;
                default:
                    apiUrl = 'api_stream.php';
                    requestData = { 
                        message: userMessage,
                        history: recentHistory,
                        mode: currentMode,
                        model: selectedModel
                    };
            }

            // Send message to server
            $.ajax({
                url: apiUrl,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(requestData),
                success: function(data) {
                    if (data.reply) {
                        // Add bot reply to current chat history immediately
                        chatHistory = getCurrentChatHistory();
                        chatHistory.push({ sender: 'bot', text: data.reply });
                        
                        // Update the appropriate history array
                        switch(currentMode) {
                            case 'uas':
                                chatHistoryUAS = chatHistory;
                                break;
                            case 'uas-math':
                                chatHistoryUASMath = chatHistory;
                                // Clear image after successful response
                                clearImagePreview();
                                break;
                            default:
                                chatHistoryDefault = chatHistory;
                        }
                        
                        saveChatHistory();
                        displayChatHistory();
                        
                        // Re-enable send button
                        $sendBtn.prop('disabled', false).removeClass('btn-loading');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error sending message:', error);
                    
                    // Add error message to chat
                    chatHistory = getCurrentChatHistory();
                    chatHistory.push({ sender: 'bot', text: '⚠️ Maaf, terjadi kesalahan saat memproses permintaan Anda. Silakan coba lagi.' });
                    
                    // Update the appropriate history array
                    switch(currentMode) {
                        case 'uas':
                            chatHistoryUAS = chatHistory;
                            break;
                        case 'uas-math':
                            chatHistoryUASMath = chatHistory;
                            break;
                        default:
                            chatHistoryDefault = chatHistory;
                    }
                    
                    saveChatHistory();
                    displayChatHistory();
                    
                    // Re-enable send button
                    $sendBtn.prop('disabled', false).removeClass('btn-loading');
                }
            });
        }
    }

    // Function to clear storage and cache
    function clearStorageAndCache() {
        let modeText;
        switch(currentMode) {
            case 'uas':
                modeText = 'Mode OCR Low';
                break;
            case 'uas-math':
                modeText = 'Mode OCR High';
                break;
            default:
                modeText = 'Mode Chat';
        }
        
        if (confirm(`Apakah Anda yakin ingin menghapus riwayat chat untuk ${modeText}? Tindakan ini tidak dapat dibatalkan.`)) {
            try {
                // Clear chat history for current mode
                switch(currentMode) {
                    case 'uas':
                        chatHistoryUAS = [];
                        localStorage.removeItem('chatHistoryUAS');
                        break;
                    case 'uas-math':
                        chatHistoryUASMath = [];
                        localStorage.removeItem('chatHistoryUASMath');
                        // Also clear math images for this mode
                        clearMathImages();
                        break;
                    default:
                        chatHistoryDefault = [];
                        localStorage.removeItem('chatHistoryDefault');
                }
                
                // Clear chat display
                $chatBox.empty();
                
                // Show success message
                alert(`Riwayat chat ${modeText} berhasil dihapus!`);
                
            } catch (error) {
                console.error('Error clearing storage:', error);
                alert('Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
            }
        }
    }
    
    // Function to clear all storage and cache (all modes)
    function clearAllStorageAndCache() {
        if (confirm('Apakah Anda yakin ingin menghapus SEMUA riwayat chat (Mode Chat, OCR Low, dan OCR High) serta cache? Tindakan ini tidak dapat dibatalkan.')) {
            try {
                // Clear localStorage
                localStorage.clear();
                
                // Clear sessionStorage
                sessionStorage.clear();
                
                // Clear chat history arrays
                chatHistoryDefault = [];
                chatHistoryUAS = [];
                chatHistoryUASMath = [];
                
                // Clear current image
                clearImagePreview();
                
                // Clear chat display
                $chatBox.empty();
                
                // Reset to default mode
                currentMode = 'default';
                updateModeButtons();
                
                // Show success message
                alert('Semua data berhasil dihapus!');
                
            } catch (error) {
                console.error('Error clearing all storage:', error);
                alert('Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
            }
        }
    }

    // Function to clear comprehensive cache and storage with advanced options
    function clearComprehensiveCache() {
        const confirmMessage = `🧹 CLEAR ALL CACHE & STORAGE
        
Ini akan menghapus:
✓ Semua riwayat chat (Mode Chat, OCR Low, OCR High)
✓ Preferensi model dan pengaturan
✓ Cache browser dan temporary files
✓ Session storage dan cookies (jika ada)
✓ Service worker cache (jika ada)
✓ IndexedDB storage (jika ada)

⚠️ PERINGATAN: Tindakan ini tidak dapat dibatalkan!
⚠️ Anda akan kembali ke pengaturan awal.

Lanjutkan?`;

        if (confirm(confirmMessage)) {
            try {
                // Show loading indicator
                const $clearCacheBtn = $('#clear-cache-btn');
                const originalText = $clearCacheBtn.html();
                $clearCacheBtn.html('🔄 Clearing...').prop('disabled', true);

                // Clear all localStorage
                localStorage.clear();
                
                // Clear all sessionStorage
                sessionStorage.clear();
                
                // Clear all chat history arrays
                chatHistoryDefault = [];
                chatHistoryUAS = [];
                chatHistoryUASMath = [];
                
                // Clear current image
                clearImagePreview();
                
                // Clear chat display
                $chatBox.empty();
                
                // Reset to default mode and model
                currentMode = 'default';
                selectedModel = 'gpt-5.1';
                currentImage = null;
                
                // Reset UI
                updateModeButtons();
                $modelSelect.val(selectedModel);
                $userInput.val('');
                
                // Clear browser cache (if possible)
                if ('caches' in window) {
                    caches.keys().then(function(names) {
                        for (let name of names) {
                            caches.delete(name);
                        }
                    });
                }
                
                // Clear IndexedDB (if any)
                if ('indexedDB' in window) {
                    try {
                        const dbs = ['tesseract-cache', 'mathjax-cache', 'app-cache'];
                        dbs.forEach(dbName => {
                            const deleteReq = indexedDB.deleteDatabase(dbName);
                            deleteReq.onsuccess = () => console.log(`${dbName} cleared`);
                        });
                    } catch (e) {
                        console.log('IndexedDB clear attempted');
                    }
                }
                
                // Success feedback
                setTimeout(() => {
                    $clearCacheBtn.html('✅ Cleared!').css('background', '#10b981');
                    
                    setTimeout(() => {
                        $clearCacheBtn.html(originalText).css('background', '').prop('disabled', false);
                        
                        // Show comprehensive success message
                        alert(`✅ PEMBERSIHAN SELESAI!

🗑️ Berhasil dihapus:
• ${Object.keys(localStorage).length || 0}+ localStorage items
• ${Object.keys(sessionStorage).length || 0}+ sessionStorage items  
• Semua riwayat chat (3 mode)
• Cache browser dan temporary files
• Pengaturan dan preferensi

🔄 Aplikasi telah direset ke pengaturan awal.
💡 Silakan mulai chat baru!`);
                        
                    }, 2000);
                }, 1000);
                
            } catch (error) {
                console.error('Error clearing comprehensive cache:', error);
                alert('❌ Terjadi kesalahan saat membersihkan cache.\nSilakan refresh halaman dan coba lagi.');
                
                // Reset button state
                $('#clear-cache-btn').html(originalText).prop('disabled', false);
            }
        }
    }

    // Event handlers
    $sendBtn.on('click', sendMessage);

    // Handle clear button clicks
    $('#clear-btn').on('click', function(e) {
        e.preventDefault();
        clearStorageAndCache();
    });

    $('#clear-btn').on('contextmenu', function(e) {
        e.preventDefault();
        clearAllStorageAndCache();
    });

    $('#clear-btn').on('dblclick', function(e) {
        e.preventDefault();
        clearAllStorageAndCache();
    });

    // Handle clear cache button
    $('#clear-cache-btn').on('click', function(e) {
        e.preventDefault();
        clearComprehensiveCache();
    });

    // Enter key handler
    $userInput.on('keydown', function(e) {
        if (e.key === 'Enter') {
            if (e.shiftKey) {
                // Allow new line with Shift+Enter
                return;
            } else {
                e.preventDefault();
                sendMessage();
            }
        }
    });

    // Handle input untuk auto-resize yang lebih responsive
    $userInput.on('input', function() {
        autoResizeTextarea($(this));
    });

    // Handle keyup untuk auto-resize yang lebih responsive
    $userInput.on('keyup', function() {
        autoResizeTextarea($(this));
    });

    // Handle paste events for images
    $userInput.on('paste', function(e) {
        const items = (e.clipboardData || e.originalEvent.clipboardData).items;
        
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                e.preventDefault();
                const file = items[i].getAsFile();
                
                if (currentMode === 'uas-math') {
                    // Mode OCR High: Store image for GPT Vision
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        currentImage = event.target.result;
                        $imagePreview.attr('src', currentImage);
                        $imagePreviewContainer.show();
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Mode Chat dan OCR Low: Use Tesseract OCR
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        performOCR(event.target.result);
                    };
                    reader.readAsDataURL(file);
                }
                break;
            }
        }
    });

    // Handle drag and drop for images
    $userInput.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('drag-over');
    });

    $userInput.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
    });

    $userInput.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0 && files[0].type.startsWith('image/')) {
            const file = files[0];
            
            if (currentMode === 'uas-math') {
                // Mode OCR High: Store image for GPT Vision
                const reader = new FileReader();
                reader.onload = function(event) {
                    currentImage = event.target.result;
                    $imagePreview.attr('src', currentImage);
                    $imagePreviewContainer.show();
                };
                reader.readAsDataURL(file);
            } else {
                // Mode Chat dan OCR Low: Use Tesseract OCR
                const reader = new FileReader();
                reader.onload = function(event) {
                    performOCR(event.target.result);
                };
                reader.readAsDataURL(file);
            }
        }
    });
});
