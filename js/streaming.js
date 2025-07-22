// Streaming chat functionality
class StreamingChat {
    constructor() {
        this.isStreaming = false;
        this.currentEventSource = null;
        this.currentBotMessageElement = null;
        this.streamingText = '';
        this.typingSpeed = 30; // ms per character for typing effect
    }

    // Send message with streaming response
    sendMessageWithStreaming(message, chatHistory, endpoint = 'api_stream.php', model = 'gpt-4', onComplete = null) {
        if (this.isStreaming) {
            console.warn('Already streaming, please wait...');
            return;
        }

        this.isStreaming = true;
        this.streamingText = '';

        // Add user message to chat immediately
        this.addUserMessage(message);

        // Create bot message placeholder with typing indicator
        this.currentBotMessageElement = this.createBotMessagePlaceholder();
        
        // Show typing indicator
        this.showTypingIndicator();

        // Prepare data for streaming
        const streamData = {
            message: message,
            history: chatHistory || [],
            model: model
        };

        // Start streaming request with specified endpoint
        this.startStreamingRequest(streamData, endpoint, onComplete);
    }

    // Start streaming request using fetch with ReadableStream
    async startStreamingRequest(data, endpoint, onComplete) {
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream',
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();
                
                if (done) {
                    break;
                }

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || ''; // Keep incomplete line in buffer

                for (const line of lines) {
                    this.processStreamLine(line.trim());
                }
            }

            // Process any remaining buffer
            if (buffer.trim()) {
                this.processStreamLine(buffer.trim());
            }

        } catch (error) {
            console.error('Streaming error:', error);
            this.handleStreamError(error.message);
        } finally {
            this.stopStreaming();
            if (onComplete) {
                onComplete(this.streamingText);
            }
        }
    }

    // Process individual stream line
    processStreamLine(line) {
        if (!line) return;

        // Parse SSE format
        if (line.startsWith('event: ')) {
            this.currentEventType = line.substring(7);
            return;
        }

        if (line.startsWith('data: ')) {
            const dataStr = line.substring(6);
            
            try {
                const data = JSON.parse(dataStr);
                this.handleStreamEvent(data, this.currentEventType || 'message');
            } catch (e) {
                console.error('Failed to parse stream data:', e, dataStr);
            }
        }
    }

    // Handle stream events
    handleStreamEvent(data, eventType) {
        switch (eventType) {
            case 'status':
                if (data.type === 'typing_start') {
                    this.showTypingIndicator();
                } else if (data.type === 'typing_end') {
                    this.hideTypingIndicator();
                    this.streamingText = data.full_text || this.streamingText;
                    this.finalizeMessage();
                }
                break;

            case 'chunk':
                if (data.content) {
                    this.hideTypingIndicator();
                    this.appendToStreamingMessage(data.content);
                    this.streamingText = data.full_text || this.streamingText;
                }
                break;

            case 'complete':
                this.hideTypingIndicator();
                this.finalizeMessage();
                break;

            case 'error':
                this.handleStreamError(data.error);
                break;

            default:
                console.log('Unknown event type:', eventType, data);
        }
    }

    // Add user message to chat
    addUserMessage(message) {
        const $chatBox = $('#chat-box');
        const $messageDiv = $('<div>').addClass('user').text(message);
        $chatBox.append($messageDiv);
        this.scrollToBottom();
    }

    // Create bot message placeholder
    createBotMessagePlaceholder() {
        const $chatBox = $('#chat-box');
        const $messageDiv = $('<div>').addClass('bot streaming').attr('id', 'streaming-message');
        
        const messageHtml = `
            <span class="streaming-text"></span>
            <span class="typing-indicator">
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
            </span>
        `;
        
        $messageDiv.html(messageHtml);
        $chatBox.append($messageDiv);
        this.scrollToBottom();
        return $('#streaming-message');
    }

    // Show typing indicator
    showTypingIndicator() {
        if (this.currentBotMessageElement) {
            this.currentBotMessageElement.find('.typing-indicator').show();
        }
    }

    // Hide typing indicator
    hideTypingIndicator() {
        if (this.currentBotMessageElement) {
            this.currentBotMessageElement.find('.typing-indicator').hide();
        }
    }

    // Append text to streaming message with typing effect
    appendToStreamingMessage(text) {
        if (!this.currentBotMessageElement) return;

        const $streamingText = this.currentBotMessageElement.find('.streaming-text');
        
        // Add text directly
        let currentText = $streamingText.text();
        const newText = currentText + text;
        
        $streamingText.text(newText);
        this.scrollToBottom();
    }

    // Finalize message (remove streaming class, clean up)
    finalizeMessage() {
        if (this.currentBotMessageElement) {
            this.currentBotMessageElement.removeClass('streaming');
            this.currentBotMessageElement.removeAttr('id');
            this.hideTypingIndicator();
            
            // Replace streaming content with final formatted content
            const finalText = this.streamingText;
            this.currentBotMessageElement.html(this.formatBotResponse(finalText));
        }
    }

    // Format bot response like the main app
    formatBotResponse(text) {
        if (!text) return '';
        
        let formatted = text
            // Ganti asterisk (*) dengan bullet sederhana di awal baris
            .replace(/^\s*\*\s+/gm, '• ')
            // Hapus asterisk lainnya yang bukan di awal baris
            .replace(/\*/g, '')
            // Bersihkan line breaks berlebihan (lebih dari 2)
            .replace(/\n{3,}/g, '\n\n')
            // Trim whitespace di awal dan akhir
            .trim();
            
        return formatted;
    }

    // Handle stream errors
    handleStreamError(errorMessage) {
        console.error('Stream error:', errorMessage);
        
        if (this.currentBotMessageElement) {
            this.currentBotMessageElement.html(
                `<span class="error-message">❌ Error: ${this.escapeHtml(errorMessage)}</span>`
            );
            this.hideTypingIndicator();
            this.finalizeMessage();
        }
    }

    // Stop streaming
    stopStreaming() {
        this.isStreaming = false;
        if (this.currentEventSource) {
            this.currentEventSource.close();
            this.currentEventSource = null;
        }
        this.currentBotMessageElement = null;
    }

    // Utility functions
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    getCurrentTime() {
        return new Date().toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    scrollToBottom() {
        const chatBox = document.getElementById('chat-box');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }
}

// Export for use in main app
window.StreamingChat = StreamingChat;
