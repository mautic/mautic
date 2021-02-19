import BuilderService from './builder.service';
// import builder from './builder.service';

/**
 * Launch builder
 *
 * @param formName
 * @param actionName
 */
function launchBuilderGrapesjs(formName) {
  // Parse HTML template
  const parser = new DOMParser();
  const textareaHtml = mQuery('textarea.builder-html');
  // const textareaAssets = mQuery('textarea#grapesjsbuilder_assets');
  const fullHtml = parser.parseFromString(textareaHtml.val(), 'text/html');

  const canvasContent = fullHtml.body.innerHTML
    ? fullHtml.body.innerHTML
    : mQuery('textarea.builder-mjml').val();

  const builder = new BuilderService(canvasContent);

  Mautic.showChangeThemeWarning = true;

  // Prepare HTML
  mQuery('html').css('font-size', '100%');
  mQuery('body').css('overflow-y', 'hidden');
  mQuery('.builder-panel').css('padding', 0);
  mQuery('.builder-panel').css('display', 'block');
  mQuery('.builder').addClass('builder-active').removeClass('hide');

  // Initialize GrapesJS
  builder.initGrapesJS(formName);
}

function manageDynamicContentTokenToSlot(component) {
  const regex = RegExp(/\{dynamiccontent="(.*)"\}/, 'g');

  const content = component.get('content');
  const regexEx = regex.exec(content);

  if (regexEx !== null) {
    const dynConName = regexEx[1];
    const dynConTabA = mQuery('#dynamicContentTabs a').filter(
      () => mQuery(this).text().trim() === dynConName
    );

    if (typeof dynConTabA !== 'undefined' && dynConTabA.length) {
      // If exist -> fill
      const dynConTarget = dynConTabA.attr('href');
      let dynConContent = '';

      if (mQuery(dynConTarget).html()) {
        const dynConContainer = mQuery(dynConTarget).find(`${dynConTarget}_content`);

        if (dynConContainer.hasClass('editor')) {
          dynConContent = dynConContainer.froalaEditor('html.get');
        } else {
          dynConContent = dynConContainer.html();
        }
<<<<<<< HEAD
      }
=======
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
                'gjs-preset-webpage': {
                    'formsOpts': false,
                },
                'grapesjs-preset-mautic': presetMauticConf
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
                    Mautic.grapesConvertDynamicContentSlotsToTokens(editor);

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
                    'grapesjs-preset-mautic': presetMauticConf
                },
                keymaps: keymapsConf
            });

            editor.BlockManager.get('mj-button').set({
                content: "<mj-button href=\"https://\">Button</mj-button>",
            });

            // Customize GrapesJS -> add close button with save for Mautic
            panelManager = editor.Panels;
            panelManager.addButton('views', [
                {
                    id: 'close',
                    className: 'fa fa-times-circle',
                    attributes: {title: 'Close'},
                    command: function () {
                        Mautic.grapesConvertDynamicContentSlotsToTokens(editor);

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
>>>>>>> mautic3x

      if (dynConContent === '') {
        dynConContent = dynConTabA.text();
      }

      component.addAttributes({
        'data-param-dec-id': parseInt(dynConTarget.replace(/[^0-9]/g, ''), 10),
      });
      component.set('content', dynConContent);
    } else {
      // If doesn't exist -> create
      const dynConTarget = Mautic.createNewDynamicContentItem(mQuery);
      const dynConTab = mQuery('#dynamicContentTabs').find(`a[href="${dynConTarget}"]`);

      component.addAttributes({
        'data-param-dec-id': parseInt(dynConTarget.replace(/[^0-9]/g, ''), 10),
      });
      component.set('content', dynConTab.text());
    }
<<<<<<< HEAD
  }
}
=======

    editor.on('load', (response) => {
        const um = editor.UndoManager;

        Mautic.grapesConvertDynamicContentTokenToSlot(editor);

        // Clear stack of undo/redo
        um.clear();
    });

    editor.on('component:add', (component) => {
        let type = component.get('type');

        // Create dynamic-content on Mautic side
        if (type === 'dynamic-content') {
            manageDynamicContentTokenToSlot(component);
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
        const commands = editor.Commands;
        const cmdCodeEdit = 'preset-mautic:code-edit';
        const cmdDynamicContent = 'preset-mautic:dynamic-content';

        // Launch preset-mautic:code-edit command stop
        if (commands.isActive(cmdCodeEdit)) {
            commands.stop(cmdCodeEdit, {editor});
        }

        // Launch preset-mautic:dynamic-content command stop
        if (commands.isActive(cmdDynamicContent)) {
            commands.stop(cmdDynamicContent, {editor});
        }

        // ReMap keyboard shortcuts on modal close
        Object.keys(allKeymaps).map(function(objectKey) {
            let shortcut = allKeymaps[objectKey];

            keymaps.add(shortcut.id, shortcut.keys, shortcut.handler);
        });

        let modalContent = editor.Modal.getContentEl().querySelector('#dynamic-content-popup');

        // On modal close -> move editor within Mautic
        if (modalContent !== null) {
            let dynamicContentContainer = mQuery('#dynamicContentContainer');
            let content = mQuery(modalContent).contents().first();

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
>>>>>>> mautic3x

/**
 * Set theme's HTML
 *
 * @param theme
 */
function setThemeHtml(theme) {
  BuilderService.setupButtonLoadingIndicator(true);
  // Load template and fill field
  mQuery.ajax({
    url: mQuery('#builder_url').val(),
    data: `template=${theme}`,
    dataType: 'json',
    success(response) {
      const textareaHtml = mQuery('textarea.builder-html');
      const textareaMjml = mQuery('textarea.builder-mjml');

      textareaHtml.val(response.templateHtml);

      if (typeof textareaMjml !== 'undefined') {
        textareaMjml.val(response.templateMjml);
      }

      // If MJML template, generate HTML before save
      // if (!textareaHtml.val().length && textareaMjml.val().length) {
      //   builder.mjmlToHtml(textareaMjml, textareaHtml);
      // }
      // }
    },
    error(request, textStatus) {
      console.log(`setThemeHtml - Request failed: ${textStatus}`);
    },
    complete() {
      BuilderService.setupButtonLoadingIndicator(false);
    },
  });
}

/**
 * Convert dynamic content tokens to slot and load content
 */
function grapesConvertDynamicContentTokenToSlot(editor) {
  const dc = editor.DomComponents;

  const dynamicContents = dc.getWrapper().find('[data-slot="dynamicContent"]');

  if (dynamicContents.length) {
    dynamicContents.forEach((dynamicContent) => {
      manageDynamicContentTokenToSlot(dynamicContent);
    });
  }
}

/**
 * Initialize original Mautic theme selection with grapejs specific modifications
 */
function initSelectThemeGrapesjs(parentInitSelectTheme) {
  function childInitSelectTheme(themeField) {
    const builderUrl = mQuery('#builder_url');
    let url;

    // Replace Mautic URL by plugin URL
    if (builderUrl.length) {
      if (builderUrl.val().indexOf('pages') !== -1) {
        url = builderUrl.val().replace('s/pages/builder', 's/grapesjsbuilder/page');
      } else {
        url = builderUrl.val().replace('s/emails/builder', 's/grapesjsbuilder/email');
      }

      builderUrl.val(url);
    }

    // Launch original Mautic.initSelectTheme function
    parentInitSelectTheme(themeField);
  }
  return childInitSelectTheme;
}

Mautic.grapesConvertDynamicContentTokenToSlot = grapesConvertDynamicContentTokenToSlot;
Mautic.launchBuilder = launchBuilderGrapesjs;
Mautic.initSelectTheme = initSelectThemeGrapesjs(Mautic.initSelectTheme);
Mautic.setThemeHtml = setThemeHtml;
