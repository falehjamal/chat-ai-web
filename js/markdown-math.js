class MarkdownMathRenderer {
    constructor() {
        this.mathQueue = Promise.resolve();

        if (window.marked) {
            marked.setOptions({
                breaks: true,
                gfm: true,
                headerIds: false,
                mangle: false
            });
        }
    }

    render(text) {
        return this.renderInternal(text, false);
    }

    renderPartial(text) {
        return this.renderInternal(text, true);
    }

    renderInternal(text, isPartial) {
        if (!text) {
            return '';
        }

        const normalized = this.cleanText(text);
        const bundle = this.extractPlaceholders(normalized, isPartial);
        const markdown = this.escapeForMarkdown(bundle.text);
        let html = this.parseMarkdown(markdown);
        html = this.unwrapBlockPlaceholders(html, bundle.placeholders);
        html = this.restorePlaceholders(html, bundle.placeholders);
        html = this.decorateHtml(html, isPartial);

        return html.trim();
    }

    cleanText(text) {
        let cleaned = String(text).trim();
        cleaned = cleaned.replace(/\r\n/g, '\n');
        cleaned = cleaned.replace(/\r/g, '\n');
        cleaned = cleaned.replace(/\u00A0/g, ' ');
        cleaned = cleaned.replace(/[ \t]+$/gm, '');
        cleaned = cleaned.replace(/\n{3,}/g, '\n\n');
        return cleaned;
    }

    extractPlaceholders(text, isPartial) {
        const placeholders = [];
        let index = 0;
        let processed = text;

        const store = (content, kind, isBlock) => {
            const token = `@@PLACEHOLDER_${kind}_${index}@@`;
            placeholders.push({ token, content, isBlock });
            index += 1;
            return token;
        };

        processed = processed.replace(/```([^\n`]*)\n([\s\S]*?)```/g, (_match, language, code) => {
            return store(this.renderCodeBlock(language, code), 'CODEBLOCK', true);
        });

        if (isPartial) {
            processed = processed.replace(/```([^\n`]*)\n?([\s\S]*)$/, (_match, language, code) => {
                return store(this.renderCodeBlock(language, code), 'CODEBLOCK', true);
            });
        }

        processed = processed.replace(/\\\[([\s\S]*?)\\\]/g, (match) => {
            return store(`<div class="math-block">${match}</div>`, 'DISPLAY_MATH', true);
        });

        processed = processed.replace(/\$\$([\s\S]*?)\$\$/g, (match) => {
            return store(`<div class="math-block">${match}</div>`, 'DISPLAY_MATH', true);
        });

        processed = processed.replace(/`([^`\n]+?)`/g, (_match, code) => {
            return store(this.renderInlineCode(code), 'INLINE_CODE', false);
        });

        processed = processed.replace(/\\\(([\s\S]*?)\\\)/g, (match) => {
            return store(`<span class="math-inline">${match}</span>`, 'INLINE_MATH', false);
        });

        processed = processed.replace(/(?<!\$)\$(?!\s)([^$\n]+?)(?<!\s)\$(?!\$)/g, (match) => {
            return store(`<span class="math-inline">${match}</span>`, 'INLINE_MATH', false);
        });

        return {
            text: processed,
            placeholders
        };
    }

    parseMarkdown(markdown) {
        if (window.marked && typeof marked.parse === 'function') {
            const parsed = marked.parse(markdown);
            return this.decorateLinks(parsed);
        }

        return this.basicFallback(markdown);
    }

    unwrapBlockPlaceholders(html, placeholders) {
        let unwrapped = html;

        placeholders.forEach((placeholder) => {
            if (!placeholder.isBlock) {
                return;
            }

            const token = this.escapeRegExp(placeholder.token);
            unwrapped = unwrapped.replace(new RegExp(`<p>\\s*${token}\\s*</p>`, 'g'), placeholder.token);
        });

        return unwrapped;
    }

    restorePlaceholders(html, placeholders) {
        let restored = html;

        placeholders.forEach((placeholder) => {
            restored = restored.split(placeholder.token).join(placeholder.content);
        });

        return restored;
    }

    decorateHtml(html, isPartial) {
        let output = html;

        output = output.replace(/<p>\s*<\/p>/g, '');
        output = output.replace(/<table>/g, '<div class="table-scroll"><table>');
        output = output.replace(/<\/table>/g, '</table></div>');

        if (!isPartial) {
            output = output.replace(
                /<p>(Jadi\b[\s\S]*?)<\/p>/gi,
                '<p class="response-callout">$1</p>'
            );
            output = output.replace(
                /<p>(Kesimpulan\b[\s\S]*?)<\/p>/gi,
                '<p class="response-callout">$1</p>'
            );
        }

        return output;
    }

    renderCodeBlock(language, code) {
        const normalizedCode = String(code).replace(/\n$/, '');
        const escapedCode = this.escapeHtml(normalizedCode);
        const safeLanguage = this.escapeHtml((language || '').trim());
        const languageClass = safeLanguage ? ` class="language-${safeLanguage}"` : '';
        const label = safeLanguage || 'text';

        return `
<div class="code-block-wrapper">
    <div class="code-block-header">
        <span class="code-lang">${label}</span>
        <button class="code-copy-btn" type="button" title="Copy code">Copy</button>
    </div>
    <pre><code${languageClass}>${escapedCode}</code></pre>
</div>`.trim();
    }

    renderInlineCode(code) {
        return `<code>${this.escapeHtml(code)}</code>`;
    }

    basicFallback(markdown) {
        return markdown
            .split(/\n\s*\n/)
            .map((block) => `<p>${block.replace(/\n/g, '<br>')}</p>`)
            .join('\n');
    }

    sanitizeUrl(url) {
        const value = String(url || '').trim();
        if (!value) {
            return '#';
        }

        if (/^(https?:|mailto:|tel:|\/|#)/i.test(value)) {
            return this.escapeHtml(value);
        }

        return '#';
    }

    decorateLinks(html) {
        return String(html).replace(/<a\s+([^>]*?)href=(['"])(.*?)\2([^>]*)>/gi, (_match, before, quote, href, after) => {
            const safeHref = this.sanitizeUrl(href);
            const attrs = `${before || ''}${after || ''}`;
            const cleanedAttrs = attrs
                .replace(/\s*target=(['"]).*?\1/gi, '')
                .replace(/\s*rel=(['"]).*?\1/gi, '')
                .trim();
            const extraAttrs = cleanedAttrs ? ` ${cleanedAttrs}` : '';

            return `<a href="${safeHref}" target="_blank" rel="noopener noreferrer"${extraAttrs}>`;
        });
    }

    escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Escape only HTML-structural characters before markdown parsing.
    // Keeps `>` intact so Markdown blockquotes are recognized; escaping `<`
    // still prevents raw HTML tags from forming, preserving XSS safety.
    escapeForMarkdown(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;');
    }

    escapeRegExp(text) {
        return String(text).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    renderMath(element) {
        if (!window.MathJax || !window.MathJax.typesetPromise) {
            return;
        }

        this.mathQueue = this.mathQueue
            .then(() => {
                if (window.MathJax.typesetClear) {
                    MathJax.typesetClear([element]);
                }

                return MathJax.typesetPromise([element]);
            })
            .catch((error) => {
                console.warn('MathJax rendering error:', error);
            });
    }
}

window.markdownMathRenderer = new MarkdownMathRenderer();
