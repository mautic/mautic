export default (editor, opts = {}) => {
    const pm = editor.Panels;
    const modal = editor.Modal;
    const cmd = editor.Commands;
    const cfg = editor.getConfig();

    if (!opts.showImportButton) {
        if (opts.mjmlTemplate) {
            pm.removeButton("options", "mjml-import");
        } else {
            pm.removeButton("options", "gjs-open-import-template");
        }
    }

    // Add function within builder to edit source code
    if (opts.sourceEdit) {
        let pfx = cfg.stylePrefix;
        let codeViewer = editor.CodeManager.getViewer('CodeMirror').clone();
        let container = document.createElement('div');
        let btnEdit = document.createElement('button');

        codeViewer.set({
            codeName: 'htmlmixed',
            readOnly: 0,
            theme: 'hopscotch',
            autoBeautify: true,
            autoCloseTags: true,
            autoCloseBrackets: true,
            lineWrapping: true,
            styleActiveLine: true,
            smartIndent: true,
            indentWithTabs: true
        });

        btnEdit.innerHTML = opts.sourceEditBtnLabel;
        btnEdit.className = pfx + 'btn-prim ' + pfx + 'btn-import';
        btnEdit.onclick = function () {
            let code = codeViewer.editor.getValue();
            editor.DomComponents.getWrapper().set('content', '');
            editor.setComponents(code.trim());
            modal.close();
        };

        cmd.add('html-edit', {
            run: function (editor, sender) {
                sender && sender.set('active', 0);
                let viewer = codeViewer.editor;
                modal.setTitle(opts.sourceEditModalTitle);

                if (!viewer) {
                    let textarea = document.createElement('textarea');
                    container.appendChild(textarea);
                    container.appendChild(btnEdit);
                    codeViewer.init(textarea);
                    viewer = codeViewer.editor;
                }

                let content;
                if (opts.mjmlTemplate) {
                    content = editor.getHtml();
                } else {
                    content = editor.runCommand('gjs-get-inlined-html');
                }

                modal.setContent('');
                modal.setContent(container);
                codeViewer.setContent(content);
                modal.open();
                viewer.refresh();
            }
        });

        pm.addButton('options', [
            {
                id: 'edit',
                className: 'fa fa-edit',
                command: 'html-edit',
                attributes: {
                    title: opts.sourceEditModalTitle
                }
            }
        ]);
    }
};
