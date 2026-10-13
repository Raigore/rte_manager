function initialize() {
    const container = document.querySelector('[data-rte-icons]');
    if (!container) return;
    const list = container.querySelector('[data-rte-icon-list]');
    const template = container.querySelector('template');
    const add = container.querySelector('[data-rte-icon-add]');
    let index = list.children.length;
    add.addEventListener('click', () => {
        const fragment = template.content.cloneNode(true);
        fragment.querySelectorAll('*').forEach((element) => {
            for (const attr of ['id', 'name', 'for']) {
                if (element.hasAttribute(attr)) element.setAttribute(attr, element.getAttribute(attr).replaceAll('__INDEX__', String(index)));
            }
        });
        index++;
        list.append(fragment);
        list.lastElementChild.open = true;
        list.lastElementChild.querySelector('input').focus();
    });
    list.addEventListener('input', (event) => {
        if (event.target.name?.endsWith('[name]')) event.target.closest('details').querySelector('summary strong').textContent = event.target.value.trim() || (globalThis.TYPO3?.lang?.['js.rteManager.new.icon'] ?? 'New icon');
    });
    list.addEventListener('change', (event) => {
        if (!event.target.name?.endsWith('[svg]')) return;
        const parsed = new DOMParser().parseFromString(event.target.value, 'image/svg+xml');
        if (parsed.documentElement.localName !== 'svg' || parsed.querySelector('parsererror')) return;
        let changed = false;
        parsed.querySelectorAll('[stroke]').forEach((node) => {
            const stroke = node.getAttribute('stroke').trim();
            if (stroke.toLowerCase() !== 'none' && stroke !== 'currentColor') {
                node.setAttribute('stroke', 'currentColor');
                changed = true;
            }
        });
        if (changed) event.target.value = new XMLSerializer().serializeToString(parsed.documentElement);
    });
    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-rte-icon-remove]');
        if (button && window.confirm('Remove this icon from the picker? Existing content keeps its inserted SVG. Save to apply.')) {
            button.closest('details').remove();
            add.focus();
        }
    });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
else initialize();
