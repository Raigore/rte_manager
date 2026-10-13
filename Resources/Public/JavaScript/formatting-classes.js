import { Plugin } from '@ckeditor/ckeditor5-core';
import { createFormattingConverter } from './formatting-class-html.js';

export class FormattingClasses extends Plugin {
    static get pluginName() {
        return 'RteManagerFormattingClasses';
    }

    afterInit() {
        // Only the editing view needs this correction: rem otherwise uses the backend root.
        const rootSize = this.editor.config.get('rteManagerRootSize');
        if (rootSize) {
            let base = rootSize;
            const relative = /^(\d*\.?\d+)(rem|em|%)$/.exec(base);
            if (relative) {
                base = `${16 * Number(relative[1]) / (relative[2] === '%' ? 100 : 1)}px`;
            }
            this.editor.editing.view.change((writer) => {
                for (const root of this.editor.editing.view.document.roots) {
                    writer.setStyle('--rte-managed-rem', base, root);
                }
            });
            if (this.editor.plugins.has('FontSizeEditing')) {
                this.editor.conversion.for('editingDowncast').attributeToElement({
                    model: 'fontSize',
                    view: (value, { writer }) => {
                        const rem = /^(\d*\.?\d+)rem$/.exec(value);
                        if (!rem) return null;
                        return writer.createAttributeElement('span', {
                            style: `font-size:calc(${base} * ${Number(rem[1])})`,
                        }, { priority: 7 });
                    },
                    converterPriority: 'high',
                });
            }
        }
        const converter = createFormattingConverter(document, this.editor.config.get('rteManagerClasses') ?? []);
        const processor = this.editor.data.processor;
        const toData = processor.toData.bind(processor);
        const toView = processor.toView.bind(processor);
        // Keep native editor behavior, but serialize configured fonts and colors as classes.
        processor.toData = (fragment) => converter.toClasses(toData(fragment));
        processor.toView = (html) => toView(converter.toInline(html));
    }
}
