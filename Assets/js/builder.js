/**
 * Initialize theme selection
 *
 * @param themeField
 */
Mautic.initSelectTheme = (function (initSelectTheme) {
    return function (themeField) {
        let builderUrl = mQuery('#builder_url');

        // Replace Mautic URL by plugin URL
        if (builderUrl.length) {
            if (builderUrl.val().indexOf('pages') !== -1) {
                url = builderUrl.val().replace('s/pages/builder','s/grapesjsbuilder/page');
            } else {
                url = builderUrl.val().replace('s/emails/builder','s/grapesjsbuilder/email');
            }

            builderUrl.val(url);
        }

        // Launch original Mautic.initSelectTheme function
        initSelectTheme(themeField);
    }
})(Mautic.initSelectTheme);

/**
 * Launch builder
 *
 * @param formName
 * @param actionName
 */
Mautic.launchBuilder = function (formName, actionName) {
    Mautic.showChangeThemeWarning = true;

    // Prepare HTML
    mQuery('html').css('font-size', '100%');
    mQuery('body').css('overflow-y', 'hidden');
    mQuery('.builder-panel').css('padding', 0);
    mQuery('.builder').addClass('builder-active').removeClass('hide');

    // Initialize GrapesJS
    Mautic.initGrapesJS(formName);
};

/**
 * Initialize GrapesJsBuilder
 *
 * @param object
 */
Mautic.initGrapesJS = function (object) {
    let editor;
    let panelManager;
    let textareaHtml = mQuery('textarea.builder-html');
    let textareaAssets = mQuery('textarea#grapesjsbuilder_assets');
    let assetManagerConf = {
        assets: JSON.parse(textareaAssets.val()),
        noAssets: 'No <b>assets</b> here, drag to upload',
        upload: textareaAssets.data('upload'),
        uploadName: 'files',
        multiUpload: true,
        embedAsBase64: false,

        // Text on upload input
        uploadText: 'Drop files here or click to upload',
        // Label for the add button
        addBtnText: 'Add image',
        // Default title for the asset manager modal
        modalTitle: 'Select Image',

        openAssetsOnDrop: 1,
        autoAdd: true,
    };

    // Redefine Keyboard shortcuts due to unbind won't works with multiple keys.
    let keymapsConf = {
        defaults: {
            'core:undoios': {
                keys: '⌘+z',
                handler: 'core:undo'
            },
            'core:redoios': {
                keys: '⌘+shift+z',
                handler: 'core:redo'
            },
            'core:copyios': {
                keys: '⌘+c',
                handler: 'core:copy'
            },
            'core:pasteios': {
                keys: '⌘+v',
                handler: 'core:paste'
            },
            'core:undo': {
                keys: 'ctrl+z',
                handler: 'core:undo'
            },
            'core:redo': {
                keys: 'ctrl+shift+z',
                handler: 'core:redo'
            },
            'core:copy': {
                keys: 'ctrl+c',
                handler: 'core:copy'
            },
            'core:paste': {
                keys: 'ctrl+v',
                handler: 'core:paste'
            },
            'core:c-deletebackspace': {
                keys: 'backspace',
                handler: 'core:component-delete'
            },
            'core:c-deletesuppr': {
                keys: 'delete',
                handler: 'core:component-delete'
            },
        }
    };

    if (object === 'page') { // PageBuilder
        // Parse HTML template
        let parser = new DOMParser();
        let fullHtml = parser.parseFromString(textareaHtml.val(), "text/html");

        // Extract body
        let body = fullHtml.body.innerHTML;

        // Launch GrapesJS with body part
        editor = grapesjs.init({
            clearOnRender: true,
            container: '.builder-panel',
            components: body,
            height: '100%',
            storageManager: false,
            assetManager: assetManagerConf,
            styleManager: {
                clearProperties: true, // Temp fix https://github.com/artf/grapesjs-preset-webpage/issues/27
            },

            plugins: ['gjs-preset-webpage', 'grapesjs-parser-postcss', 'grapesjs-preset-mautic'],
            pluginsOpts: {
                'gjs-preset-webpage': {},
                'grapesjs-preset-mautic': {}
            },
            keymaps: keymapsConf
        });

        // Customize GrapesJS -> add close button with save for Mautic
        panelManager = editor.Panels;
        panelManager.addButton('views', [
            {
                id: 'close',
                className: 'fa fa-times-circle',
                attributes: {title: 'Close'},
                command: function () {
                    convertDynamicContentSlotsToTokens(editor);

                    // Update textarea for save
                    fullHtml.body.innerHTML = editor.getHtml() + '<style>' + editor.getCss({avoidProtected: true}) + '</style>';
                    textareaHtml.val(fullHtml.documentElement.outerHTML);

                    // Reset HTML
                    mQuery('.builder').removeClass('builder-active').addClass('hide');
                    mQuery('html').css('font-size', '');
                    mQuery('body').css('overflow-y', '');

                    // Destroy GrapesJS
                    editor.destroy();
                }
            }
        ]);
    } else if (object === 'emailform') {
        let textareaMjml = mQuery('textarea.builder-mjml');

        if (textareaMjml.val().length) { // EmailBuilder -> MJML
            editor = grapesjs.init({
                clearOnRender: true,
                container: '.builder-panel',
                components: textareaMjml.val(),
                height: '100%',
                storageManager: false,
                assetManager: assetManagerConf,

                plugins: ['grapesjs-mjml', 'grapesjs-parser-postcss', 'grapesjs-preset-mautic'],
                pluginsOpts: {
                    'grapesjs-mjml': {},
                    'grapesjs-preset-mautic': {}
                },
                keymaps: keymapsConf
            });

            // Customize GrapesJS -> add close button with save for Mautic
            panelManager = editor.Panels;
            panelManager.addButton('views', [
                {
                    id: 'close',
                    className: 'fa fa-times-circle',
                    attributes: {title: 'Close'},
                    command: function () {
                        convertDynamicContentSlotsToTokens(editor);

                        let code = '';

                        // Try catch for mjml parser error
                        try {
                            code = editor.runCommand('mjml-get-code');
                        } catch(error) {
                            console.log(error.message);
                            alert('Errors inside your template. Template will not be saved.');
                        }

                        // Update textarea for save
                        if (!code.length) {
                            textareaHtml.val(code.html);
                            textareaMjml.val(editor.getHtml());
                        }

                        // Reset HTML
                        mQuery('.builder').removeClass('builder-active').addClass('hide');
                        mQuery('html').css('font-size', '');
                        mQuery('body').css('overflow-y', '');

                        // Destroy GrapesJS
                        editor.destroy();
                    }
                }
            ]);
        } else { // EmailBuilder -> HTML
            // Parse HTML template
            let parser = new DOMParser();
            let fullHtml = parser.parseFromString(textareaHtml.val(), "text/html");

            // Extract body
            let body = fullHtml.body.innerHTML;

            // Launch GrapesJS with body part
            editor = grapesjs.init({
                clearOnRender: true,
                container: '.builder-panel',
                components: body,
                height: '100%',
                storageManager: false,
                assetManager: assetManagerConf,

                plugins: ['gjs-preset-newsletter', 'grapesjs-parser-postcss', 'grapesjs-preset-mautic'],
                pluginsOpts: {
                    'gjs-preset-newsletter': {},
                    'grapesjs-preset-mautic': {}
                },
                keymaps: keymapsConf
            });

            // Customize GrapesJS -> add close button with save for Mautic
            panelManager = editor.Panels;
            panelManager.addButton('views', [
                {
                    id: 'close',
                    className: 'fa fa-times-circle',
                    attributes: {title: 'Close'},
                    command: function () {
                        convertDynamicContentSlotsToTokens(editor);

                        // Update textarea for save
                        fullHtml.body.innerHTML = editor.runCommand('gjs-get-inlined-html');
                        textareaHtml.val(fullHtml.documentElement.outerHTML);

                        // Reset HTML
                        mQuery('.builder').removeClass('builder-active').addClass('hide');
                        mQuery('html').css('font-size', '');
                        mQuery('body').css('overflow-y', '');

                        // Destroy GrapesJS
                        editor.destroy();
                    }
                }
            ]);
        }
    }

    editor.on('load', (response) => {
        const um = editor.UndoManager;

        convertDynamicContentTokenToSlot(editor);

        // Clear stack of undo/redo
        um.clear();
    });

    editor.on('component:add', (component) => {
        let type = component.get('type');

        // Create dynamic-content on Mautic side
        if (type === 'dynamic-content') {
            let dynConTarget = Mautic.createNewDynamicContentItem(mQuery);
            let dynConTab = mQuery('#dynamicContentTabs').find('a[href="'+dynConTarget+'"]');

            component.set('content', dynConTab.text());
            component.addAttributes({'data-param-dec-id': parseInt(dynConTarget.replace(/[^0-9]/g, '')) });
        }
    });

    editor.on('component:remove', (component) => {
        let type = component.get('type');

        // Delete dynamic-content on Mautic side
        if (type === 'dynamic-content') {
            deleteDynamicContentItem(component);
        }
    });

    const keymaps = editor.Keymaps;
    let allKeymaps;

    editor.on('modal:open', () => {
        // Save all keyboard shortcuts
        allKeymaps = Object.assign({}, keymaps.getAll());

        // Remove keyboard shortcuts to prevent launch behind popup
        keymaps.removeAll();
    });

    editor.on('modal:close', () => {
        // ReMap keyboard shortcuts on modal close
        Object.keys(allKeymaps).map(function(objectKey) {
            let shortcut = allKeymaps[objectKey];

            keymaps.add(shortcut.id, shortcut.keys, shortcut.handler);
        });

        let modalContent = editor.Modal.getContent().querySelector('#dynamic-content-popup');

        // On modal close -> update view of DC block and move editor within Mautic
        if (modalContent !== null) {
            let dynamicContentContainer = mQuery('#dynamicContentContainer');
            let content = mQuery(modalContent).contents().first();

            let dynConTarget = '#'+content.attr('id');
            let dynConTab = mQuery('#dynamicContentTabs').find('a[href=' + dynConTarget + ']');
            let dynConContent = '';

            // Get editor content to populate view
            if (content.html()) {
                let dynConContainer = content.find(dynConTarget+'_content');

                if (dynConContainer.hasClass('editor')) {
                    dynConContent = dynConContainer.froalaEditor('html.get');
                } else {
                    dynConContent = dynConContainer.html();
                }
            }

            // If editor empty populate view with tab text
            if (dynConContent.trim() === '') {
                dynConContent = dynConTab.text();
            }

            let dynamicContent = editor.DomComponents.getWrapper().find('[data-param-dec-id="' + parseInt(dynConTarget.replace(/[^0-9]/g, '')) + '"]');
            dynamicContent[0].set('content', dynConContent);

            dynamicContentContainer.append(content.detach());
        }
    });

    editor.on('asset:add', (response) => {
        // Save assets list in textarea to keep new uploaded files without reload page
        textareaAssets.val(JSON.stringify(getAssetsList(editor)));
    });

    editor.on('asset:remove', (response) => {
        // Save assets list in textarea to keep new deleted files without reload page
        textareaAssets.val(JSON.stringify(getAssetsList(editor)));

        // Delete file on server
        mQuery.ajax({
            url: textareaAssets.data('delete'),
            data: {'filename': response.getFilename()}
        });
    });
};

/**
 * Set theme's HTML
 *
 * @param theme
 */
Mautic.setThemeHtml = function(theme) {
    setupButtonLoadingIndicator(true);

    // Load template and fill field
    mQuery.ajax({
        url: mQuery('#builder_url').val(),
        data: 'template=' + theme,
        dataType: 'json',
        success: function (response) {
            let textareaHtml = mQuery('textarea.builder-html');
            let textareaMjml = mQuery('textarea.builder-mjml');

            textareaHtml.val(response.templateHtml);

            if (typeof textareaMjml !== 'undefined') {
                textareaMjml.val(response.templateMjml);

                // If MJML template, generate HTML before save
                if (!textareaHtml.val().length && textareaMjml.val().length) {
                    mjmlToHtml(textareaMjml, textareaHtml);
                }
            }
        },
        error: function (request, textStatus, errorThrown) {
            console.log("setThemeHtml - Request failed: " + textStatus);
        },
        complete: function() {
            setupButtonLoadingIndicator(false);
        }
    });
};

/**
 * Init GrapesJS to generate HTML
 *
 * @param source - Textarea where MJML is stored
 * @param destination - Textarea where HTML will be stored
 * @param container - Invisible container to init GrapesJS
 */
let mjmlToHtml = function (source, destination, container) {
    if (typeof(container) === 'undefined' ) {
        container = '.builder-panel';
    }

    let code = '';
    let editor = grapesjs.init({
        clearOnRender: true,
        container: container,
        components: source.val(),
        storageManager: false,
        panels: {defaults: []},

        plugins: ['grapesjs-mjml', 'grapesjs-parser-postcss'],
        pluginsOpts: {
            'grapesjs-mjml': {}
        }
    });

    // Try catch for MJML parser error
    try {
        code = editor.runCommand('mjml-get-code');
    } catch (error) {
        console.log(error.message);
        alert('Errors inside your template. Template will not be saved.');
    }

    // Set result to destination
    if (!code.length) {
        destination.val(code.html);
    }

    // Destroy GrapesJS
    editor.destroy();
};

/**
 * Manage button loading indicator
 *
 * @param activate - true or false
 */
let setupButtonLoadingIndicator = function (activate) {
    let builderButton = mQuery('.btn-builder');
    let saveButton = mQuery('.btn-save');
    let applyButton = mQuery('.btn-apply');

    if (activate) {
        Mautic.activateButtonLoadingIndicator(builderButton);
        Mautic.activateButtonLoadingIndicator(saveButton);
        Mautic.activateButtonLoadingIndicator(applyButton);
    } else {
        Mautic.removeButtonLoadingIndicator(builderButton);
        Mautic.removeButtonLoadingIndicator(saveButton);
        Mautic.removeButtonLoadingIndicator(applyButton);
    }
};

/**
 * Generate assets list from GrapesJs
 *
 * @param editor
 */
let getAssetsList = function(editor) {
    let assetManager = editor.AssetManager;
    let assets = assetManager.getAll();
    let assetsList = [];

    assets.forEach(asset => {
        if (asset.get('type') === 'image') {
            assetsList.push({'src': asset.get('src'), 'width': asset.get('width'), 'height': asset.get('height')});
        } else {
            assetsList.push(asset.get('src'));
        }
    });

    return assetsList;
};

/**
 * Convert dynamic content tokens to slot and load content
 *
 * @param editor
 */
let convertDynamicContentTokenToSlot = function(editor) {
    const dc = editor.DomComponents;

    let dynamicContents = dc.getWrapper().find('[data-slot="dynamicContent"]');

    if (dynamicContents.length) {
        dynamicContents.forEach(dynamicContent => {
            const regex = RegExp(/\{dynamiccontent="(.*)"\}/,'g');

            let content = dynamicContent.get('content');
            let dynConName = regex.exec(content)[1];
            let dynConTab = mQuery('#dynamicContentTabs').find('a:contains("'+dynConName+'")');
            let dynConTarget = dynConTab.attr('href');
            let dynConContent = '';

            if (mQuery(dynConTarget).html()) {
                let dynConContainer = mQuery(dynConTarget).find(dynConTarget+'_content');

                if (dynConContainer.hasClass('editor')) {
                    dynConContent = dynConContainer.froalaEditor('html.get');
                } else {
                    dynConContent = dynConContainer.html();
                }
            }

            if (dynConContent === '') {
                dynConContent = dynConTab.text();
            }

            dynamicContent.addAttributes({'data-param-dec-id': parseInt(dynConTarget.replace(/[^0-9]/g, '')) });
            dynamicContent.set('content', dynConContent);
        });
    }
};

/**
 * Convert dynamic content slots to tokens
 *
 * @param editor
 */
let convertDynamicContentSlotsToTokens = function (editor) {
    const dc = editor.DomComponents;

    let dynamicContents = dc.getWrapper().find('[data-slot="dynamicContent"]');

    if (dynamicContents.length) {
        dynamicContents.forEach(dynamicContent => {
            let attributes = dynamicContent.getAttributes();
            let dynConId   = '#emailform_dynamicContent_' + attributes['data-param-dec-id'];

            let dynConTarget = mQuery(dynConId);
            let dynConName   = dynConTarget.find(dynConId + '_tokenName').val();
            let dynConToken  = '{dynamiccontent="'+dynConName+'"}';

            // Clear id because it's reloaded by Mautic and this prevent slot to be destroyed by GrapesJs destroy event on close.
            dynamicContent.addAttributes({'data-param-dec-id': ''});
            dynamicContent.set('content', dynConToken);
        });
    }
};

/**
 * Delete DC on Mautic side
 *
 * @param component
 */
let deleteDynamicContentItem = function (component) {
    let attributes = component.getAttributes();

    // Only delete if we click on trash, not when GrapesJs is destroy
    if (attributes['data-param-dec-id'] !== '') {
        let dynConId     = '#emailform_dynamicContent_' + attributes['data-param-dec-id'];
        let dynConTarget = mQuery(dynConId);

        if (typeof dynConTarget !== 'undefined') {
            dynConTarget.find('a.remove-item:first').click();
            // remove vertical tab in outside form
            mQuery('.dynamicContentFilterContainer').find('a[href=' + dynConId + ']').parent().remove();
        }
    }
};
