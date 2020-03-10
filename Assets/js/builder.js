/**
 * Initialize theme selection
 *
 * @param themeField
 */
Mautic.coreInitSelectTheme = Mautic.initSelectTheme;

Mautic.initSelectTheme = function(themeField) {
    let builderUrl = mQuery('#builder_url');

    if (builderUrl.length) {
        if (builderUrl.val().indexOf('pages') !== -1) {
            url = builderUrl.val().replace('s/pages/builder','s/grapesjsbuilder/page');
        } else {
            url = builderUrl.val().replace('s/emails/builder','s/grapesjsbuilder/email');
        }

        builderUrl.val(url);
    }

    Mautic.coreInitSelectTheme(themeField);
};

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

    // Init GrapesJS
    Mautic.initGrapesJS(actionName);
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
    let textareaMjml = mQuery('textarea.builder-mjml');
    let textareaAssets = mQuery('textarea#grapesjsbuilder_assets');

    if (object === 'page') {
        editor = grapesjs.init({
            clearOnRender: true,
            container: '.builder-panel',
            components: textareaHtml.val(),
            height: '100%',
            storageManager: false,

            plugins: ['gjs-preset-newsletter', 'grapesjs-parser-postcss'],
            pluginsOpts: {
                'gjs-preset-newsletter': {
                    modalTitleImport: 'Import HTML template',
                    modalLabelImport: 'Paste all your code here below and click import',
                    modalLabelExport: 'Copy the code and use it wherever you want',
                    importPlaceholder: ''
                },
            }
        });
    } else {
        if (textareaMjml.val().length) {
            editor = grapesjs.init({
                clearOnRender: true,
                container: '.builder-panel',
                components: textareaMjml.val(),
                height: '100%',
                storageManager: false,

                plugins: ['grapesjs-mjml', 'grapesjs-parser-postcss', 'grapesjs-preset-mautic'],
                pluginsOpts: {
                    'grapesjs-mjml': {
                        modalTitleImport: 'Import MJML template',
                        modalLabelImport: 'Paste all your code here below and click import',
                        modalLabelExport: 'Copy the code and use it wherever you want',
                        importPlaceholder: '',
                    },
                    'grapesjs-preset-mautic': {}
                }
            });

            panelManager = editor.Panels;
            panelManager.addButton('views', [
                {
                    id: 'close',
                    className: 'fa fa-times-circle',
                    attributes: {title: 'Close'},
                    command: function () {
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
        } else {
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

                assetManager: {
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
                },

                plugins: ['gjs-preset-newsletter', 'grapesjs-parser-postcss', 'grapesjs-preset-mautic'],
                pluginsOpts: {
                    'gjs-preset-newsletter': {
                        modalTitleImport: 'Import HTML template',
                        modalLabelImport: 'Paste all your code here below and click import',
                        modalLabelExport: 'Copy the code and use it wherever you want',
                        importPlaceholder: ''
                    },
                    'grapesjs-preset-mautic': {}
                }
            });

            // Customize GrapesJS
            panelManager = editor.Panels;
            panelManager.removeButton("options", "gjs-toggle-images");
            panelManager.addButton('options', [
                {
                    id: 'undo',
                    className: 'fa fa-undo',
                    attributes: {title: 'Undo'},
                    command: function () { editor.runCommand('core:undo') }
                }, {
                    id: 'redo',
                    className: 'fa fa-repeat',
                    attributes: {title: 'Redo'},
                    command: function () { editor.runCommand('core:redo') }
                }
            ]);

            panelManager.addButton('views', [
                {
                    id: 'close',
                    className: 'fa fa-times-circle',
                    attributes: {title: 'Close'},
                    command: function () {
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

    editor.on('asset:add', (response) => {
        // Save assets list in textarea to keep new uploaded files without reload page
        textareaAssets.val(JSON.stringify(getAssetsList(editor)));
    });

    editor.on('asset:remove', (response) => {
        // Save assets list in textarea to keep new deleted files without reload page
        textareaAssets.val(JSON.stringify(getAssetsList(editor)));

        // Delete file
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

    mQuery.ajax({
        url: mQuery('#builder_url').val(),
        data: 'template=' + theme,
        dataType: 'json',
        success: function (response) {
            let textareaHtml = mQuery('textarea.builder-html');
            let textareaMjml = mQuery('textarea.builder-mjml');

            textareaHtml.val(response.templateHtml);
            textareaMjml.val(response.templateMjml);

            // If MJML template, generate HTML before save
            if (!textareaHtml.val().length && textareaMjml.val().length) {
                mjmlToHtml(textareaMjml, textareaHtml);
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
    let builderButton = mQuery('#emailform_buttons_builder_toolbar');
    let saveButton = mQuery('#emailform_buttons_save_toolbar');
    let applyButton = mQuery('#emailform_buttons_apply_toolbar');

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

let getAssetsList = function(editor) {
    let assetManager = editor.AssetManager;
    let assets = assetManager.getAll().models;
    let assetsList = [];

    assets.forEach(function(file) {
        let attributes = file.attributes;

        if (attributes.type === 'image') {
            assetsList.push({'src': attributes.src, 'width': attributes.width, 'height': attributes.height});
        } else {
            assetsList.push(attributes.src);
        }
    });

    return assetsList;
};