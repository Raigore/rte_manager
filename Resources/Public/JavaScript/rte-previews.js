// Sandboxed documents keep draft CSS and SVG away from the backend UI.
const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
const field = (row, key) => row.querySelector(`[name$="[${key}]"]`)?.value.trim() ?? '';
const size = (value) => /^\d*\.?\d+$/.test(value) ? `${value}px` : value;
const color = (value) => /^#[a-f\d]{3}(?:[a-f\d]{3})?$/i.test(value) ? value : '';
const cssValue = (value) => /^[\w\s.%#(),-]*$/.test(value) ? value : '';
const tags = new Set(['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a', 'span', 'div', 'blockquote', 'pre', 'code', 'strong', 'em', 'ul', 'ol', 'li', 'table', 'tr', 'td', 'th']);

function additionalCss(value) {
    const css = value.replace(/\/\*[\s\S]*?\*\//g, '');
    // Match the server's no-import/no-URL policy, including escaped spellings.
    return /[<>@\\]|url\s*\(|expression\s*\(/i.test(css) ? '' : css;
}

function iconMarkup(value) {
    const parsed = new DOMParser().parseFromString(value, 'image/svg+xml');
    const root = parsed.documentElement;
    if (root.localName !== 'svg' || parsed.querySelector('parsererror')) return '';
    const allowed = new Set(['svg', 'g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon']);
    const attrs = new Set(['xmlns', 'viewBox', 'width', 'height', 'fill', 'fill-rule', 'clip-rule', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset', 'opacity', 'fill-opacity', 'stroke-opacity', 'transform', 'd', 'cx', 'cy', 'r', 'rx', 'ry', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'points']);
    for (const node of [root, ...root.querySelectorAll('*')]) {
        if (!allowed.has(node.localName)) { node.remove(); continue; }
        for (const attr of [...node.attributes]) {
            if (!attrs.has(attr.name) || /url\s*\(/i.test(attr.value)) node.removeAttribute(attr.name);
        }
    }
    root.setAttribute('width', '2em');
    root.setAttribute('height', '2em');
    return root.outerHTML;
}

function initialize() {
    const controls = document.querySelector('[data-rte-previews]');
    if (!controls) return;
    const form = controls.closest('form');
    const defaultText = controls.dataset.previewText || 'The quick brown fox jumps over the lazy dog. 0123456789';
    const previews = new Map();
    const samples = new WeakMap();
    const fonts = new Map();
    let timer;
    const schedule = () => { clearTimeout(timer); timer = setTimeout(render, 150); };
    const rows = (selector) => [...form.querySelectorAll(selector)];
    const defaultFontRow = () => form.querySelector('#rte-default-font-file')?.closest('.mb-3');
    const defaultRows = () => rows('#rte-defaults details');
    const fontRows = () => [defaultFontRow(), ...rows('[data-rte-font-row]')].filter(Boolean);
    const readData = (blob) => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(blob);
    });

    function fontData(row) {
        const upload = row.querySelector('input[type="file"]')?.files[0];
        const saved = field(row, 'file');
        if (row.querySelector('[name$="[clear]"]')?.checked) return null;
        if (!upload && !/^[a-f\d]{64}\.(woff2?|ttf|otf)$/.test(saved)) return null;
        if (upload && upload.size > 10485760) return null;
        const key = upload || saved;
        if (!fonts.has(key)) {
            const entry = { data: '', error: false, alias: `RtePreviewFont${fonts.size}` };
            fonts.set(key, entry);
            const source = upload ? Promise.resolve(upload) : fetch(new URL(saved, new URL(controls.dataset.fontBase, location.href))).then((response) => {
                if (!response.ok) throw new Error('Font unavailable');
                return response.blob();
            });
            source.then(readData).then((data) => { entry.data = data; schedule(); }).catch(() => { entry.error = true; schedule(); });
        }
        return fonts.get(key);
    }

    function render() {
        const colors = new Map(rows('[data-rte-color-row]').map((row) => [field(row, 'label'), color(field(row, 'color'))]));
        const sizes = new Map(rows('[data-rte-size-row]').map((row) => [field(row, 'name'), cssValue(size(field(row, 'value')))]));
        const families = new Map();
        let faces = '';
        fontRows().forEach((row) => {
            const entry = fontData(row);
            if (!entry?.data) return;
            const name = field(row, 'family') || 'RteDefaultFont';
            // Share an alias across variants while keeping user names out of CSS.
            const alias = families.get(name) || entry.alias;
            families.set(name, alias);
            faces += `@font-face{font-family:${alias};src:url("${entry.data}");font-weight:${cssValue(field(row, 'weight')) || '400'};font-style:${cssValue(field(row, 'style')) || 'normal'};}`;
        });
        const formatting = (row) => {
            let css = '';
            const family = families.get(field(row, 'font'));
            const selectedColor = colors.get(field(row, 'color'));
            const selectedSize = sizes.get(field(row, 'size'));
            if (family) css += `font-family:${family};`;
            if (selectedColor) css += `color:${selectedColor};`;
            if (selectedSize) css += `font-size:${selectedSize};`;
            return css + additionalCss(field(row, 'css'));
        };
        let base = `html{font-size:${cssValue(size(form.querySelector('#rte-default-size-html')?.value.trim() || '16px')) || '16px'};}body{margin:16px;color:${color(form.querySelector('#rte-default-color')?.value || '') || '#222'};font-family:${families.get('RteDefaultFont') || 'sans-serif'};line-height:1.5;overflow-wrap:anywhere;}`;
        for (const [name, value] of colors) {
            const slug = name.toLowerCase().replace(/[^\p{L}\p{N}_-]+/gu, '-').replace(/^-+|-+$/g, '');
            if (slug && value) base += `:root{--color-${slug}:${value};}`;
        }
        for (const row of defaultRows()) {
            const tag = row.querySelector('select')?.name.match(/\[elementDefaults\]\[([^\]]+)\]/)?.[1];
            if (tags.has(tag)) base += `${tag}{${formatting(row)}}`;
        }

        const entries = [
            ...rows('[data-rte-style-row]').map((row) => [row, 'style']),
            ...defaultRows().map((row) => [row, 'default']),
            ...fontRows().map((row) => [row, 'font']),
            ...rows('[data-rte-color-row], [data-rte-default-color]').map((row) => [row, 'color']),
            ...rows('[data-rte-size-row]').map((row) => [row, 'size']),
            ...rows('[name^="rte[defaultFontSizes]"]').map((input) => [input.parentElement, 'size']),
            ...rows('[data-rte-icon-list] > details').map((row) => [row, 'icon']),
        ];
        for (const [row, type] of entries) {
            if (!previews.has(row)) {
                const box = document.createElement('div');
                box.className = 'rte-preview';
                const label = document.createElement('div');
                label.className = 'form-label';
                label.textContent = (globalThis.TYPO3?.lang?.['js.rteManager.live.preview'] ?? 'Preview');
                const frame = document.createElement('iframe');
                frame.title = (globalThis.TYPO3?.lang?.['js.rteManager.preview'] ?? 'Preview');
                // Same-origin permits parent-side editing handlers; scripts stay disabled.
                frame.setAttribute('sandbox', 'allow-same-origin');
                frame.addEventListener('load', () => {
                    const doc = frame.contentDocument;
                    if (!doc) return;
                    doc.querySelectorAll('[data-sample]').forEach((editable) => {
                        editable.addEventListener('focus', () => frame.classList.add('is-editing'));
                        editable.addEventListener('blur', () => frame.classList.remove('is-editing'));
                        editable.addEventListener('input', () => {
                            const text = editable.textContent.slice(0, 300);
                            if (editable.textContent.length > 300) editable.textContent = text;
                            const values = samples.get(row) || {};
                            values[editable.dataset.sample] = text;
                            samples.set(row, values);
                        });
                    });
                    doc.addEventListener('click', (event) => {
                        if (event.target.closest('a')) event.preventDefault();
                    });
                });
                box.append(label, frame);
                row.append(box);
                previews.set(row, frame);
            }
            const frame = previews.get(row);
            let css = '';
            let message = '';
            let elements = ['p'];
            if (type === 'style') {
                elements = [...row.querySelectorAll('[data-rte-elements] input:checked')].flatMap((input) => /^(headings|h1-h6)$/.test(input.value) ? ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] : input.value.split(/[,\s]+/)).filter((tag) => tags.has(tag));
                if (!elements.length) elements = ['p'];
                css = formatting(row);
            } else if (type === 'default') {
                elements = [row.querySelector('select').name.match(/\[elementDefaults\]\[([^\]]+)\]/)[1]];
            } else if (type === 'font') {
                const entry = fontData(row);
                const family = families.get(field(row, 'family') || 'RteDefaultFont');
                css = `font-family:${family || 'sans-serif'};font-weight:${cssValue(field(row, 'weight').split(' ')[0]) || '400'};font-style:${cssValue(field(row, 'style')) || 'normal'};`;
                message = !entry ? (globalThis.TYPO3?.lang?.['js.rteManager.choose.a.font.file.to.preview.it'] ?? 'Choose a font file to preview it.') : entry.error ? (globalThis.TYPO3?.lang?.['js.rteManager.font.could.not.be.loaded'] ?? 'Font could not be loaded.') : !entry.data ? (globalThis.TYPO3?.lang?.['js.rteManager.loading.font'] ?? 'Loading font…') : '';
            } else if (type === 'color') {
                css = `color:${color(row.querySelector('[data-rte-color-value]').value) || 'inherit'};`;
            } else if (type === 'size') {
                const value = row.matches('[data-rte-size-row]') ? field(row, 'value') : row.querySelector('input')?.value.trim() || '';
                css = `font-size:${cssValue(size(value)) || 'inherit'};`;
            }
            const text = (key) => escapeHtml(samples.get(row)?.[key] ?? defaultText);
            const editable = (key) => `contenteditable="plaintext-only" role="textbox" aria-multiline="true" aria-label="${escapeHtml(frame.title)}" data-sample="${key}"`;
            let content = elements.map((tag) => `<${tag} class="sample" ${editable(tag)}>${text(tag)}</${tag}>`).join('');
            if (type === 'icon') {
                const svg = iconMarkup(field(row, 'svg'));
                content = svg ? `<div class="sample">${svg}</div>` : '<p>' + escapeHtml(globalThis.TYPO3?.lang?.['js.rteManager.paste.a.valid.svg.to.preview.it'] ?? 'Paste a valid SVG to preview it.') + '</p>';
            }
            // No scripts, navigation, forms or external resources in draft previews.
            const html = `<!doctype html><html><head><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'unsafe-inline'; font-src data:; img-src 'none'; base-uri 'none'; form-action 'none'"><style>${faces}${base}[contenteditable]{min-height:1.5em;cursor:text;white-space:pre-wrap;outline:none;}.sample{margin:0;${css}}.sample+.sample{margin-top:0.75em;}</style></head><body>${message ? `<p>${escapeHtml(message)}</p>` : ''}${content}</body></html>`;
            if (frame.srcdoc !== html) frame.srcdoc = html;
        }
        for (const [row] of previews) if (!row.isConnected) previews.delete(row);
    }
    form.addEventListener('input', schedule);
    form.addEventListener('change', schedule);
    // New and removed rows in every tab, without observing our own previews.
    new MutationObserver((records) => {
        if (records.some((record) => [...record.addedNodes, ...record.removedNodes].some((node) => node.nodeType === 1 && !node.matches('.rte-preview') && (node.matches('details, [data-rte-font-row], [data-rte-color-row], [data-rte-size-row]'))))) schedule();
    }).observe(form, { childList: true, subtree: true });
    render();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
else initialize();
