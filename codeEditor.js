class CodeEditor {
    constructor(editor, opts = {}) {
        this.editor = editor;
        this.opts = opts;

        this.codeEditor = this.buildCodeEditor();
        this.codePopup = this.buildCodePopup();
    }

    buildCodeEditor() {
        let codeEditor = this.editor.CodeManager.getViewer('CodeMirror').clone();

        codeEditor.set({
            codeName: 'htmlmixed',
            readOnly: false,
            theme: 'hopscotch',
            autoBeautify: true,
            autoCloseTags: true,
            autoCloseBrackets: true,
            lineWrapping: true,
            styleActiveLine: true,
            smartIndent: true,
            indentWithTabs: true
        });

        return codeEditor;
    }

    buildCodePopup() {
        const cfg = this.editor.getConfig();

        let codePopup = document.createElement('div');
        let btnEdit = document.createElement('button');
        let textarea = document.createElement('textarea');

        btnEdit.innerHTML = this.opts.sourceEditBtnLabel;
        btnEdit.className = cfg.stylePrefix + 'btn-prim ' + cfg.stylePrefix + 'btn-code-edit';
        btnEdit.onclick = this.updateCode.bind(this);

        codePopup.appendChild(textarea);
        codePopup.appendChild(btnEdit);

        this.codeEditor.init(textarea);
        this.updateEditorContents();

        return codePopup;
    }

    showCodePopup() {
        this.editor.Modal.setContent('');
        this.editor.Modal.setContent(this.codePopup);
        this.editor.Modal.setTitle(this.opts.sourceEditModalTitle);

        this.updateEditorContents();
        this.codeEditor.editor.refresh();

        this.editor.Modal.open();
    }

    updateCode() {
        let code = this.codeEditor.editor.getValue();

        this.editor.DomComponents.getWrapper().set('content', '');
        this.editor.setComponents(code.trim());

        this.editor.Modal.close();
    }

    updateEditorContents() {
        let content;

        if (this.opts.mjmlTemplate) {
            content = this.editor.getHtml();
        } else {
            content = this.editor.getHtml() + '<style>' + this.editor.getCss({avoidProtected: true}) + '</style>';
        }

        this.codeEditor.setContent(content);
    }
}

export default CodeEditor
