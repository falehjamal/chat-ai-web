// Streaming chat functionality
class StreamingChat {
    constructor() {
        this.isStreaming = false;
        this.currentEventSource = null;
        this.currentBotMessageElement = null;
        this.streamingText = '';
        this.typingSpeed = 30; // ms per character for typing effect
    }

    // Send message with streaming response for math mode with optional image
    sendMathMessageWithStreaming(message, chatHistory, endpoint = 'api_uas_math_stream.php', model = 'gpt-4o', imageBase64 = null, onComplete = null) {
        console.log('🚀 Starting sendMathMessageWithStreaming');
        console.log('📸 Image:', imageBase64 ? 'Present (' + imageBase64.length + ' chars)' : 'No image (text only)');
        console.log('🎯 Endpoint:', endpoint);
        console.log('🤖 Model:', model);
        
        if (this.isStreaming) {
            console.warn('Already streaming, please wait...');
            return;
        }

        this.isStreaming = true;
        this.streamingText = '';

        // Add user message to chat immediately (with or without image indicator)
        const displayMessage = message || (imageBase64 ? 'Gambar soal matematika' : 'Pertanyaan matematika');
        this.addUserMessage(displayMessage, !!imageBase64);

        // Create bot message placeholder with typing indicator
        this.currentBotMessageElement = this.createBotMessagePlaceholder();
        
        // Show typing indicator
        this.showTypingIndicator();

        // Prepare data for streaming with image
        // Note: Even though UAS Math doesn't use chat history for API, we still track it locally
        const streamData = {
            message: message,
            history: [], // UAS Math API doesn't use history, but we still save locally
            model: model,
            image: imageBase64
        };

        console.log('📤 Sending stream data:', JSON.stringify({
            message: message,
            historyLength: 0, // Always 0 for UAS Math API
            model: model,
            imageLength: imageBase64 ? imageBase64.length : 0
        }));

        // Start streaming request with specified endpoint
        this.startStreamingRequest(streamData, endpoint, onComplete);
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
        console.log('📨 Handling stream event:', eventType, data);
        
        switch (eventType) {
            case 'status':
                if (data.type === 'typing_start') {
                    this.showTypingIndicator();
                } else if (data.type === 'typing_end') {
                    this.hideTypingIndicator();
                    this.streamingText = data.full_text || this.streamingText;
                    this.finalizeMessage();
                } else if (data.status) {
                    // Handle status messages from math API
                    console.log('📊 Status:', data.status);
                }
                break;

            case 'stream':
                // Handle streaming content from math API
                console.log('🌊 Stream content received:', data.content ? data.content.length + ' chars' : 'no content');
                if (data.content) {
                    this.hideTypingIndicator();
                    this.appendToStreamingMessage(data.content);
                    this.streamingText += data.content;
                }
                break;

            case 'chunk':
                console.log('📦 Chunk received:', data.content ? data.content.length + ' chars' : 'no content');
                if (data.content) {
                    this.hideTypingIndicator();
                    this.appendToStreamingMessage(data.content);
                    this.streamingText = data.full_text || this.streamingText;
                }
                break;

            case 'complete':
                console.log('✅ Stream completed');
                this.hideTypingIndicator();
                this.finalizeMessage();
                break;

            case 'error':
                console.error('❌ Stream error:', data.error);
                this.handleStreamError(data.error);
                break;

            default:
                // Handle default message format (fallback)
                console.log('🔄 Default handler for event:', eventType);
                if (data.content) {
                    console.log('📝 Content in default handler:', data.content.length + ' chars');
                    this.hideTypingIndicator();
                    this.appendToStreamingMessage(data.content);
                    this.streamingText += data.content;
                } else {
                    console.log('❓ Unknown event type:', eventType, data);
                }
        }
    }

    // Add user message to chat
    addUserMessage(message, hasImage = false) {
        const $chatBox = $('#chat-box');
        let displayMessage = message;
        
        if (hasImage) {
            displayMessage = message ? `📷 ${message}` : '📷 Gambar soal matematika';
        }
        
        const $messageDiv = $('<div>').addClass('user').text(displayMessage);
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
            
            // Replace streaming content with final formatted content using markdown renderer
            const finalText = this.streamingText;
            const formattedHtml = this.formatBotResponseWithMath(finalText);
            
            // Add copy button to the message
            const messageWithCopyBtn = this.addCopyButton(formattedHtml);
            this.currentBotMessageElement.html(messageWithCopyBtn);
            
            // Render math expressions
            if (window.markdownMathRenderer) {
                markdownMathRenderer.renderMath(this.currentBotMessageElement[0]);
            }
            
            // Setup copy button functionality
            this.setupCopyButton(this.currentBotMessageElement);
        }
    }
    
    // Add copy button to message
    addCopyButton(htmlContent) {
        return `
            <button class="copy-btn" title="Copy response">📋 Copy</button>
            ${htmlContent}
        `;
    }
    
    // Setup copy button functionality
    setupCopyButton(messageElement) {
        const copyBtn = messageElement.find('.copy-btn');
        const originalText = this.streamingText;
        
        copyBtn.on('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            try {
                await navigator.clipboard.writeText(originalText);
                
                // Visual feedback
                const btn = $(this);
                const originalText = btn.text();
                
                btn.addClass('copied')
                   .text('Copied!')
                   .css('background', '#10b981');
                
                setTimeout(() => {
                    btn.removeClass('copied')
                       .text(originalText)
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

    // Format bot response with markdown and math rendering - ALL FORMATTING IN FRONTEND
    formatBotResponseWithMath(text) {
        if (!text) return '';
        
        // ALWAYS use the markdown math renderer for ALL modes
        if (window.markdownMathRenderer) {
            console.log('🎨 Using frontend renderer for ALL formatting');
            return markdownMathRenderer.render(text);
        } else {
            // Enhanced fallback formatting
            console.log('⚠️ Markdown renderer not available, using enhanced fallback');
            return this.formatBotResponseEnhanced(text);
        }
    }

    // Enhanced fallback formatting with more regex processing
    formatBotResponseEnhanced(text) {
        if (!text) return '';
        
        let formatted = text
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
            // Convert double line breaks to paragraph breaks
            .replace(/\n\n/g, '</p><p>')
            // Add opening and closing p tags
            .replace(/^/, '<p>')
            .replace(/$/, '</p>')
            // Convert remaining single line breaks to <br>
            .replace(/\n/g, '<br>')
            // Clean up empty paragraphs
            .replace(/<p>\s*<\/p>/g, '')
            .replace(/<p><\/p>/g, '')
            // Clean up
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
