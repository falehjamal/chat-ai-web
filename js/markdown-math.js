// Enhanced Markdown and Math Renderer for all modes
class MarkdownMathRenderer {
    constructor() {
        // Configure marked.js if available (loaded but used as reference)
        if (window.marked) {
            marked.setOptions({
                breaks: true,
                gfm: true,
                headerIds: false,
                mangle: false
            });
        }
        this._renderTimer = null;
    }

    // Main function - full render (used after streaming completes)
    render(text) {
        if (!text) return '';

        let processed = this.cleanText(text);
        processed = this.processMarkdownEnhanced(processed);
        processed = this.applyCustomFormatting(processed);

        return processed;
    }

    // Partial render for use DURING streaming (lightweight, no custom formatting)
    renderPartial(text) {
        if (!text) return '';

        let processed = this.cleanText(text);
        processed = this.processMarkdownEnhanced(processed);

        return processed;
    }

    // Clean and normalize text from backend
    cleanText(text) {
        let cleaned = text.trim();

        // Normalize line breaks
        cleaned = cleaned.replace(/\r\n/g, '\n');
        cleaned = cleaned.replace(/\r/g, '\n');

        // Reduce excessive line breaks (max 2)
        cleaned = cleaned.replace(/\n{3,}/g, '\n\n');

        // Remove trailing spaces at end of lines
        cleaned = cleaned.replace(/ +$/gm, '');

        return cleaned;
    }

    // Enhanced markdown processing with math and code block protection
    processMarkdownEnhanced(text) {
        let processed = text;

        // === Step 1: Protect special blocks with placeholders ===
        const placeholders = [];
        let placeholderIndex = 0;

        const addPlaceholder = (match, type) => {
            const placeholder = `__PLACEHOLDER_${type}_${placeholderIndex}__`;
            placeholders[placeholderIndex] = { placeholder, content: match, type };
            placeholderIndex++;
            return placeholder;
        };

        // Protect fenced code blocks FIRST (``` ... ```)
        processed = processed.replace(/```(\w*)\n([\s\S]*?)```/g, (match, lang, code) => {
            const escapedCode = code
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
            const langClass = lang ? ` class="language-${lang}"` : '';
            const langLabel = lang ? `<span class="code-lang">${lang}</span>` : '';
            const html = `<div class="code-block-wrapper">${langLabel}<button class="code-copy-btn" title="Copy code">📋</button><pre><code${langClass}>${escapedCode}</code></pre></div>`;
            return addPlaceholder(html, 'CODEBLOCK');
        });

        // Protect display math \[...\] and $$...$$
        processed = processed.replace(/\\\[([\s\S]*?)\\\]/g, (match) => addPlaceholder(match, 'MATHD'));
        processed = processed.replace(/\$\$([\s\S]*?)\$\$/g, (match) => addPlaceholder(match, 'MATHD'));

        // Protect inline math \(...\) and $...$
        processed = processed.replace(/\\\(([\s\S]*?)\\\)/g, (match) => addPlaceholder(match, 'MATHI'));
        processed = processed.replace(/\$([^$\n]+?)\$/g, (match) => addPlaceholder(match, 'MATHI'));

        // Protect inline code `code`
        processed = processed.replace(/`([^`]+?)`/g, (match, code) => {
            const html = `<code>${code.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</code>`;
            return addPlaceholder(html, 'ICODE');
        });

        // === Step 2: Convert markdown syntax to HTML ===

        // Headers
        processed = processed.replace(/^#### (.*?)$/gm, '<h4>$1</h4>');
        processed = processed.replace(/^### (.*?)$/gm, '<h3>$1</h3>');
        processed = processed.replace(/^## (.*?)$/gm, '<h2>$1</h2>');
        processed = processed.replace(/^# (.*?)$/gm, '<h1>$1</h1>');

        // Bold
        processed = processed.replace(/\*\*([^*\n]+?)\*\*/g, '<strong>$1</strong>');

        // Italic
        processed = processed.replace(/(?<!\*)\*([^*\n]+?)\*(?!\*)/g, '<em>$1</em>');

        // Strikethrough
        processed = processed.replace(/~~([^~\n]+?)~~/g, '<del>$1</del>');

        // Horizontal rules
        processed = processed.replace(/^---+$/gm, '<hr>');
        processed = processed.replace(/^\*\*\*+$/gm, '<hr>');
        processed = processed.replace(/^___+$/gm, '<hr>');

        // Links [text](url)
        processed = processed.replace(/\[([^\]]+?)\]\(([^)]+?)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');

        // Tables (GFM style)
        processed = this.processTables(processed);

        // Blockquotes
        processed = processed.replace(/^> (.+)$/gm, '<blockquote>$1</blockquote>');
        // Merge consecutive blockquotes
        processed = processed.replace(/<\/blockquote>\n<blockquote>/g, '<br>');

        // Bullet lists (- or * or •)
        processed = processed.replace(/^[-*•] (.+)$/gm, '<li>$1</li>');
        // Group consecutive <li> into <ul>
        processed = processed.replace(/((?:<li>.*?<\/li>\s*)+)/gs, '<ul>$1</ul>');

        // Numbered lists
        processed = processed.replace(/^\d+\. (.+)$/gm, '<ol-item>$1</ol-item>');
        processed = processed.replace(/((?:<ol-item>.*?<\/ol-item>\s*)+)/gs, (match) => {
            const items = match.replace(/<ol-item>(.*?)<\/ol-item>/g, '<li>$1</li>');
            return '<ol>' + items + '</ol>';
        });

        // === Step 3: Paragraphs ===
        const paragraphs = processed.split(/\n\s*\n/);
        processed = paragraphs.map(paragraph => {
            paragraph = paragraph.trim();
            if (!paragraph) return '';
            // Don't wrap block-level elements in <p>
            if (paragraph.match(/^<(h[1-6]|hr|ul|ol|blockquote|div|pre|table)/)) {
                return paragraph;
            }
            // Don't wrap code block placeholders
            if (paragraph.match(/__PLACEHOLDER_CODEBLOCK_\d+__/)) {
                return paragraph;
            }
            return `<p>${paragraph.replace(/\n/g, '<br>')}</p>`;
        }).filter(p => p).join('\n');

        // === Step 4: Restore all placeholders ===
        for (let i = 0; i < placeholders.length; i++) {
            const { placeholder, content, type } = placeholders[i];
            // For code blocks and inline code, content is already HTML
            const restoreValue = (type === 'CODEBLOCK' || type === 'ICODE') ? content : content;
            processed = processed.replace(placeholder, restoreValue);
        }

        return processed;
    }

    // Process GFM-style tables
    processTables(text) {
        const tableRegex = /^(\|.+\|)\n(\|[-:\| ]+\|)\n((?:\|.+\|\n?)+)/gm;

        return text.replace(tableRegex, (match, headerRow, separatorRow, bodyRows) => {
            const alignments = separatorRow.split('|').filter(c => c.trim()).map(cell => {
                cell = cell.trim();
                if (cell.startsWith(':') && cell.endsWith(':')) return 'center';
                if (cell.endsWith(':')) return 'right';
                return 'left';
            });

            const headers = headerRow.split('|').filter(c => c.trim()).map(c => c.trim());
            let html = '<table><thead><tr>';
            headers.forEach((h, i) => {
                const align = alignments[i] ? ` style="text-align:${alignments[i]}"` : '';
                html += `<th${align}>${h}</th>`;
            });
            html += '</tr></thead><tbody>';

            const rows = bodyRows.trim().split('\n');
            rows.forEach(row => {
                const cells = row.split('|').filter(c => c.trim()).map(c => c.trim());
                html += '<tr>';
                cells.forEach((cell, i) => {
                    const align = alignments[i] ? ` style="text-align:${alignments[i]}"` : '';
                    html += `<td${align}>${cell}</td>`;
                });
                html += '</tr>';
            });
            html += '</tbody></table>';
            return html;
        });
    }

    // Apply custom formatting for specific patterns (math/exam specific)
    applyCustomFormatting(html) {
        // Format boxed answers (\\boxed{...} and \boxed{...})
        html = html.replace(/\\\\boxed\{([^}]+)\}/g, '<span class="answer-box">$1</span>');
        html = html.replace(/\\boxed\{([^}]+)\}/g, '<span class="answer-box">$1</span>');

        // Format mathematical choice options (A. B. C. D. E.) — only at line start
        html = html.replace(/^([A-E])\.\s+(.+)$/gm, '<div class="choice-option"><strong>$1.</strong> $2</div>');

        // Format solution step markers
        html = html.replace(/^(Langkah \d+[:\.].*?)$/gm, '<div class="solution-step"><strong>$1</strong></div>');
        html = html.replace(/^(Step \d+[:\.].*?)$/gm, '<div class="solution-step"><strong>$1</strong></div>');
        html = html.replace(/^(Tahap \d+[:\.].*?)$/gm, '<div class="solution-step"><strong>$1</strong></div>');

        // Format "Jadi" / "Therefore" conclusions
        html = html.replace(/^(Jadi[,:]?\s*.*)$/gmi, '<div class="conclusion"><strong>$1</strong></div>');
        html = html.replace(/^(Therefore[,:]?\s*.*)$/gmi, '<div class="conclusion"><strong>$1</strong></div>');
        html = html.replace(/^(Sehingga[,:]?\s*.*)$/gmi, '<div class="conclusion"><strong>$1</strong></div>');

        // Clean up empty paragraphs
        html = html.replace(/<p>\s*<\/p>/g, '');

        return html.trim();
    }

    // Render math after DOM update
    renderMath(element) {
        if (window.MathJax && window.MathJax.typesetPromise) {
            MathJax.typesetPromise([element]).catch((err) => {
                console.warn('MathJax rendering error:', err);
            });
        } else {
            setTimeout(() => {
                if (window.MathJax && window.MathJax.typesetPromise) {
                    MathJax.typesetPromise([element]).catch(() => {});
                }
            }, 1000);
        }
    }
}

// Initialize the renderer
window.markdownMathRenderer = new MarkdownMathRenderer();
