$(document).ready(function() {
    const MAX_HISTORY = 10; // Maximum number of messages to keep for context
    
    // Debug: Check if Tesseract is available
    console.log('Tesseract available:', typeof Tesseract !== 'undefined');
    if (typeof Tesseract !== 'undefined') {
        console.log('Tesseract object:', Tesseract);
    }
    
    // Load chat history from localStorage
    let chatHistory = JSON.parse(localStorage.getItem('chatHistory')) || [];
    
    // Cache jQuery objects
    const $chatBox = $('#chat-box');
    const $userInput = $('#user-input');
    const $sendBtn = $('#send-btn');
    const $ocrProgress = $('#ocr-progress');
    const $progressText = $('#ocr-progress-text');
    const $progressFill = $('#ocr-progress-fill');
    const $progressPercentage = $('#ocr-progress-percentage');

    // Initialize
    displayChatHistory();
    autoResizeTextarea($userInput);
    
    // Clean up any leftover typing indicators
    hideTypingIndicator();

    function displayChatHistory() {
        $chatBox.empty();
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
        // Get the last 10 messages for context
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

        // Add user message to chat history
        chatHistory.push({ sender: 'user', text: userMessage });
        localStorage.setItem('chatHistory', JSON.stringify(chatHistory));
        displayChatHistory();

        // Clear input field
        $userInput.val('');
        autoResizeTextarea($userInput);

        // Show typing indicator
        showTypingIndicator();

        // Get recent chat history for context
        const recentHistory = getRecentHistory();

        // Send message and history to server
        $.ajax({
            url: 'api.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ 
                message: userMessage,
                history: recentHistory
            }),
            success: function(data) {
                if (data.reply) {
                    // Simulasi typing delay yang realistis (1-2 detik)
                    const typingDelay = Math.random() * 1000 + 1000; // 1-2 seconds
                    
                    setTimeout(() => {
                        // Hide typing indicator
                        hideTypingIndicator();
                        
                        // Add bot reply to chat history (response sudah dibersihkan di backend)
                        chatHistory.push({ sender: 'bot', text: data.reply });
                        localStorage.setItem('chatHistory', JSON.stringify(chatHistory));
                        displayChatHistory();
                    }, typingDelay);
                } else {
                    hideTypingIndicator();
                }
            },
            error: function(xhr, status, error) {
                // Hide typing indicator on error
                hideTypingIndicator();
                
                console.error('Error:', error);
                // Show error message in chat
                chatHistory.push({ sender: 'bot', text: 'Maaf, terjadi kesalahan saat memproses pesan Anda. Silakan coba lagi.' });
                localStorage.setItem('chatHistory', JSON.stringify(chatHistory));
                displayChatHistory();
            }
        });
    }

    // Function to clear storage and cache
    function clearStorageAndCache() {
        if (confirm('Apakah Anda yakin ingin menghapus semua riwayat chat dan cache? Tindakan ini tidak dapat dibatalkan.')) {
            try {
                // Clear localStorage
                localStorage.clear();
                
                // Clear sessionStorage
                sessionStorage.clear();
                
                // Clear chat history array
                chatHistory = [];
                
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
    $clearBtn.on('click', clearStorageAndCache);
    
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
