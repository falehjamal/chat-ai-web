// Streaming chat functionality
class StreamingChat {
    constructor() {
        this.isStreaming = false;
        this.currentEventSource = null;
        this.currentBotMessageElement = null;
        this.streamingText = '';
        this.hasError = false;
        this._renderDebounce = null;
    }

    // Send message with streaming response for math mode with optional image
    sendMathMessageWithStreaming(message, chatHistory, endpoint = 'api_uas_math_stream.php', model = 'gpt-5.2', imageBase64 = null, onComplete = null, skipUserMessage = false) {
        if (this.isStreaming) return;

        this.isStreaming = true;
        this.streamingText = '';
        this.hasError = false;

        if (!skipUserMessage) {
            const displayMessage = message || (imageBase64 ? 'Gambar soal matematika' : 'Pertanyaan matematika');
            this.addUserMessage(displayMessage, !!imageBase64, imageBase64);
        } else if (message) {
            this.addUserMessage(message, false, null);
        }

        this.currentBotMessageElement = this.createBotMessagePlaceholder();

        const streamData = {
            message: message,
            history: [],
            model: model,
            image: imageBase64
        };

        this.startStreamingRequest(streamData, endpoint, onComplete);
    }

    // Send message with streaming response
    sendMessageWithStreaming(message, chatHistory, endpoint = 'api_stream.php', model = 'gpt-5.2', onComplete = null) {
        if (this.isStreaming) return;

        this.isStreaming = true;
        this.streamingText = '';
        this.hasError = false;

        this.addUserMessage(message);
        this.currentBotMessageElement = this.createBotMessagePlaceholder();

        const streamData = {
            message: message,
            history: chatHistory || [],
            model: model
        };

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
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';

                for (const line of lines) {
                    this.processStreamLine(line.trim());
                }
            }

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
                // Silently ignore malformed JSON in stream
            }
        }
    }

    // Handle stream events
    handleStreamEvent(data, eventType) {
        switch (eventType) {
            case 'status':
                if (data.type === 'typing_end') {
                    this.streamingText = data.full_text || this.streamingText;
                    this.finalizeMessage();
                }
                break;

            case 'stream':
            case 'chunk':
                if (data.content) {
                    this.streamingText = data.full_text || (this.streamingText + data.content);
                    this.renderStreamingContent();
                }
                break;

            case 'complete':
                this.finalizeMessage();
                break;

            case 'error':
                this.handleStreamError(data.error);
                break;

            default:
                if (data.content) {
                    this.streamingText += data.content;
                    this.renderStreamingContent();
                }
        }
    }

    // Debounced progressive markdown render during streaming
    renderStreamingContent() {
        if (this._renderDebounce) return;

        this._renderDebounce = setTimeout(() => {
            this._renderDebounce = null;
            this._doRenderStreaming();
        }, 40);
    }

    // Actually render the streaming content as partial markdown
    _doRenderStreaming() {
        if (!this.currentBotMessageElement) return;

        const $content = this.currentBotMessageElement.find('.streaming-content');
        if (!$content.length) return;

        if (window.markdownMathRenderer) {
            const html = markdownMathRenderer.renderPartial(this.streamingText);
            $content.html(html);
        } else {
            $content.text(this.streamingText);
        }

        this.scrollToBottom();
    }

    // Add user message to chat
    addUserMessage(message, hasImage = false, imageBase64 = null) {
        const $chatBox = $('#chat-box');
        const $messageDiv = $('<div>').addClass('message user-message');
        const $messageContent = $('<div>').addClass('message-content');

        if (hasImage && imageBase64) {
            const $imageElement = $('<img>')
                .attr('src', imageBase64)
                .attr('alt', 'Soal Matematika')
                .addClass('chat-image')
                .css({
                    'max-width': '100%',
                    'max-height': '300px',
                    'border-radius': '8px',
                    'margin': '10px 0',
                    'cursor': 'pointer'
                });

            $imageElement.on('click', function () {
                this.openImageModal(imageBase64);
            }.bind(this));

            $messageContent.append($imageElement);

            if (message) {
                const $messageText = $('<div>').addClass('message-text').text(message);
                $messageContent.append($messageText);
            } else {
                const $imageInfo = $('<div>')
                    .addClass('image-info')
                    .html('<small style="color: #666; font-style: italic;">📷 Gambar soal matematika</small>');
                $messageContent.append($imageInfo);
            }
        } else {
            let displayMessage = message;
            if (hasImage) {
                displayMessage = message ? `📷 ${message}` : '📷 Gambar soal matematika';
            }
            const $messageText = $('<div>').addClass('message-text').text(displayMessage);
            $messageContent.append($messageText);
        }

        $messageDiv.append($messageContent);
        $chatBox.append($messageDiv);
        this.scrollToBottom();
    }

    // Open image in modal for better viewing
    openImageModal(imageData) {
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
                .on('click', function () {
                    $(this).hide();
                });

            $('body').append($modal);
        }

        $modal.find('img').attr('src', imageData);
        $modal.show();
    }

    // Create bot message placeholder with streaming cursor
    createBotMessagePlaceholder() {
        const $chatBox = $('#chat-box');
        const $messageDiv = $('<div>').addClass('message bot-message streaming').attr('id', 'streaming-message');
        const $messageContent = $('<div>').addClass('message-content');

        $messageContent.html('<div class="streaming-content"></div><span class="streaming-cursor"></span>');
        $messageDiv.append($messageContent);
        $chatBox.append($messageDiv);
        this.scrollToBottom();
        return $('#streaming-message');
    }

    // Finalize message (remove streaming class, render full markdown + MathJax)
    finalizeMessage() {
        if (!this.currentBotMessageElement || this.hasError) return;

        // Cancel any pending render debounce
        if (this._renderDebounce) {
            clearTimeout(this._renderDebounce);
            this._renderDebounce = null;
        }

        this.currentBotMessageElement.removeClass('streaming');
        this.currentBotMessageElement.removeAttr('id');

        const finalText = this.streamingText;
        let formattedHtml = '';

        if (window.markdownMathRenderer) {
            formattedHtml = markdownMathRenderer.render(finalText);
        } else {
            formattedHtml = this.formatFallback(finalText);
        }

        const $messageContent = this.currentBotMessageElement.find('.message-content');
        $messageContent.html(`
            ${formattedHtml}
            <button class="copy-btn" title="Copy response">📋</button>
        `);

        // Setup code block copy buttons
        this.setupCodeBlockCopyButtons($messageContent);

        // Render math expressions
        if (window.markdownMathRenderer) {
            markdownMathRenderer.renderMath(this.currentBotMessageElement[0]);
        }

        // Setup copy button
        this.setupCopyButton(this.currentBotMessageElement);
    }

    // Setup code block copy buttons
    setupCodeBlockCopyButtons($container) {
        $container.find('.code-copy-btn').each(function () {
            $(this).on('click', async function (e) {
                e.preventDefault();
                e.stopPropagation();
                const codeText = $(this).closest('.code-block-wrapper').find('code').text();
                try {
                    await navigator.clipboard.writeText(codeText);
                    $(this).text('✅').css('background', '#10b981');
                    setTimeout(() => {
                        $(this).text('📋').css('background', '');
                    }, 2000);
                } catch (err) {
                    const textArea = document.createElement('textarea');
                    textArea.value = codeText;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                }
            });
        });
    }

    // Setup copy button functionality
    setupCopyButton(messageElement) {
        const copyBtn = messageElement.find('> .message-content > .copy-btn');
        const originalText = this.streamingText;

        copyBtn.on('click', async function (e) {
            e.preventDefault();
            e.stopPropagation();

            try {
                await navigator.clipboard.writeText(originalText);
                const btn = $(this);
                const originalBtnText = btn.text();
                btn.addClass('copied').text('Copied!').css('background', '#10b981');
                setTimeout(() => {
                    btn.removeClass('copied').text(originalBtnText).css('background', '');
                }, 2000);
            } catch (err) {
                const textArea = document.createElement('textarea');
                textArea.value = originalText;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);

                const btn = $(this);
                btn.text('Copied!').css('background', '#10b981');
                setTimeout(() => {
                    btn.text('📋').css('background', '');
                }, 2000);
            }
        });
    }

    // Fallback formatting when markdownMathRenderer is unavailable
    formatFallback(text) {
        if (!text) return '';
        return text
            .replace(/\n{3,}/g, '\n\n')
            .replace(/\r\n/g, '\n')
            .replace(/\r/g, '\n')
            .replace(/^### (.*?)$/gm, '<h3>$1</h3>')
            .replace(/^## (.*?)$/gm, '<h2>$1</h2>')
            .replace(/^# (.*?)$/gm, '<h1>$1</h1>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/^---$/gm, '<hr>')
            .replace(/^[-*] (.+)$/gm, '<li>$1</li>')
            .replace(/((?:<li>.*<\/li>\s*)+)/gs, '<ul>$1</ul>')
            .replace(/\n\n/g, '</p><p>')
            .replace(/^/, '<p>')
            .replace(/$/, '</p>')
            .replace(/\n/g, '<br>')
            .replace(/<p>\s*<\/p>/g, '')
            .trim();
    }

    // Handle stream errors — sets error flag to prevent finalizeMessage from overwriting
    handleStreamError(errorMessage) {
        this.hasError = true;

        if (this.currentBotMessageElement) {
            this.currentBotMessageElement.removeClass('streaming');
            this.currentBotMessageElement.removeAttr('id');
            const $messageContent = this.currentBotMessageElement.find('.message-content');
            $messageContent.html(
                `<span class="error-message">❌ Error: ${this.escapeHtml(errorMessage)}</span>`
            );
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

    scrollToBottom() {
        const chatBox = document.getElementById('chat-box');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }
}

// Export for use in main app
window.StreamingChat = StreamingChat;
