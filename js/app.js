$(document).ready(function() {
    const MAX_HISTORY = 10; // Maximum number of messages to keep for context
    
    // Current mode tracking
    let currentMode = 'default';
    let selectedModel = 'gpt-3.5-turbo'; // Default model sesuai dengan HTML
    let currentImage = null; // For storing current image in UAS Math mode
    
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
            updateModeButtons();
            displayChatHistory();
            clearImagePreview();
        }
    });
    
    $modeUAS.on('click', function() {
        if (currentMode !== 'uas') {
            currentMode = 'uas';
            updateModeButtons();
            displayChatHistory();
            clearImagePreview();
        }
    });
    
    $modeUASMath.on('click', function() {
        if (currentMode !== 'uas-math') {
            currentMode = 'uas-math';
            updateModeButtons();
            displayChatHistory();
            clearImagePreview();
        }
    });
    
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
        }
        
        // Update chat container mode indicator
        const $chatContainer = $('#chat-container');
        switch(currentMode) {
            case 'uas':
                $chatContainer.attr('data-mode', 'Mode UAS');
                break;
            case 'uas-math':
                $chatContainer.attr('data-mode', 'Mode UAS Matematika');
                break;
            default:
                $chatContainer.attr('data-mode', 'Mode Default');
        }
    }

    // Initialize
    displayChatHistory();
    autoResizeTextarea($userInput);
    updateModeButtons();
    
    // Load saved model preference
    const savedModel = localStorage.getItem('selectedGPTModel');
    if (savedModel) {
        selectedModel = savedModel;
        $modelSelect.val(savedModel);
    }
    
    // Clean up any leftover typing indicators
    hideTypingIndicator();

    // Image upload functionality for UAS Math mode
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
                currentImage = event.target.result;
                $imagePreview.attr('src', currentImage);
                $imagePreviewContainer.show();
            };
            reader.readAsDataURL(file);
        }
    });

    $removeImage.on('click', function() {
        clearImagePreview();
    });

    function clearImagePreview() {
        currentImage = null;
        $imagePreview.attr('src', '');
        $imagePreviewContainer.hide();
        $imageInput.val('');
    }

    function displayChatHistory() {
        $chatBox.empty();
        const chatHistory = getCurrentChatHistory();
        
        chatHistory.forEach(msg => {
            const $messageDiv = $('<div>').addClass(msg.sender);
            
            // Format text untuk bot messages
            if (msg.sender === 'bot') {
                const formattedText = formatBotMessage(msg.text);
                
                // Add copy button for bot messages
                const messageWithCopyBtn = `
                    <button class="copy-btn" title="Copy response">📋 Copy</button>
                    ${formattedText}
                `;
                
                $messageDiv.html(messageWithCopyBtn);
                
                // Setup copy functionality for this message
                setupCopyButtonForMessage($messageDiv, msg.text);
                
                // Render math if available
                if (window.markdownMathRenderer) {
                    markdownMathRenderer.renderMath($messageDiv[0]);
                }
                
                // Add timestamp if available
                if (msg.timestamp) {
                    const timestamp = new Date(msg.timestamp).toLocaleTimeString();
                    $messageDiv.append(`<div class="timestamp">${timestamp}</div>`);
                }
            } else {
                // User messages - handle image indicator
                let displayText = msg.text;
                if (msg.hasImage) {
                    displayText = `📷 ${msg.text}`;
                }
                $messageDiv.text(displayText);
            }
            
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
    
    function showTypingIndicator() {
        // Remove any existing typing indicator
        $('.typing-indicator-container').remove();
        
        const $typingDiv = $('<div>')
            .addClass('typing-indicator-container bot')
            .html(`
                <div class="typing-indicator">
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                </div>
            `);
        
        $chatBox.append($typingDiv);
        scrollToBottom();
    }
    
    function hideTypingIndicator() {
        $('.typing-indicator-container').remove();
    }

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
        
        // For UAS Math mode, allow either image or text (not both required)
        if (currentMode === 'uas-math') {
            if (!currentImage && !userMessage) {
                alert('Mode UAS Matematika memerlukan gambar atau pesan teks');
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
        // UAS Math mode: TANPA HISTORY - setiap chat independen
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
            
            // For UAS Math mode, send image data as well
            if (currentMode === 'uas-math') {
                console.log('🟣 Sending UAS Math mode message');
                console.log('📸 Image data:', currentImage ? 'Present (' + currentImage.length + ' chars)' : 'No image');
                console.log('💬 Message:', userMessage || 'No message');
                
                // UAS Math mode: Tanpa history, setiap chat independen
                streamingChat.sendMathMessageWithStreaming(userMessage, [], streamEndpoint, selectedModel, currentImage, (fullResponse) => {
                    console.log('✅ UAS Math streaming completed');
                    console.log('📝 Full response length:', fullResponse ? fullResponse.length : 'No response');
                    
                    // Add messages to history after streaming completes
                    chatHistory = getCurrentChatHistory();
                    chatHistory.push({ 
                        sender: 'user', 
                        text: userMessage || 'Gambar soal matematika',
                        hasImage: !!currentImage 
                    });
                    chatHistory.push({ sender: 'bot', text: fullResponse });
                    
                    // Update the appropriate history array
                    chatHistoryUASMath = chatHistory;
                    
                    saveChatHistory();
                    
                    // Clear image after sending
                    clearImagePreview();
                    
                    // Re-enable send button
                    $sendBtn.prop('disabled', false).removeClass('btn-loading');
                });
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

            // Show typing indicator
            showTypingIndicator();
            
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
                        alert('Mode UAS Matematika memerlukan gambar');
                        $sendBtn.prop('disabled', false).removeClass('btn-loading');
                        hideTypingIndicator();
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
                        // Simulasi typing delay yang realistis
                        const typingDelay = currentMode === 'uas' || currentMode === 'uas-math' ? 
                            Math.random() * 2000 + 2000 : // 2-4 seconds for UAS modes
                            Math.random() * 1000 + 1000;  // 1-2 seconds for default
                        
                        setTimeout(() => {
                            // Hide typing indicator
                            hideTypingIndicator();
                            
                            // Add bot reply to current chat history
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
                        }, typingDelay);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error sending message:', error);
                    
                    // Hide typing indicator
                    hideTypingIndicator();
                    
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
                modeText = 'Mode UAS';
                break;
            case 'uas-math':
                modeText = 'Mode UAS Matematika';
                break;
            default:
                modeText = 'Mode Default';
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
        if (confirm('Apakah Anda yakin ingin menghapus SEMUA riwayat chat (Mode Default, UAS, dan UAS Matematika) serta cache? Tindakan ini tidak dapat dibatalkan.')) {
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
✓ Semua riwayat chat (Mode Default, UAS, UAS Math)
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
                selectedModel = 'gpt-3.5-turbo';
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
                    // Mode UAS Matematika: Store image for GPT Vision
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        currentImage = event.target.result;
                        $imagePreview.attr('src', currentImage);
                        $imagePreviewContainer.show();
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Mode Default dan UAS: Use Tesseract OCR
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
                // Mode UAS Matematika: Store image for GPT Vision
                const reader = new FileReader();
                reader.onload = function(event) {
                    currentImage = event.target.result;
                    $imagePreview.attr('src', currentImage);
                    $imagePreviewContainer.show();
                };
                reader.readAsDataURL(file);
            } else {
                // Mode Default dan UAS: Use Tesseract OCR
                const reader = new FileReader();
                reader.onload = function(event) {
                    performOCR(event.target.result);
                };
                reader.readAsDataURL(file);
            }
        }
    });
});
