function initialize() {
    const updateElements = (dropdown) => {
        const selected = Array.from(dropdown.querySelectorAll('input:checked'), (input) => input.dataset.label);
        dropdown.querySelector('summary').textContent = selected.length ? selected.join(', ') : (globalThis.TYPO3?.lang?.['js.rteManager.choose.elements'] ?? 'Choose elements');
    };
    document.querySelectorAll('[data-rte-styles]').forEach((container) => {
        const list = container.querySelector('[data-rte-style-list]');
        const template = container.querySelector('[data-rte-style-template]');
        const addButton = container.querySelector('[data-rte-style-add]');
        let nextIndex = list.querySelectorAll('[data-rte-style-row]').length;
        list.querySelectorAll('[data-rte-elements]').forEach(updateElements);
        list.addEventListener('input', (event) => {
            if (!event.target.name?.endsWith('[title]')) return;
            event.target.closest('[data-rte-style-row]').querySelector('[data-rte-style-title]').textContent = event.target.value.trim() || (globalThis.TYPO3?.lang?.['js.rteManager.new.style'] ?? 'New style');
        });
        list.addEventListener('change', (event) => {
            const dropdown = event.target.closest('[data-rte-elements]');
            if (dropdown) updateElements(dropdown);
        });
        container.addEventListener('keydown', (event) => {
            const dropdown = event.target.closest('[data-rte-elements]');
            if (event.key === 'Escape' && dropdown) {
                dropdown.open = false;
                dropdown.querySelector('summary').focus();
            }
        });
        document.addEventListener('click', (event) => {
            list.querySelectorAll('[data-rte-elements][open]').forEach((dropdown) => {
                if (!dropdown.contains(event.target)) dropdown.open = false;
            });
        });

        addButton.addEventListener('click', () => {
            const fragment = template.content.cloneNode(true);
            fragment.querySelectorAll('*').forEach((element) => {
                ['id', 'name', 'for', 'aria-describedby', 'aria-labelledby'].forEach((attribute) => {
                    if (element.hasAttribute(attribute)) {
                        element.setAttribute(attribute, element.getAttribute(attribute).replaceAll('__INDEX__', String(nextIndex)));
                    }
                });
            });
            nextIndex++;
            list.append(fragment);
            list.lastElementChild.open = true;
            document.dispatchEvent(new Event('rte-style-added'));
            list.lastElementChild.querySelector('input').focus();
        });

        list.addEventListener('click', (event) => {
            const button = event.target.closest('[data-rte-style-remove]');
            if (button) {
                if (!window.confirm('Remove this style? Save the form to apply the removal.')) return;
                button.closest('[data-rte-style-row]').remove();
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
