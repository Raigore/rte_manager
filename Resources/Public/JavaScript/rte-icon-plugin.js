import { Plugin, Command } from '@ckeditor/ckeditor5-core';
import { Widget, toWidget } from '@ckeditor/ckeditor5-widget';
import { createDropdown, addToolbarToDropdown, ButtonView } from '@ckeditor/ckeditor5-ui';
import { Collection } from '@ckeditor/ckeditor5-utils';

const pickerIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2 2h7v7H2zm9 0h7v7h-7zM2 11h7v7H2zm12.5 0 4 7h-8z"/></svg>';

// Use the same passive shape subset as the server when reopening pasted/saved HTML.
export function cleanIconSvg(markup) {
    const parsed = new DOMParser().parseFromString(markup, 'image/svg+xml');
    const root = parsed.documentElement;
    if (root.localName !== 'svg' || parsed.querySelector('parsererror')) return '';
    const tags = new Set(['svg', 'g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon']);
    const attributes = new Set(['xmlns', 'viewBox', 'width', 'height', 'fill', 'fill-rule', 'clip-rule', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset', 'opacity', 'fill-opacity', 'stroke-opacity', 'transform', 'd', 'cx', 'cy', 'r', 'rx', 'ry', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'points']);
    for (const node of [root, ...root.querySelectorAll('*')]) {
        if (!tags.has(node.localName)) { node.remove(); continue; }
        for (const attr of [...node.attributes]) {
            if (!attributes.has(attr.name) || /url\s*\(/i.test(attr.value)) node.removeAttribute(attr.name);
        }
    }
    root.setAttribute('width', '1em');
    if (!root.hasAttribute('fill')) root.setAttribute('fill', 'currentColor');
    root.setAttribute('height', '1em');
    root.setAttribute('aria-hidden', 'true');
    root.setAttribute('focusable', 'false');
    return root.outerHTML;
}

class InsertIcon extends Command {
    refresh() {
        this.isEnabled = !!this.editor.model.schema.findAllowedParent(this.editor.model.document.selection.getFirstPosition(), 'rteIcon');
    }
    execute({ id }) {
        const icon = (this.editor.config.get('rteManagerIcons') ?? []).find((item) => item.id === id);
        if (!icon) return;
        this.editor.model.change((writer) => {
            const element = writer.createElement('rteIcon', { iconId: icon.id, svg: cleanIconSvg(icon.svg) });
            this.editor.model.insertObject(element, null, null, { setSelection: 'after' });
        });
    }
}

export class RteIcons extends Plugin {
    static get pluginName() { return 'RteManagerIcons'; }
    static get requires() { return [Widget]; }
    init() {
        const editor = this.editor;
        editor.model.schema.register('rteIcon', { inheritAllFrom: '$inlineObject', allowAttributes: ['iconId', 'svg', 'fontColor', 'fontSize'] });
        editor.data.processor.registerRawContentMatcher({ name: 'span', classes: 'rte-icon' });
        editor.conversion.for('upcast').elementToElement({
            view: { name: 'span', classes: 'rte-icon' },
            model: (element, { writer }) => writer.createElement('rteIcon', {
                iconId: element.getAttribute('data-rte-icon') ?? 'icon',
                svg: cleanIconSvg(element.getCustomProperty('$rawContent') ?? ''),
            }),
            converterPriority: 'high',
        });
        const render = (model, { writer }) => writer.createRawElement('span', {
            class: 'rte-icon', 'data-rte-icon': model.getAttribute('iconId'),
        }, (element) => { element.innerHTML = cleanIconSvg(model.getAttribute('svg') ?? ''); });
        editor.conversion.for('dataDowncast').elementToElement({ model: 'rteIcon', view: render });
        editor.conversion.for('editingDowncast').elementToElement({
            model: 'rteIcon', view: (model, { writer }) => {
                const content = writer.createRawElement('span', null, (element) => { element.innerHTML = cleanIconSvg(model.getAttribute('svg') ?? ''); });
                const container = writer.createContainerElement('span', { class: 'rte-icon' }, content);
                return toWidget(container, writer, { label: (globalThis.TYPO3?.lang?.['js.rteManager.inline.icon'] ?? 'Inline icon') });
            },
        });
        editor.commands.add('rteIcon', new InsertIcon(editor));
        editor.ui.componentFactory.add('rteIcon', (locale) => {
            const dropdown = createDropdown(locale);
            const label = globalThis.TYPO3?.lang?.['js.rteManager.insert.icon'] ?? 'Insert icon';
            dropdown.buttonView.set({ label, icon: pickerIcon, withText: false, tooltip: true });
            const items = new Collection();
            for (const icon of editor.config.get('rteManagerIcons') ?? []) {
                const button = new ButtonView(locale);
                button.set({ label: icon.name, icon: cleanIconSvg(icon.svg), withText: false, tooltip: true, iconId: icon.id });
                // Preserve SVG fills (including fill="none") instead of the toolbar theme's recoloring.
                button.iconView.isColorInherited = false;
                button.extendTemplate({ attributes: { style: { width: '40px', height: '40px', 'min-width': '40px', padding: '6px', margin: '2px 1px' } } });
                button.iconView.extendTemplate({ attributes: { style: { width: '28px', height: '28px' } } });
                items.add(button);
            }
            addToolbarToDropdown(dropdown, items, { ariaLabel: label, maxWidth: '264px' });
            dropdown.on('change:isOpen', () => {
                if (!dropdown.isOpen) return;
                const toolbar = dropdown.toolbarView;
                toolbar.element.style.maxHeight = '280px';
                toolbar.element.style.overflowY = 'auto';
                toolbar.itemsView.element.style.display = 'grid';
                toolbar.itemsView.element.style.gridTemplateColumns = 'repeat(6, 42px)';
            });
            dropdown.bind('isEnabled').to(editor.commands.get('rteIcon'), 'isEnabled', (enabled) => enabled && items.length > 0);
            this.listenTo(dropdown, 'execute', (event) => {
                editor.execute('rteIcon', { id: event.source.iconId });
                editor.editing.view.focus();
            });
            return dropdown;
        });
    }
}
