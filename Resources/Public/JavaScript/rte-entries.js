function initialize() {
    const form = document.querySelector('[data-rte-previews]');
    if (!form) return;
    const value = (row, key) => row.querySelector(`[name$="[${key}]"]`)?.value.trim() ?? '';
    const update = (row) => {
        const type = row.dataset.rteEntry;
        const title = row.querySelector('[data-rte-entry-title]');
        const meta = row.querySelector('[data-rte-entry-meta]');
        const nameField = { color: 'label', font: 'family', size: 'name', icon: 'name', style: 'title' }[type];
        title.textContent = value(row, nameField) || globalThis.TYPO3?.lang?.[`js.rteManager.new.${type}`] || `New ${type}`;
        let details = [];
        if (type === 'color') details = [value(row, 'color')];
        if (type === 'size') details = [value(row, 'value')];
        if (type === 'font') details = ['weight', 'style'].map((key) => row.querySelector(`[name$="[${key}]"] option:checked`)?.textContent.trim());
        if (type === 'icon') details = ['SVG'];
        if (type === 'style') details = [
            [...row.querySelectorAll('[data-rte-elements] input:checked')].map((input) => input.dataset.label).join(', '),
            value(row, 'classes').split(/\s+/).filter(Boolean).map((name) => `.${name}`).join(' '),
        ];
        meta.textContent = details.filter(Boolean).join(' · ');
    };
    const refresh = () => form.querySelectorAll('[data-rte-entry]').forEach(update);
    form.addEventListener('input', (event) => {
        const row = event.target.closest('[data-rte-entry]');
        if (row) update(row);
    });
    form.addEventListener('change', (event) => {
        const row = event.target.closest('[data-rte-entry]');
        if (row) update(row);
    });
    // Open collapsed entries before the browser focuses an invalid field.
    form.addEventListener('invalid', (event) => {
        let parent = event.target.parentElement;
        while (parent && parent !== form) {
            if (parent.matches('details')) parent.open = true;
            parent = parent.parentElement;
        }
        const pane = event.target.closest('[role="tabpanel"]');
        if (pane) document.getElementById(pane.getAttribute('aria-labelledby'))?.click();
    }, true);
    new MutationObserver((records) => {
        if (records.some((record) => [...record.addedNodes].some((node) => node.nodeType === 1 && node.matches('[data-rte-entry]')))) refresh();
    }).observe(form, { childList: true, subtree: true });
    refresh();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
else initialize();
