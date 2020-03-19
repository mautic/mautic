export default (editor, opts = {}) => {
    const pm = editor.Panels;
    const modal = editor.Modal;
    const cmd = editor.Commands;
    const cfg = editor.getConfig();

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

    // Disable Import code button
    if (!opts.showImportButton) {
        let mjmlImportBtn = pm.getButton('options', 'mjml-import');
        let htmlImportBtn = pm.getButton('options', 'gjs-open-import-template');
        let pageImportBtn = pm.getButton('options', 'gjs-open-import-webpage');

        if (mjmlImportBtn !== null) {
            pm.removeButton('options', 'mjml-import');
        }

        if (htmlImportBtn !== null) {
            pm.removeButton('options', 'gjs-open-import-template');
        }

        if (pageImportBtn !== null) {
            pm.removeButton('options', 'gjs-open-import-webpage');
        }
    }

    // Move Undo & Redo inside Commands Panel
    let undo = pm.getButton('options', 'undo');
    let redo = pm.getButton('options', 'redo');

    if (undo !== null) {
        pm.removeButton('options', 'undo');
        pm.addButton('commands', [
            {
                id: 'undo',
                className: 'fa fa-undo',
                attributes: {title: 'Undo'},
                command: function () { editor.runCommand('core:undo') }
            }
        ]);
    }

    if (redo !== null) {
        pm.removeButton('options', 'redo');
        pm.addButton('commands', [
            {
                id: 'redo',
                className: 'fa fa-repeat',
                attributes: {title: 'Redo'},
                command: function () { editor.runCommand('core:redo') }
            }
        ]);
    }

    // Remove preview button
    let preview = pm.getButton('options', 'preview');

    if (preview !== null) {
        pm.removeButton('options', 'preview');
    }

    // Remove clear button
    let clear = pm.getButton('options', 'canvas-clear');

    if (clear !== null) {
        pm.removeButton('options', 'canvas-clear');
    }

    // Remove toggle images button
    let toggleImages = pm.getButton('options', 'gjs-toggle-images');

    if (toggleImages !== null) {
        pm.removeButton('options', 'gjs-toggle-images');
    }

    // Do stuff on load
    editor.on('load', function() {
        // Hide Layers Manager
        if (!opts.showLayersManager) {
            let openLayersBtn = pm.getButton('views', 'open-layers');

            if (openLayersBtn !== null) {
                openLayersBtn.set('attributes', {
                    style: 'display:none;',
                });
            }
        }

        // Activate by default View Components button
        let viewComponents = pm.getButton('options', 'sw-visibility');
        viewComponents && viewComponents.set('active', 1);

        let $ = grapesjs.$;

        // Load and show settings and style manager
        let openTmBtn = pm.getButton('views', 'open-tm');
        openTmBtn && openTmBtn.set('active', 1);
        let openSm = pm.getButton('views', 'open-sm');
        openSm && openSm.set('active', 1);

        pm.removeButton("views", "open-tm");

        // Add Settings Sector
        let traitsSector = $('<div class="gjs-sm-sector no-select">'+
            '<div class="gjs-sm-title"><span class="icon-settings fa fa-cog"></span> Settings</div>' +
            '<div class="gjs-sm-properties" style="display: none;"></div></div>');
        let traitsProps = traitsSector.find('.gjs-sm-properties');

        traitsProps.append($('.gjs-trt-traits'));
        $('.gjs-sm-sectors').before(traitsSector);
        traitsSector.find('.gjs-sm-title').on('click', function(){
            let traitStyle = traitsProps.get(0).style;
            let hidden = traitStyle.display === 'none';

            if (hidden) {
                traitStyle.display = 'block';
            } else {
                traitStyle.display = 'none';
            }
        });

        // Open settings
        traitsProps.get(0).style.display = 'block';

        // Open block manager
        let openBlocksBtn = editor.Panels.getButton('views', 'open-blocks');
        openBlocksBtn && openBlocksBtn.set('active', 1);
    });
};
