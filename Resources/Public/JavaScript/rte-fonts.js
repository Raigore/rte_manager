function initialize() {
    document.querySelectorAll('[data-rte-fonts]').forEach((container) => {
        const list = container.querySelector('[data-rte-font-list]');
        const template = container.querySelector('[data-rte-font-template]');
        const addButton = container.querySelector('[data-rte-font-add]');
        let nextIndex = list.querySelectorAll('[data-rte-font-row]').length;
        addButton.addEventListener('click', () => {
            const fragment = template.content.cloneNode(true);
            fragment.querySelectorAll('*').forEach((element) => {
                ['id', 'name', 'for'].forEach((attribute) => {
                    if (element.hasAttribute(attribute)) {
                        element.setAttribute(attribute, element.getAttribute(attribute).replaceAll('__INDEX__', String(nextIndex)));
                    }
                });
            });
            nextIndex++;
            list.append(fragment);
            list.lastElementChild.open = true;
            list.lastElementChild.querySelector('input').focus();
        });
        list.addEventListener('click', (event) => {
            const button = event.target.closest('[data-rte-font-remove]');
            if (button) {
                if (!window.confirm('Remove this font? Save the form to apply the removal.')) return;
                button.closest('[data-rte-font-row]').remove();
                addButton.focus();
            }
        });
        list.addEventListener('change', (event) => {
            if (event.target.type !== 'file') return;
            const file = event.target.files[0];
            event.target.setCustomValidity(file && file.size > 10485760 ? 'Select a font file of at most 10 MB.' : '');
            event.target.reportValidity();
        });
        container.closest('form').addEventListener('invalid', (event) => {
            if (container.contains(event.target)) {
                document.getElementById('rte-font-tab').click();
            }
        }, true);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize, { once: true });
} else {
    initialize();
}
