import CodeEditor from './codeEditor';

export default (editor, opts = {}) => {
    const cmd = editor.Commands;

    let codeEditor = null;

    cmd.add('code-edit', {
        run: (editor, sender) => {
            if (!codeEditor) codeEditor = new CodeEditor(editor, opts);

            sender && sender.set('active', 0);
            codeEditor.showCodePopup()
        }
    });
};
