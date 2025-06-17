<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
        transition: border-color 0.3s ease;
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
</style>
<body>
    <div id="chat-container">
        <div id="chat-box"></div>
        <div id="input-container">
            <input type="text" id="user-input" placeholder="Type your message..." />
            <button id="send-btn">Send</button>
        </div>
    </div>

    <script>
        const chatBox = document.getElementById('chat-box');
        const userInput = document.getElementById('user-input');
        const sendBtn = document.getElementById('send-btn');
        const MAX_HISTORY = 10; // Maximum number of messages to keep for context

        // Load chat history from localStorage
        let chatHistory = JSON.parse(localStorage.getItem('chatHistory')) || [];
        displayChatHistory();

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

        function sendMessage() {
            const userMessage = userInput.value.trim();
            if (!userMessage) return;

            // Add user message to chat history
            chatHistory.push({ sender: 'user', text: userMessage });
            localStorage.setItem('chatHistory', JSON.stringify(chatHistory));
            displayChatHistory();

            // Clear input field
            userInput.value = '';

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
        userInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    </script>
</body>
</html>
