function initialize() {
    document.querySelectorAll('[data-rte-sizes]').forEach((container) => {
        const list = container.querySelector('[data-rte-size-list]');
        const template = container.querySelector('[data-rte-size-template]');
        const addButton = container.querySelector('[data-rte-size-add]');
        let nextIndex = list.querySelectorAll('[data-rte-size-row]').length;
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
            const button = event.target.closest('[data-rte-size-remove]');
            if (button) {
                if (!window.confirm('Remove this size? Save the form to apply the removal.')) return;
                button.closest('[data-rte-size-row]').remove();
                addButton.focus();
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize, { once: true });
} else {
    initialize();
}
