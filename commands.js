import CodeEditor from './codeEditor';

export default (editor, opts = {}) => {
    const cmd = editor.Commands;

    let codeEditor = null;

    // Launch Code Editor popup
    cmd.add('preset-mautic:code-edit', {
        run: (editor, sender, options = {}) => {
            if (!codeEditor) codeEditor = new CodeEditor(editor, opts);

            sender && sender.set('active', 0);
            codeEditor.showCodePopup()
        }
    });
};
