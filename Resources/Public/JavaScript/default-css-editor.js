import '@typo3/backend/code-editor/element/code-mirror-element.js';

const initialize = () => {
    document.querySelectorAll('textarea[data-default-css]').forEach((textarea) => {
        if (textarea.closest('typo3-t3editor-codemirror')) return;
        const editor = document.createElement('typo3-t3editor-codemirror');
        editor.mode = {
            name: '@codemirror/lang-css', exportName: 'css', flags: 2,
            items: [{ type: 'invoke', args: [] }],
        };
        editor.lineWrapping = true;
        editor.label = 'CSS';
        textarea.before(editor);
        editor.append(textarea);
    });
};
document.addEventListener('rte-style-added', initialize);
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize, { once: true });
} else {
    initialize();
}
