import loadButtons from './buttons';
import loadBlocks from './blocks';

export default (editor, opts = {}) => {
    const am = editor.AssetManager;
    const pm = editor.Panels;
    const cfg = editor.getConfig();

    let config = {
        sourceEdit: 1,
        sourceEditBtnLabel: 'Edit',
        sourceEditModalTitle: 'Edit code',
        deleteAssetConfirmText: 'Are you sure?',
        showImportButton: 0,
        mjmlTemplate: false,
        ...opts,
    };

    // Check if MJML plugin is on
    if (cfg.plugins.includes('grapesjs-mjml')) {
        config.mjmlTemplate = true;
    }

    // Extend the original `image` and add a confirm dialog before removing it
    am.addType('image', {
        // As you adding on top of an already defined type you can avoid indicating
        // `am.getType('image').view.extend({...` the editor will do it by default
        // but you can eventually extend some other type
        view: {
            // If you want to see more methods to extend check out
            // https://github.com/artf/grapesjs/blob/dev/src/asset_manager/view/AssetImageView.js
            onRemove(e) {
                e.stopImmediatePropagation();
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

    loadButtons(editor, config);
    loadBlocks(editor, config);
};
