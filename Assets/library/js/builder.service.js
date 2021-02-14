import grapesjs from 'grapesjs';
import grapesjsmjml from 'grapesjs-mjml';
import grapesjsnewsletter from 'grapesjs-preset-newsletter';
import grapesjswebpage from 'grapesjs-preset-webpage';
import grapesjspostcss from 'grapesjs-parser-postcss';
import grapesjsmautic from './grapesjs-preset-mautic.min'

export default class BuilderService {
  static assetManagerConf;

  static presetMauticConf;

  textareaAssets;

  textareaHtml;

  textareaMjml;

  // Redefine Keyboard shortcuts due to unbind won't works with multiple keys.
  static keymapsConf = {
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

  static setTextareas(textareaHtml, textareaAssets, textareaMjml) {
    if (
      !textareaHtml ||
      !textareaAssets ||
      !textareaMjml
    ) {
      console.debug('not all textareas loaded');
    }

    this.textareaHtml = textareaHtml;
    this.textareaMjml = textareaMjml;
    this.textareaAssets = textareaAssets;
  }

  static getHtmlValue(){
    if ( this.textareaHtml && this.textareaHtml.val() && this.textareaHtml.val().length > 0 ){
      return this.textareaHtml.val();
    }
    return null;
  }

  static getMjmlValue(){
    if ( this.textareaMjml && this.textareaMjml.val() && this.textareaMjml.val().length > 0 ){
      return this.textareaMjml.val();
    }
    return null;
  }

  static getAssetValue(){
    if ( this.textareaAssets && this.textareaAssets.val() && this.textareaAssets.val().length > 0 ){
      return this.textareaAssets.val();
    }
    return null;
  }

  static setAssetManagerConf() {
    this.assetManagerConf = {
      assets: JSON.parse(this.getAssetValue()),
      noAssets: Mautic.translate('grapesjsbuilder.assetManager.noAssets'),
      upload: this.textareaAssets.data('upload'),
      uploadName: 'files',
      multiUpload: true,
      embedAsBase64: false,
      openAssetsOnDrop: 1,
      autoAdd: true,
      headers: { 'X-CSRF-Token': mauticAjaxCsrf }, // global variable
    };
  }

  static setPresetMauticConf() {
    this.setpresetMauticConf = {
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
  }

  static initPage() {
    // PageBuilder
    // Parse HTML template
    const parser = new DOMParser();
    const fullHtml = parser.parseFromString(this.getHtmlValue(), 'text/html');

    // Extract body
    const body = fullHtml.body.innerHTML;

    // Launch GrapesJS with body part
    const editor = grapesjs.init({
      clearOnRender: true,
      container: '.builder-panel',
      components: body,
      height: '100%',
      storageManager: false,
      assetManager: this.assetManagerConf,
      styleManager: {
        clearProperties: true, // Temp fix https://github.com/artf/grapesjs-preset-webpage/issues/27
      },

      plugins: [grapesjswebpage, grapesjspostcss, grapesjsmautic],
      pluginsOpts: {
        grapesjswebpage: {
          formsOpts: false,
        },
        grapesjsmautic: this.presetMauticConf,
      },
      keymaps: this.keymapsConf,
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
          this.getHtmlValue(fullHtml.documentElement.outerHTML);

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

  static initEmailMjml() {
    // EmailBuilder -> MJML
    const editor = grapesjs.init({
      clearOnRender: true,
      container: '.builder-panel',
      components: this.getMjmlValue(),
      height: '100%',
      storageManager: false,
      assetManager: this.assetManagerConf,

      plugins: [grapesjsmjml, grapesjspostcss, grapesjsmautic],
      pluginsOpts: {
        grapesjsmjml: {},
        grapesjsmautic: this.presetMauticConf,
      },
      keymaps: this.keymapsConf,
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
            this.textareaHtml.val(code.html);
            this.textareaMjml.val(editor.getHtml());
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

  static initEmailHtml() {
    // EmailBuilder -> HTML
    // Parse HTML template
    const parser = new DOMParser();
    const fullHtml = parser.parseFromString(this.getHtmlValue(), 'text/html');

    // Extract body
    const body = fullHtml.body.innerHTML;

    // Launch GrapesJS with body part
    const editor = grapesjs.init({
      clearOnRender: true,
      container: '.builder-panel',
      components: body,
      height: '100%',
      storageManager: false,
      assetManager: this.assetManagerConf,

      plugins: [grapesjsnewsletter, grapesjspostcss, grapesjsmautic],
      pluginsOpts: {
        grapesjsnewsletter: {},
        grapesjsmautic: this.presetMauticConf,
      },
      keymaps: this.keymapsConf,
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
          this.textareaHtml.val(fullHtml.documentElement.outerHTML);

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
  static deleteDynamicContentItem(component) {
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
  static mjmlToHtml(source, destination, container) {
    const containerName = container || '.builder-panel';

    let code = '';
    const editor = grapesjs.init({
      clearOnRender: true,
      containerName,
      components: source.val(),
      storageManager: false,
      panels: { defaults: [] },

      plugins: [grapesjsmjml, grapesjspostcss],
      pluginsOpts: {
        grapesjsmjml: {},
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
  static setupButtonLoadingIndicator(activate) {
    const builderButton = mQuery('.btn-builder');
    const saveButton = mQuery('.btn-save');
    const applyButton = mQuery('.btn-apply');
    console.warn(this.textareaHtml );
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
  static getAssetsList(editor) {
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

  static manageDynamicContentTokenToSlot(component) {
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
}
