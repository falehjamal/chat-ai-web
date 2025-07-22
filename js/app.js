$(document).ready(function() {
    const MAX_HISTORY = 10; // Maximum number of messages to keep for context
    
    // Current mode tracking
    let currentMode = 'default';
    let selectedModel = 'gpt-3.5-turbo'; // Default model sesuai dengan HTML
    
    // Debug: Check if Tesseract is available
    console.log('Tesseract available:', typeof Tesseract !== 'undefined');
    if (typeof Tesseract !== 'undefined') {
        console.log('Tesseract object:', Tesseract);
    }
    
    // Load separate chat histories from localStorage
    let chatHistoryDefault = JSON.parse(localStorage.getItem('chatHistoryDefault')) || [];
    let chatHistoryUAS = JSON.parse(localStorage.getItem('chatHistoryUAS')) || [];
    
    // Get current chat history based on mode
    function getCurrentChatHistory() {
        return currentMode === 'uas' ? chatHistoryUAS : chatHistoryDefault;
    }
    
    // Save chat history to localStorage
    function saveChatHistory() {
        if (currentMode === 'uas') {
            localStorage.setItem('chatHistoryUAS', JSON.stringify(chatHistoryUAS));
        } else {
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
    const $modelSelect = $('#gpt-model');

    // Mode switching functionality
    $modeDefault.on('click', function() {
        if (currentMode !== 'default') {
            currentMode = 'default';
            updateModeButtons();
            displayChatHistory();
        }
    });
    
    $modeUAS.on('click', function() {
        if (currentMode !== 'uas') {
            currentMode = 'uas';
            updateModeButtons();
            displayChatHistory();
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
        
        // Update chat container mode indicator
        const $chatContainer = $('#chat-container');
        if (currentMode === 'uas') {
            $chatContainer.attr('data-mode', 'Mode UAS');
        } else {
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

    function displayChatHistory() {
        $chatBox.empty();
        const chatHistory = getCurrentChatHistory();
        
        chatHistory.forEach(msg => {
            const $messageDiv = $('<div>').addClass(msg.sender);
            
            // Format text untuk bot messages
            if (msg.sender === 'bot') {
                $messageDiv.html(formatBotResponse(msg.text));
            } else {
                $messageDiv.text(msg.text);
            }
            
            $chatBox.append($messageDiv);
        });
        
        $chatBox.scrollTop($chatBox[0].scrollHeight);
    }

    // Fungsi untuk memformat response bot agar lebih rapi
    function formatBotResponse(text) {
        if (!text) return '';
        
        let formatted = text
            // Ganti asterisk (*) dengan bullet sederhana di awal baris
            .replace(/^\s*\*\s+/gm, '• ')
            // Hapus asterisk lainnya yang bukan di awal baris
            .replace(/\*/g, '')
            // Bersihkan line breaks berlebihan (lebih dari 2)
            .replace(/\n{3,}/g, '\n\n')
            // Bersihkan multiple spaces
            .replace(/[ \t]{2,}/g, ' ')
            // Trim setiap baris
            .split('\n').map(line => line.trim()).join('\n')
            // Trim keseluruhan
            .trim();
        
        // Convert line breaks ke HTML untuk display
        formatted = formatted.replace(/\n/g, '<br>');
        
        return formatted;
    }

    function getRecentHistory() {
        // Get the last 10 messages for context from current chat history
        const chatHistory = getCurrentChatHistory();
        return chatHistory.slice(-MAX_HISTORY);
    }

    function showOCRProgress() {
        // Reset progress bar
        $progressText.text('Memulai proses...');
        $progressFill.css('width', '0%');
        $progressPercentage.text('0%');
        $ocrProgress.show();
    }

    function hideOCRProgress() {
        $ocrProgress.hide();
    }

    // Fungsi untuk menampilkan typing indicator
    function showTypingIndicator() {
        // Hapus typing indicator yang mungkin sudah ada
        hideTypingIndicator();
        
        // Buat elemen typing indicator
        const $typingIndicator = $('<div>').addClass('typing-indicator').html(`
            <div class="typing-dots">
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
            </div>
        `);
        
        // Tambahkan ke chat box
        $chatBox.append($typingIndicator);
        
        // Trigger animasi dengan delay kecil
        setTimeout(() => {
            $typingIndicator.addClass('show');
            // Auto scroll ke bottom setelah animasi mulai
            $chatBox.scrollTop($chatBox[0].scrollHeight);
        }, 10);
    }

    // Fungsi untuk menyembunyikan typing indicator
    function hideTypingIndicator() {
        const $indicator = $('.typing-indicator');
        if ($indicator.length) {
            $indicator.removeClass('show');
            setTimeout(() => {
                $indicator.remove();
            }, 300); // Wait for transition to complete
        }
    }

    // Auto-resize textarea function seperti WhatsApp
    function autoResizeTextarea($textarea) {
        const maxHeight = 150; // sesuai dengan CSS max-height
        const minHeight = 44;  // sesuai dengan CSS min-height
        
        // Reset height untuk mendapatkan scrollHeight yang akurat
        $textarea.css('height', 'auto');
        
        // Dapatkan scrollHeight
        const scrollHeight = $textarea[0].scrollHeight;
        
        if (scrollHeight <= maxHeight) {
            // Jika konten tidak melebihi max-height, sesuaikan tinggi
            $textarea.css('height', Math.max(scrollHeight, minHeight) + 'px');
            $textarea.css('overflow-y', 'hidden');
        } else {
            // Jika konten melebihi max-height, set ke max dan aktifkan scroll
            $textarea.css('height', maxHeight + 'px');
            $textarea.css('overflow-y', 'auto');
            // Auto scroll ke bottom
            $textarea.scrollTop($textarea[0].scrollHeight);
        }
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

    // Handle paste event
    $userInput.on('paste', async function(e) {
        const items = e.originalEvent.clipboardData.items;
        
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            
            if (item.type.indexOf('image') !== -1) {
                e.preventDefault(); // Prevent default paste behavior
                
                const blob = item.getAsFile();
                const reader = new FileReader();
                
                reader.onload = function(event) {
                    performOCR(event.target.result);
                };
                
                reader.readAsDataURL(blob);
                break;
            }
        }
    });

    function sendMessage() {
        const userMessage = $userInput.val().trim();
        if (!userMessage) return;

        // Disable send button during streaming
        $sendBtn.prop('disabled', true).addClass('btn-loading');

        // Get current chat history based on mode
        let chatHistory = getCurrentChatHistory();
        
        // Clear input field immediately
        $userInput.val('');
        autoResizeTextarea($userInput);

        // Get recent chat history for context - UAS mode doesn't need history
        const recentHistory = currentMode === 'uas' ? [] : getRecentHistory();
        
        // Check if streaming is available
        const useStreaming = window.StreamingChat;
        
        if (useStreaming) {
            // Use streaming for both modes
            const streamingChat = new StreamingChat();
            const streamEndpoint = currentMode === 'uas' ? 'api_uas_stream.php' : 'api_stream.php';
            
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
            
        } else {
            // Fallback to regular API for UAS mode or if streaming not available
            
            // Add user message to current chat history
            chatHistory.push({ sender: 'user', text: userMessage });
            
            // Update the appropriate history array
            if (currentMode === 'uas') {
                chatHistoryUAS = chatHistory;
            } else {
                chatHistoryDefault = chatHistory;
            }
            
            saveChatHistory();
            displayChatHistory();

            // Show typing indicator
            showTypingIndicator();
            
            // Choose API endpoint based on mode
            const apiUrl = currentMode === 'uas' ? 'api_uas.php' : 'api.php';

            // Send message to server
            $.ajax({
                url: apiUrl,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ 
                    message: userMessage,
                    history: recentHistory,
                    mode: currentMode,
                    model: selectedModel
                }),
                success: function(data) {
                    if (data.reply) {
                        // Simulasi typing delay yang realistis (lebih lama untuk UAS mode)
                        const typingDelay = currentMode === 'uas' ? 
                            Math.random() * 2000 + 2000 : // 2-4 seconds for UAS
                            Math.random() * 1000 + 1000;  // 1-2 seconds for default
                        
                        setTimeout(() => {
                            // Hide typing indicator
                            hideTypingIndicator();
                            
                            // Add bot reply to current chat history
                            chatHistory = getCurrentChatHistory();
                            chatHistory.push({ sender: 'bot', text: data.reply });
                            
                            // Update the appropriate history array
                            if (currentMode === 'uas') {
                                chatHistoryUAS = chatHistory;
                            } else {
                                chatHistoryDefault = chatHistory;
                            }
                            
                            saveChatHistory();
                            displayChatHistory();
                            
                            // Re-enable send button
                            $sendBtn.prop('disabled', false).removeClass('btn-loading');
                        }, typingDelay);
                    } else {
                        hideTypingIndicator();
                        $sendBtn.prop('disabled', false).removeClass('btn-loading');
                    }
                },
                error: function(xhr, status, error) {
                    // Hide typing indicator on error
                    hideTypingIndicator();
                    $sendBtn.prop('disabled', false).removeClass('btn-loading');
                    
                    console.error('Error:', error);
                    
                    // Show error message in chat
                    let chatHistory = getCurrentChatHistory();
                    chatHistory.push({ 
                        sender: 'bot', 
                        text: 'Maaf, terjadi kesalahan saat memproses pesan Anda. Silakan coba lagi.' 
                    });
                    
                    // Update the appropriate history array
                    if (currentMode === 'uas') {
                        chatHistoryUAS = chatHistory;
                    } else {
                        chatHistoryDefault = chatHistory;
                    }
                    
                    saveChatHistory();
                    displayChatHistory();
                }
            });
        }
    }

    // Function to clear storage and cache
    function clearStorageAndCache() {
        const modeText = currentMode === 'uas' ? 'Mode UAS' : 'Mode Default';
        if (confirm(`Apakah Anda yakin ingin menghapus riwayat chat untuk ${modeText}? Tindakan ini tidak dapat dibatalkan.`)) {
            try {
                // Clear chat history for current mode
                if (currentMode === 'uas') {
                    chatHistoryUAS = [];
                    localStorage.removeItem('chatHistoryUAS');
                } else {
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
    
    // Function to clear all storage and cache (both modes)
    function clearAllStorageAndCache() {
        if (confirm('Apakah Anda yakin ingin menghapus SEMUA riwayat chat (Mode Default dan UAS) serta cache? Tindakan ini tidak dapat dibatalkan.')) {
            try {
                // Clear localStorage
                localStorage.clear();
                
                // Clear sessionStorage
                sessionStorage.clear();
                
                // Clear chat history arrays
                chatHistoryDefault = [];
                chatHistoryUAS = [];
                
                // Clear chat display
                $chatBox.empty();
                
                // Clear service worker cache if available
                if ('caches' in window) {
                    caches.keys().then(function(cacheNames) {
                        return Promise.all(
                            cacheNames.map(function(cacheName) {
                                return caches.delete(cacheName);
                            })
                        );
                    }).then(function() {
                        console.log('Cache cleared successfully');
                    }).catch(function(error) {
                        console.log('Error clearing cache:', error);
                    });
                }
                
                // Clear indexedDB if needed
                if ('indexedDB' in window) {
                    // Most basic clear - can be extended if app uses indexedDB
                    try {
                        indexedDB.deleteDatabase('tesseract-cache');
                    } catch (e) {
                        console.log('No indexedDB to clear or error:', e);
                    }
                }
                
                // Show success message
                alert('Semua data berhasil dihapus! Halaman akan di-refresh.');
                
                // Reload page to ensure complete cleanup
                window.location.reload();
                
            } catch (error) {
                console.error('Error clearing storage:', error);
                alert('Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
            }
        }
    }

    // Event handlers
    $sendBtn.on('click', sendMessage);
    
    // Cache jQuery object for clear button
    const $clearBtn = $('#clear-btn');
    
    // Regular click - clear current mode
    $clearBtn.on('click', function(e) {
        e.preventDefault();
        clearStorageAndCache();
    });
    
    // Right click - show options
    $clearBtn.on('contextmenu', function(e) {
        e.preventDefault();
        const choice = confirm('Klik OK untuk menghapus SEMUA riwayat (Default + UAS)\nKlik Cancel untuk menghapus hanya mode saat ini');
        if (choice) {
            clearAllStorageAndCache();
        } else {
            clearStorageAndCache();
        }
    });
    
    // Double click - clear all
    $clearBtn.on('dblclick', function(e) {
        e.preventDefault();
        clearAllStorageAndCache();
    });
    
    // Auto-resize textarea on input (seperti WhatsApp)
    $userInput.on('input propertychange', function() {
        autoResizeTextarea($(this));
    });

    // Handle paste events untuk auto-resize
    $userInput.on('paste', function() {
        // Delay sedikit untuk memastikan konten sudah ter-paste
        setTimeout(() => {
            autoResizeTextarea($(this));
        }, 10);
    });

    // Handle Enter key untuk mengirim pesan (seperti WhatsApp)
    $userInput.on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        } else if (e.key === 'Enter' && e.shiftKey) {
            // Shift+Enter untuk baris baru, auto-resize setelahnya
            setTimeout(() => {
                autoResizeTextarea($(this));
            }, 10);
        }
    });

    // Handle keyup untuk auto-resize yang lebih responsive
    $userInput.on('keyup', function() {
        autoResizeTextarea($(this));
    });
}); 
