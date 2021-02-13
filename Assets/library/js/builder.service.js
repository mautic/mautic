import '../../../Demo/node_modules/grapesjs/dist/css/grapes.min.css';
import grapesjs from 'grapesjs';
import grapesjsmjml from 'grapesjs-mjml';

const textareaHtml = mQuery('textarea.builder-html');
const textareaAssets = mQuery('textarea#grapesjsbuilder_assets');
const textareaMjml = mQuery('textarea.builder-mjml');

const assetManagerConf = {
  assets: JSON.parse(textareaAssets.val()),
  noAssets: Mautic.translate('grapesjsbuilder.assetManager.noAssets'),
  upload: textareaAssets.data('upload'),
  uploadName: 'files',
  multiUpload: true,
  embedAsBase64: false,
  openAssetsOnDrop: 1,
  autoAdd: true,
  headers: { 'X-CSRF-Token': mauticAjaxCsrf }, // global variable
};

const presetMauticConf = {
  sourceEditBtnLabel: Mautic.translate('grapesjsbuilder.sourceEditBtnLabel'),
  sourceCancelBtnLabel: Mautic.translate('grapesjsbuilder.sourceCancelBtnLabel'),
  sourceEditModalTitle: Mautic.translate('grapesjsbuilder.sourceEditModalTitle'),
  deleteAssetConfirmText: Mautic.translate('grapesjsbuilder.deleteAssetConfirmText'),
  categorySectionLabel: Mautic.translate('grapesjsbuilder.categorySectionLabel'),
  categoryBlockLabel: Mautic.translate('grapesjsbuilder.categoryBlockLabel'),
  dynamicContentBlockLabel: Mautic.translate('grapesjsbuilder.dynamicContentBlockLabel'),
  dynamicContentBtnLabel: Mautic.translate('grapesjsbuilder.dynamicContentBtnLabel'),
  dynamicContentModalTitle: Mautic.translate('grapesjsbuilder.dynamicContentModalTitle'),
};

// Redefine Keyboard shortcuts due to unbind won't works with multiple keys.
const keymapsConf = {
  defaults: {
    'core:undoios': {
      keys: '⌘+z',
      handler: 'core:undo',
    },
    'core:redoios': {
      keys: '⌘+shift+z',
      handler: 'core:redo',
    },
    'core:copyios': {
      keys: '⌘+c',
      handler: 'core:copy',
    },
    'core:pasteios': {
      keys: '⌘+v',
      handler: 'core:paste',
    },
    'core:undo': {
      keys: 'ctrl+z',
      handler: 'core:undo',
    },
    'core:redo': {
      keys: 'ctrl+shift+z',
      handler: 'core:redo',
    },
    'core:copy': {
      keys: 'ctrl+c',
      handler: 'core:copy',
    },
    'core:paste': {
      keys: 'ctrl+v',
      handler: 'core:paste',
    },
    'core:c-deletebackspace': {
      keys: 'backspace',
      handler: 'core:component-delete',
    },
    'core:c-deletesuppr': {
      keys: 'delete',
      handler: 'core:component-delete',
    },
  },
};

function initPage() {
  // PageBuilder
  // Parse HTML template
  const parser = new DOMParser();
  const fullHtml = parser.parseFromString(textareaHtml.val(), 'text/html');

  // Extract body
  const body = fullHtml.body.innerHTML;

  // Launch GrapesJS with body part
  const editor = grapesjs.init({
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
        formsOpts: false,
      },
      'grapesjs-preset-mautic': presetMauticConf,
    },
    keymaps: keymapsConf,
  });

  // Customize GrapesJS -> add close button with save for Mautic
  const panelManager = editor.Panels;
  panelManager.addButton('views', [
    {
      id: 'close',
      className: 'fa fa-times-circle',
      attributes: { title: 'Close' },
      command() {
        Mautic.grapesConvertDynamicContentSlotsToTokens(editor);

        // Update textarea for save
        fullHtml.body.innerHTML = `${editor.getHtml()}<style>${editor.getCss({
          avoidProtected: true,
        })}</style>`;
        textareaHtml.val(fullHtml.documentElement.outerHTML);

        // Reset HTML
        mQuery('.builder').removeClass('builder-active').addClass('hide');
        mQuery('html').css('font-size', '');
        mQuery('body').css('overflow-y', '');

        // Destroy GrapesJS
        editor.destroy();
      },
    },
  ]);

  return editor;
}
function initEmailMjml() {
  // EmailBuilder -> MJML
  const editor = grapesjs.init({
    clearOnRender: true,
    container: '.builder-panel',
    components: textareaMjml.val(),
    height: '100%',
    storageManager: false,
    assetManager: assetManagerConf,

    plugins: [grapesjsmjml, 'grapesjs-parser-postcss', 'grapesjs-preset-mautic'],
    pluginsOpts: {
      'grapesjs-mjml': {},
      'grapesjs-preset-mautic': presetMauticConf,
    },
    keymaps: keymapsConf,
  });

  editor.BlockManager.get('mj-button').set({
    content: '<mj-button href="https://">Button</mj-button>',
  });

  // Customize GrapesJS -> add close button with save for Mautic
  const panelManager = editor.Panels;
  panelManager.addButton('views', [
    {
      id: 'close',
      className: 'fa fa-times-circle',
      attributes: { title: 'Close' },
      command() {
        Mautic.grapesConvertDynamicContentSlotsToTokens(editor);

        let code = '';

        // Try catch for mjml parser error
        try {
          code = editor.runCommand('mjml-get-code');
        } catch (error) {
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
      },
    },
  ]);

  return editor;
}

function initEmailHtml() {
  // EmailBuilder -> HTML
  // Parse HTML template
  const parser = new DOMParser();
  const fullHtml = parser.parseFromString(textareaHtml.val(), 'text/html');

  // Extract body
  const body = fullHtml.body.innerHTML;

  // Launch GrapesJS with body part
  const editor = grapesjs.init({
    clearOnRender: true,
    container: '.builder-panel',
    components: body,
    height: '100%',
    storageManager: false,
    assetManager: assetManagerConf,

    plugins: ['gjs-preset-newsletter', 'grapesjs-parser-postcss', 'grapesjs-preset-mautic'],
    pluginsOpts: {
      'gjs-preset-newsletter': {},
      'grapesjs-preset-mautic': presetMauticConf,
    },
    keymaps: keymapsConf,
  });

  editor.BlockManager.get('button').set({
    content:
      '<a href="#" target="_blank" style="display:inline-block;text-decoration:none;border-color:#4e5d9d;border-width: 10px 20px;border-style:solid; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; background-color: #4e5d9d; display: inline-block;font-size: 16px; color: #ffffff; ">\n' +
      'Button\n' +
      '</a>',
  });

  // Customize GrapesJS -> add close button with save for Mautic
  const panelManager = editor.Panels;
  panelManager.addButton('views', [
    {
      id: 'close',
      className: 'fa fa-times-circle',
      attributes: { title: 'Close' },
      command() {
        Mautic.grapesConvertDynamicContentSlotsToTokens(editor);

        // Update textarea for save
        fullHtml.body.innerHTML = editor.runCommand('gjs-get-inlined-html');
        textareaHtml.val(fullHtml.documentElement.outerHTML);

        // Reset HTML
        mQuery('.builder').removeClass('builder-active').addClass('hide');
        mQuery('html').css('font-size', '');
        mQuery('body').css('overflow-y', '');

        // Destroy GrapesJS
        editor.destroy();
      },
    },
  ]);

  return editor;
}
/**
 * Delete DC on Mautic side
 *
 * @param component
 */
function deleteDynamicContentItem(component) {
  const attributes = component.getAttributes();

  // Only delete if we click on trash, not when GrapesJs is destroy
  if (attributes['data-param-dec-id'] !== '') {
    const dynConId = `#emailform_dynamicContent_${attributes['data-param-dec-id']}`;
    const dynConTarget = mQuery(dynConId);

    if (typeof dynConTarget !== 'undefined') {
      dynConTarget.find('a.remove-item:first').click();
      // remove vertical tab in outside form
      mQuery('.dynamicContentFilterContainer').find(`a[href=${dynConId}]`).parent().remove();
    }
  }
}

/**
 * Init GrapesJS to generate HTML
 *
 * @param source - Textarea where MJML is stored
 * @param destination - Textarea where HTML will be stored
 * @param container - Invisible container to init GrapesJS
 */
function mjmlToHtml(source, destination, container) {
  const containerName = container || '.builder-panel';

  let code = '';
  const editor = grapesjs.init({
    clearOnRender: true,
    containerName,
    components: source.val(),
    storageManager: false,
    panels: { defaults: [] },

    plugins: ['grapesjs-mjml', 'grapesjs-parser-postcss'],
    pluginsOpts: {
      'grapesjs-mjml': {},
    },
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
}

/**
 * Manage button loading indicator
 *
 * @param activate - true or false
 */
function setupButtonLoadingIndicator(activate) {
  const builderButton = mQuery('.btn-builder');
  const saveButton = mQuery('.btn-save');
  const applyButton = mQuery('.btn-apply');

  if (activate) {
    Mautic.activateButtonLoadingIndicator(builderButton);
    Mautic.activateButtonLoadingIndicator(saveButton);
    Mautic.activateButtonLoadingIndicator(applyButton);
  } else {
    Mautic.removeButtonLoadingIndicator(builderButton);
    Mautic.removeButtonLoadingIndicator(saveButton);
    Mautic.removeButtonLoadingIndicator(applyButton);
  }
}

/**
 * Generate assets list from GrapesJs
 *
 * @param editor
 */
function getAssetsList(editor) {
  const assetManager = editor.AssetManager;
  const assets = assetManager.getAll();
  const assetsList = [];

  assets.forEach((asset) => {
    if (asset.get('type') === 'image') {
      assetsList.push({
        src: asset.get('src'),
        width: asset.get('width'),
        height: asset.get('height'),
      });
    } else {
      assetsList.push(asset.get('src'));
    }
  });

  return assetsList;
}

function manageDynamicContentTokenToSlot(component) {
  const regex = RegExp(/\{dynamiccontent="(.*)"\}/, 'g');

  const content = component.get('content');
  const regexEx = regex.exec(content);

  if (regexEx !== null) {
    const dynConName = regexEx[1];
    const dynConTabA = mQuery('#dynamicContentTabs a').filter(function () {
      return mQuery(this).text().trim() === dynConName;
    });

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
      }

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
  }
}
module.exports = {
  initPage,
  initEmailHtml,
  initEmailMjml,
  deleteDynamicContentItem,
  manageDynamicContentTokenToSlot,
  mjmlToHtml,
  getAssetsList,
  setupButtonLoadingIndicator,
};
