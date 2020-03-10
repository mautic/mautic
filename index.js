export default (editor, opts = {}) => {
    const am = editor.AssetManager;
    const pm = editor.Panels;
    const modal = editor.Modal;
    const cmd = editor.Commands;
    const cfg = editor.getConfig();

    let mjmlTemplate = false;

    // Check if MJML plugin is on
    if (cfg.plugins.includes('grapesjs-mjml')) {
        mjmlTemplate = true;
    }

    let config = {
        sourceEdit: 1,
        sourceEditBtnLabel: 'Edit',
        sourceEditModalTitle: 'Edit code',
        deleteAssetConfirmText: 'Are you sure?',
        showLayersManager: 0,
        showImportButton: 0,
        ...opts,
    };

    // Extend the original `image` and add a confirm dialog before removing it
    am.addType('image', {
        // As you adding on top of an already defined type you can avoid indicating
        // `am.getType('image').view.extend({...` the editor will do it by default
        // but you can eventually extend some other type
        view: {
            // If you want to see more methods to extend check out
            // https://github.com/artf/grapesjs/blob/dev/src/asset_manager/view/AssetImageView.js
            onRemove(e) {
                e.stopPropagation();
                const model = this.model;

                if (confirm(config.deleteAssetConfirmText)) {
                    model.collection.remove(model);
                }
            }
        },
    });

    editor.on('load', function() {
        let $ = grapesjs.$;

        // Load and show settings and style manager
        let openTmBtn = pm.getButton('views', 'open-tm');
        openTmBtn && openTmBtn.set('active', 1);
        let openSm = pm.getButton('views', 'open-sm');
        openSm && openSm.set('active', 1);

        pm.removeButton("views", "open-tm");

        if (!config.showLayersManager) {
            pm.removeButton("views", "open-layers");
        }

        if (!config.showImportButton) {
            if (mjmlTemplate) {
                pm.removeButton("options", "mjml-import");
            } else {
                pm.removeButton("options", "gjs-open-import-template");
            }
        }

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

        // Open block manager
        let openBlocksBtn = editor.Panels.getButton('views', 'open-blocks');
        openBlocksBtn && openBlocksBtn.set('active', 1);
    });

    // Add function within builder to edit source code
    if (config.sourceEdit) {
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

        btnEdit.innerHTML = config.sourceEditBtnLabel;
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
                modal.setTitle(config.sourceEditModalTitle);

                if (!viewer) {
                    let textarea = document.createElement('textarea');
                    container.appendChild(textarea);
                    container.appendChild(btnEdit);
                    codeViewer.init(textarea);
                    viewer = codeViewer.editor;
                }

                let content;
                if (mjmlTemplate) {
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
                    title: config.sourceEditModalTitle
                }
            }
        ]);
    }
};
