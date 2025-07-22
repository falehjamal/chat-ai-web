// Enhanced Markdown and Math Renderer for all modes - ALL FORMATTING IN FRONTEND
class MarkdownMathRenderer {
    constructor() {
        // Configure marked.js for markdown parsing
        marked.setOptions({
            breaks: true,
            gfm: true,
            headerIds: false,
            mangle: false
        });
    }

    // Main function to render markdown with math - handles ALL formatting
    render(text) {
        if (!text) return '';
        
        console.log('🎨 Frontend processing ALL formatting for text');
        
        // Step 1: Clean and normalize text
        let processed = this.cleanText(text);
        
        // Step 2: Enhanced markdown processing
        processed = this.processMarkdownEnhanced(processed);
        
        // Step 3: Apply custom formatting
        processed = this.applyCustomFormatting(processed);
        
        console.log('📄 Final HTML content preview:', processed.substring(0, 200) + '...');
        
        return processed;
    }

    // Clean and normalize text from backend
    cleanText(text) {
        let cleaned = text;
        
        // Remove any leftover asterisks that aren't part of markdown
        cleaned = cleaned.trim();
        
        // Normalize line breaks and reduce excessive line breaks
        cleaned = cleaned.replace(/\r\n/g, '\n');
        cleaned = cleaned.replace(/\r/g, '\n');
        
        // Reduce multiple consecutive line breaks to maximum 2
        cleaned = cleaned.replace(/\n{3,}/g, '\n\n');
        
        // Clean up multiple spaces
        cleaned = cleaned.replace(/ {2,}/g, ' ');
        
        // Remove trailing spaces at end of lines
        cleaned = cleaned.replace(/ +$/gm, '');
        
        return cleaned;
    }

    // Enhanced markdown processing while being careful with math expressions
    processMarkdownEnhanced(text) {
        let processed = text;
        
        // First, protect math expressions by replacing them with placeholders
        const mathPlaceholders = [];
        let mathIndex = 0;
        
        // Protect display math \[...\] and $$...$$
        processed = processed.replace(/\\\[([\s\S]*?)\\\]/g, (match) => {
            const placeholder = `__MATH_DISPLAY_${mathIndex}__`;
            mathPlaceholders[mathIndex] = match;
            mathIndex++;
            return placeholder;
        });
        
        processed = processed.replace(/\$\$([\s\S]*?)\$\$/g, (match) => {
            const placeholder = `__MATH_DISPLAY_${mathIndex}__`;
            mathPlaceholders[mathIndex] = match;
            mathIndex++;
            return placeholder;
        });
        
        // Protect inline math \(...\) and $...$
        processed = processed.replace(/\\\(([\s\S]*?)\\\)/g, (match) => {
            const placeholder = `__MATH_INLINE_${mathIndex}__`;
            mathPlaceholders[mathIndex] = match;
            mathIndex++;
            return placeholder;
        });
        
        processed = processed.replace(/\$([^$\n]+?)\$/g, (match) => {
            const placeholder = `__MATH_INLINE_${mathIndex}__`;
            mathPlaceholders[mathIndex] = match;
            mathIndex++;
            return placeholder;
        });
        
        // Convert headers (multiple levels)
        processed = processed.replace(/^#### (.*?)$/gm, '<h4>$1</h4>');
        processed = processed.replace(/^### (.*?)$/gm, '<h3>$1</h3>');
        processed = processed.replace(/^## (.*?)$/gm, '<h2>$1</h2>');
        processed = processed.replace(/^# (.*?)$/gm, '<h1>$1</h1>');
        
        // Convert bold text **text** (now safe from math)
        processed = processed.replace(/\*\*([^*\n]+?)\*\*/g, '<strong>$1</strong>');
        
        // Convert italic text *text* (now safe from math)  
        processed = processed.replace(/(?<!\*)\*([^*\n]+?)\*(?!\*)/g, '<em>$1</em>');
        
        // Convert code `code`
        processed = processed.replace(/`([^`]+?)`/g, '<code>$1</code>');
        
        // Convert horizontal rules
        processed = processed.replace(/^---+$/gm, '<hr>');
        processed = processed.replace(/^\*\*\*+$/gm, '<hr>');
        processed = processed.replace(/^___+$/gm, '<hr>');
        
        // Convert lists (both - and * bullets)
        processed = processed.replace(/^[-*•] (.+)$/gm, '<li>$1</li>');
        
        // Group consecutive list items into UL
        processed = processed.replace(/(<li>.*?<\/li>\s*(?:<li>.*?<\/li>\s*)*)/gs, '<ul>$1</ul>');
        
        // Convert numbered lists
        processed = processed.replace(/^\d+\. (.+)$/gm, '<ol-item>$1</ol-item>');
        processed = processed.replace(/(<ol-item>.*?<\/ol-item>\s*(?:<ol-item>.*?<\/ol-item>\s*)*)/gs, (match) => {
            const items = match.replace(/<ol-item>(.*?)<\/ol-item>/g, '<li>$1</li>');
            return '<ol>' + items + '</ol>';
        });
        
        // Convert blockquotes
        processed = processed.replace(/^> (.+)$/gm, '<blockquote>$1</blockquote>');
        
        // Convert line breaks to paragraphs more carefully
        const paragraphs = processed.split(/\n\s*\n/);
        processed = paragraphs.map(paragraph => {
            paragraph = paragraph.trim();
            if (paragraph) {
                // Check if it's already HTML element
                if (paragraph.match(/^<(h[1-6]|hr|ul|ol|blockquote)/)) {
                    return paragraph;
                } else {
                    // Convert single line breaks to <br> within paragraphs
                    return `<p>${paragraph.replace(/\n/g, '<br>')}</p>`;
                }
            }
            return '';
        }).filter(p => p).join('\n'); // Filter out empty paragraphs and use single \n
        
        // Restore math expressions
        for (let i = 0; i < mathPlaceholders.length; i++) {
            processed = processed.replace(`__MATH_DISPLAY_${i}__`, mathPlaceholders[i]);
            processed = processed.replace(`__MATH_INLINE_${i}__`, mathPlaceholders[i]);
        }
        
        return processed;
    }

    // Apply custom formatting for specific patterns - ALL REGEX PROCESSING HERE
    applyCustomFormatting(html) {
        // Format boxed answers (\\boxed{...} and \boxed{...})
        html = html.replace(/\\\\boxed\{([^}]+)\}/g, '<span class="answer-box">$1</span>');
        html = html.replace(/\\boxed\{([^}]+)\}/g, '<span class="answer-box">$1</span>');
        
        // Format mathematical choice options (A. B. C. D.)
        html = html.replace(/^([A-E])\.\s*(.+)$/gm, '<div class="choice-option"><strong>$1.</strong> $2</div>');
        
        // Format solution steps markers (more comprehensive)
        html = html.replace(/^(Langkah \d+[:\.].*?)$/gm, '<div class="solution-step"><strong>$1</strong></div>');
        html = html.replace(/^(Step \d+[:\.].*?)$/gm, '<div class="solution-step"><strong>$1</strong></div>');
        html = html.replace(/^(Tahap \d+[:\.].*?)$/gm, '<div class="solution-step"><strong>$1</strong></div>');
        
        // Format "Jawaban:" or "Answer:" labels (more flexible)
        html = html.replace(/^(\*?\*?Jawaban\*?\*?[:\.]?)(.*)$/gmi, '<div class="answer-section"><strong>Jawaban</strong>$2</div>');
        html = html.replace(/^(\*?\*?Answer\*?\*?[:\.]?)(.*)$/gmi, '<div class="answer-section"><strong>Answer</strong>$2</div>');
        html = html.replace(/^(\*?\*?Final Answer\*?\*?[:\.]?)(.*)$/gmi, '<div class="answer-section"><strong>Final Answer</strong>$2</div>');
        
        // Format "Penyelesaian:" or "Solution:" labels (more flexible)
        html = html.replace(/^(\*?\*?Penyelesaian\*?\*?[:\.]?)(.*)$/gmi, '<div class="solution-section"><strong>Penyelesaian</strong>$2</div>');
        html = html.replace(/^(\*?\*?Solution\*?\*?[:\.]?)(.*)$/gmi, '<div class="solution-section"><strong>Solution</strong>$2</div>');
        html = html.replace(/^(\*?\*?Solusi\*?\*?[:\.]?)(.*)$/gmi, '<div class="solution-section"><strong>Solusi</strong>$2</div>');
        
        // Format mathematical equations and expressions
        html = html.replace(/^(.*=.*[0-9]+.*)$/gm, '<div class="math-equation">$1</div>');
        
        // Format "Therefore" and "Jadi" conclusions
        html = html.replace(/^(Jadi[,:]?\s*.*)$/gmi, '<div class="conclusion"><strong>$1</strong></div>');
        html = html.replace(/^(Therefore[,:]?\s*.*)$/gmi, '<div class="conclusion"><strong>$1</strong></div>');
        html = html.replace(/^(Sehingga[,:]?\s*.*)$/gmi, '<div class="conclusion"><strong>$1</strong></div>');
        
        // FINAL CLEANUP - Remove excessive line breaks and spaces
        html = html.replace(/\n{2,}/g, '\n'); // Reduce multiple line breaks to single
        html = html.replace(/>\s+</g, '><'); // Remove spaces between HTML tags
        html = html.replace(/^\s+|\s+$/gm, ''); // Remove leading/trailing spaces from lines
        
        // Clean up empty paragraphs
        html = html.replace(/<p>\s*<\/p>/g, '');
        html = html.replace(/<p><\/p>/g, '');
        
        return html.trim();
    }

    // Render math after DOM update
    renderMath(element) {
        if (window.MathJax && window.MathJax.typesetPromise) {
            console.log('🧮 Rendering math with MathJax');
            MathJax.typesetPromise([element]).then(() => {
                console.log('✅ MathJax rendering completed');
            }).catch((err) => {
                console.error('❌ MathJax rendering error:', err);
            });
        } else {
            console.warn('⚠️ MathJax not available or not ready');
            // Retry after a short delay
            setTimeout(() => {
                if (window.MathJax && window.MathJax.typesetPromise) {
                    MathJax.typesetPromise([element]);
                }
            }, 1000);
        }
    }
}

// Initialize the renderer
window.markdownMathRenderer = new MarkdownMathRenderer();

console.log('📝 Simplified Markdown Math Renderer initialized');
