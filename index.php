<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/tesseract.js@v2.1.5/dist/tesseract.min.js"></script>
</head>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #chat-container {
        width: 90%;
        max-width: 600px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    #chat-box {
        height: 70vh;
        padding: 20px;
        overflow-y: auto;
        background: #ffffff;
    }

    #chat-box::-webkit-scrollbar {
        width: 6px;
    }

    #chat-box::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    #chat-box::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }

    .user, .bot {
        margin: 8px 0;
        padding: 12px 16px;
        border-radius: 12px;
        max-width: 80%;
        word-wrap: break-word;
        position: relative;
        animation: fadeIn 0.3s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .user {
        background: #2563eb;
        color: white;
        margin-left: auto;
    }

    .bot {
        background: #f3f4f6;
        color: #1f2937;
        margin-right: auto;
    }

    #input-container {
        display: flex;
        padding: 16px;
        background: #ffffff;
        border-top: 1px solid #e5e7eb;
    }

    #user-input {
        flex: 1;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        margin-right: 12px;
        font-size: 16px;
        font-family: 'Inter', sans-serif;
        transition: border-color 0.3s ease;
        resize: none;
        overflow-y: hidden;
        min-height: 20px;
        max-height: 120px;
        line-height: 1.4;
    }

    #user-input:focus {
        outline: none;
        border-color: #2563eb;
    }

    #send-btn {
        background: #2563eb;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    #send-btn:hover {
        background: #1d4ed8;
    }

    #send-btn:active {
        transform: scale(0.98);
    }

    @media (max-width: 768px) {
        #chat-container {
            width: 95%;
            height: 90vh;
        }

        #chat-box {
            height: calc(90vh - 80px);
        }
    }

    /* Loading indicator */
    #ocr-loading {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1001;
    }

    #ocr-loading-content {
        background: white;
        padding: 32px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }

    .loading-spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid #2563eb;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto 16px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
<body>
    <div id="chat-container">
        <div id="chat-box"></div>
        <div id="input-container">
            <textarea id="user-input" placeholder="Type your message..." rows="1"></textarea>
            <button id="send-btn">Send</button>
        </div>
    </div>

    <!-- OCR Loading -->
    <div id="ocr-loading">
        <div id="ocr-loading-content">
            <div class="loading-spinner"></div>
            <h3>Mengekstrak teks dari gambar...</h3>
            <p>Mohon tunggu, proses ini mungkin memakan beberapa detik.</p>
        </div>
    </div>

    <script>
        const chatBox = document.getElementById('chat-box');
        const userInput = document.getElementById('user-input');
        const sendBtn = document.getElementById('send-btn');
        const MAX_HISTORY = 10; // Maximum number of messages to keep for context

        // Load chat history from localStorage
        let chatHistory = JSON.parse(localStorage.getItem('chatHistory')) || [];
        
        // OCR variables
        const ocrLoading = document.getElementById('ocr-loading');

        displayChatHistory();
        
        // Initialize textarea auto-resize
        autoResizeTextarea(userInput);

        function displayChatHistory() {
            chatBox.innerHTML = '';
            chatHistory.forEach(msg => {
                const messageDiv = document.createElement('div');
                messageDiv.className = msg.sender;
                messageDiv.textContent = msg.text;
                chatBox.appendChild(messageDiv);
            });
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function getRecentHistory() {
            // Get the last 10 messages for context
            return chatHistory.slice(-MAX_HISTORY);
        }

        function showOCRLoading() {
            ocrLoading.style.display = 'flex';
        }

        function hideOCRLoading() {
            ocrLoading.style.display = 'none';
        }

        // Auto-resize textarea function
        function autoResizeTextarea(textarea) {
            textarea.style.height = 'auto';
            const newHeight = Math.min(textarea.scrollHeight, 120); // max-height: 120px
            textarea.style.height = newHeight + 'px';
        }

        async function performOCR(imageData) {
            try {
                showOCRLoading();

                const { data: { text } } = await Tesseract.recognize(
                    imageData,
                    'ind+eng', // Indonesian and English
                    {
                        logger: m => console.log(m) // Optional: show progress in console
                    }
                );

                hideOCRLoading();
                
                // Clean up the extracted text while preserving line breaks
                const cleanText = text.trim()
                    .replace(/\n{3,}/g, '\n\n') // Replace 3+ consecutive newlines with 2
                    .replace(/[ \t]+$/gm, ''); // Remove trailing spaces/tabs from each line
                
                if (cleanText) {
                    userInput.value = cleanText;
                    autoResizeTextarea(userInput);
                    userInput.focus();
                } else {
                    alert('Tidak dapat mengekstrak teks dari gambar. Pastikan gambar memiliki teks yang jelas.');
                }

            } catch (error) {
                hideOCRLoading();
                console.error('OCR Error:', error);
                alert('Terjadi kesalahan saat memproses gambar. Silakan coba lagi.');
            }
        }

        // Handle paste event
        userInput.addEventListener('paste', async (e) => {
            const items = e.clipboardData.items;
            
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
            const userMessage = userInput.value.trim();
            if (!userMessage) return;

            // Add user message to chat history
            chatHistory.push({ sender: 'user', text: userMessage });
            localStorage.setItem('chatHistory', JSON.stringify(chatHistory));
            displayChatHistory();

            // Clear input field
            userInput.value = '';
            autoResizeTextarea(userInput);

            // Get recent chat history for context
            const recentHistory = getRecentHistory();

            // Send message and history to server
            fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    message: userMessage,
                    history: recentHistory
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.reply) {
                    // Add bot reply to chat history
                    chatHistory.push({ sender: 'bot', text: data.reply });
                    localStorage.setItem('chatHistory', JSON.stringify(chatHistory));
                    displayChatHistory();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Show error message in chat
                chatHistory.push({ sender: 'bot', text: 'Sorry, there was an error processing your message.' });
                localStorage.setItem('chatHistory', JSON.stringify(chatHistory));
                displayChatHistory();
            });
        }

        sendBtn.addEventListener('click', sendMessage);
        
        // Auto-resize textarea on input
        userInput.addEventListener('input', () => {
            autoResizeTextarea(userInput);
        });

        // Handle Enter key for textarea (Enter to send, Shift+Enter for new line)
        userInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    </script>
</body>
</html>
