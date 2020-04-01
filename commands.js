import CodeEditor from './codeEditor';
import DynamicContent from './dynamicContent';

export default (editor, opts = {}) => {
    const cmd = editor.Commands;

    let codeEditor;
    let dynamicContent;

    // Launch Code Editor popup
    cmd.add('preset-mautic:code-edit', {
        run: (editor, sender, options = {}) => {
            if (!codeEditor) codeEditor = new CodeEditor(editor, opts);

            sender && sender.set('active', 0);
            codeEditor.showCodePopup()
        }
    });

    // Launch Dynamic Content popup
    cmd.add('preset-mautic:dynamic-content', {
        run: (editor, sender, options = {}) => {
            const { target } = options;
            const component = target || editor.getSelected();

            if (!dynamicContent) dynamicContent = new DynamicContent(editor, opts);

            dynamicContent.showCodePopup(component)
        }
    });
};
