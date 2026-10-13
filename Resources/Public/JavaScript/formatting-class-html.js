/** Translate only configured formatting, leaving unrelated classes and styles intact. */
export function createFormattingConverter(document, mappings) {
    const probe = document.createElement('span');
    const normalize = (property, value) => {
        probe.style.cssText = '';
        probe.style.setProperty(property, value);
        const canonical = probe.style.getPropertyValue(property);
        return property === 'font-family'
            ? canonical.replace(/["']/g, '').trim().toLowerCase()
            : canonical;
    };
    const entries = mappings.map((mapping) => ({
        ...mapping,
        canonical: normalize(mapping.property, mapping.value),
    }));
    const transform = (html, toClasses) => {
        const template = document.createElement('template');
        template.innerHTML = html;
        let changed = false;
        template.content.querySelectorAll('span').forEach((element) => {
            if (toClasses) {
                for (const property of ['color', 'font-family', 'font-size']) {
                    const value = element.style.getPropertyValue(property);
                    if (!value) continue;
                    const canonical = normalize(property, value);
                    const match = entries.find((entry) => entry.property === property && entry.canonical === canonical);
                    if (!match) continue; // Preserve legacy/pasted formatting without a configured equivalent.
                    for (const entry of entries.filter((entry) => entry.property === property)) {
                        element.classList.remove(entry.className);
                    }
                    element.classList.add(match.className);
                    element.style.removeProperty(property);
                    changed = true;
                }
            } else {
                for (const entry of entries) {
                    if (!element.classList.contains(entry.className)) continue;
                    // Feed the native font/color converters so selection, reset and undo keep working.
                    element.style.setProperty(entry.property, entry.value);
                    element.classList.remove(entry.className);
                    changed = true;
                }
            }
            if (element.hasAttribute('style') && !element.style.length) element.removeAttribute('style');
            if (element.hasAttribute('class') && !element.classList.length) element.removeAttribute('class');
        });
        return changed ? template.innerHTML : html;
    };
    return {
        toClasses: (html) => transform(html, true),
        toInline: (html) => transform(html, false),
    };
}
