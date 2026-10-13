function initialize() {
    document.querySelectorAll('[data-rte-colors]').forEach((container) => {
        const list = container.querySelector('[data-rte-color-list]');
        const template = container.querySelector('[data-rte-color-template]');
        const addButton = container.querySelector('[data-rte-color-add]');
        let nextIndex = list.querySelectorAll('[data-rte-color-row]').length;

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
            const button = event.target.closest('[data-rte-color-remove]');
            if (button) {
                if (!window.confirm('Remove this color? Save the form to apply the removal.')) return;
                button.closest('[data-rte-color-row]').remove();
                addButton.focus();
            }
        });

        const synchronize = (event) => {
            const row = event.target.closest('[data-rte-color-row], [data-rte-default-color]');
            if (!row) return;
            const picker = row.querySelector('[data-rte-color-picker]');
            const value = row.querySelector('[data-rte-color-value]');
            if (event.target === picker) {
                value.value = picker.value;
            } else if (event.target === value) {
                const hex = value.value.trim();
                if (/^#[a-f0-9]{6}$/i.test(hex)) {
                    picker.value = hex;
                } else if (/^#[a-f0-9]{3}$/i.test(hex)) {
                    picker.value = '#' + [...hex.slice(1)].map((character) => character.repeat(2)).join('');
                }
            }
        };
        list.addEventListener('input', synchronize);
        list.addEventListener('change', synchronize);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize, { once: true });
} else {
    initialize();
}
