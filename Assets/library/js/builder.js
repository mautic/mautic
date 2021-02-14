import builder from './builder.service';

/**
 * Initialize theme selection
 *
 * @param themeField
 */
Mautic.initSelectTheme = (function (initSelectTheme) {
  return function (themeField) {
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
    initSelectTheme(themeField);
  };
})(Mautic.initSelectTheme);

/**
 * Launch builder
 *
 * @param formName
 * @param actionName
 */
Mautic.launchBuilder = function (formName) {
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

Mautic.setListeners = function (editor) {
  if (!editor) {
    throw Error('No editor found');
  }
  editor.on('load', () => {
    const um = editor.UndoManager;

    Mautic.grapesConvertDynamicContentTokenToSlot(editor);

    // Clear stack of undo/redo
    um.clear();
  });

  editor.on('component:add', (component) => {
    const type = component.get('type');

    // Create dynamic-content on Mautic side
    if (type === 'dynamic-content') {
      builder.manageDynamicContentTokenToSlot(component);
    }
  });

  editor.on('component:remove', (component) => {
    const type = component.get('type');

    // Delete dynamic-content on Mautic side
    if (type === 'dynamic-content') {
      builder.deleteDynamicContentItem(component);
    }
  });

  const keymaps = editor.Keymaps;
  let allKeymaps;

  editor.on('modal:open', () => {
    // Save all keyboard shortcuts
    allKeymaps = { ...keymaps.getAll() };

    // Remove keyboard shortcuts to prevent launch behind popup
    keymaps.removeAll();
  });

  editor.on('modal:close', () => {
    const commands = editor.Commands;
    const cmdCodeEdit = 'preset-mautic:code-edit';
    const cmdDynamicContent = 'preset-mautic:dynamic-content';

    // Launch preset-mautic:code-edit command stop
    if (commands.isActive(cmdCodeEdit)) {
      commands.stop(cmdCodeEdit, { editor });
    }

    // Launch preset-mautic:dynamic-content command stop
    if (commands.isActive(cmdDynamicContent)) {
      commands.stop(cmdDynamicContent, { editor });
    }

    // ReMap keyboard shortcuts on modal close
    Object.keys(allKeymaps).map((objectKey) => {
      const shortcut = allKeymaps[objectKey];

      keymaps.add(shortcut.id, shortcut.keys, shortcut.handler);
      return keymaps;
    });

    const modalContent = editor.Modal.getContent().querySelector('#dynamic-content-popup');

    // On modal close -> move editor within Mautic
    if (modalContent !== null) {
      const dynamicContentContainer = mQuery('#dynamicContentContainer');
      const content = mQuery(modalContent).contents().first();

      dynamicContentContainer.append(content.detach());
    }
  });

  editor.on('asset:add', () => {
    // Save assets list in textarea to keep new uploaded files without reload page
    builder.textareaAssets.val(JSON.stringify(builder.getAssetsList(editor)));
  });

  editor.on('asset:remove', (response) => {
    // Save assets list in textarea to keep new deleted files without reload page
    builder.textareaAssets.val(JSON.stringify(builder.getAssetsList(editor)));

    // Delete file on server
    mQuery.ajax({
      url: builder.textareaAssets.data('delete'),
      data: { filename: response.getFilename() },
    });
  });
};

Mautic.initGrapesJS = function (object) {
  // disable mautic global shortcuts
  Mousetrap.reset();
  let editor;

  const textareaHtml = mQuery('textarea.builder-html');
  const textareaAssets = mQuery('textarea#grapesjsbuilder_assets');
  const textareaMjml = mQuery('textarea.builder-mjml');
  console.warn({ textareaHtml });
  builder.setTextareas(textareaHtml, textareaAssets, textareaMjml);

  if (object === 'page') {
    editor = builder.initPage();
  } else if (object === 'emailform') {
    if (builder.textareaMjml.val().length) {
      editor = builder.initEmailMjml();
    } else {
      editor = builder.initEmailHtml();
    }
  } else {
    throw Error(`not supported builder type: ${object}`);
  }

  this.setListeners(editor);
};

/**
 * Set theme's HTML
 *
 * @param theme
 */
Mautic.setThemeHtml = function (theme) {
  builder.setupButtonLoadingIndicator(true);

  // Load template and fill field
  mQuery.ajax({
    url: mQuery('#builder_url').val(),
    data: `template=${theme}`,
    dataType: 'json',
    success(response) {
      builder.textareaHtml.val(response.templateHtml);

      if (typeof builder.textareaMjml !== 'undefined') {
        builder.textareaMjml.val(response.templateMjml);

        // If MJML template, generate HTML before save
        console.warn(typeof builder.textareaHtml);
        console.warn(builder.textareaHtml);
        if (!builder.textareaHtml.val().length && builder.textareaMjml.val().length) {
          builder.mjmlToHtml(builder.textareaMjml, builder.textareaHtml);
        }
      }
    },
    error(request, textStatus) {
      console.log(`setThemeHtml - Request failed: ${textStatus}`);
    },
    complete() {
      builder.setupButtonLoadingIndicator(false);
    },
  });
};

/**
 * Convert dynamic content tokens to slot and load content
 *
 * @param editor
 */
Mautic.grapesConvertDynamicContentTokenToSlot = function (editor) {
  const dc = editor.DomComponents;

  const dynamicContents = dc.getWrapper().find('[data-slot="dynamicContent"]');

  if (dynamicContents.length) {
    dynamicContents.forEach((dynamicContent) => {
      builder.manageDynamicContentTokenToSlot(dynamicContent);
    });
  }
};

/**
 * Convert dynamic content slots to tokens
 *
 * @param editor
 */
Mautic.grapesConvertDynamicContentSlotsToTokens = function (editor) {
  const dc = editor.DomComponents;

  const dynamicContents = dc.getWrapper().find('[data-slot="dynamicContent"]');

  if (dynamicContents.length) {
    dynamicContents.forEach((dynamicContent) => {
      const attributes = dynamicContent.getAttributes();
      const decId = attributes['data-param-dec-id'];

      // If it's not a token -> convert to token
      if (decId !== '') {
        const dynConId = `#emailform_dynamicContent_${attributes['data-param-dec-id']}`;

        const dynConTarget = mQuery(dynConId);
        const dynConName = dynConTarget.find(`${dynConId}_tokenName`).val();
        const dynConToken = `{dynamiccontent="${dynConName}"}`;

        // Clear id because it's reloaded by Mautic and this prevent slot to be destroyed by GrapesJs destroy event on close.
        dynamicContent.addAttributes({ 'data-param-dec-id': '' });
        dynamicContent.set('content', dynConToken);
      }
    });
  }
};
