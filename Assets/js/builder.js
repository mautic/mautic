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
        });

        // Customize GrapesJS -> add close button with save for Mautic
        panelManager = editor.Panels;
        panelManager.addButton('views', [
            {
                id: 'close',
                className: 'fa fa-times-circle',
                attributes: {title: 'Close'},
                command: function () {
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
                }
            });

            // Customize GrapesJS -> add close button with save for Mautic
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
                }
            });

            // Customize GrapesJS -> add close button with save for Mautic
            panelManager = editor.Panels;
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
