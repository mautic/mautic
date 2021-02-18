import grapesjs from 'grapesjs';
import grapesjsmjml from 'grapesjs-mjml';
import grapesjsnewsletter from 'grapesjs-preset-newsletter';
import grapesjswebpage from 'grapesjs-preset-webpage';
import grapesjspostcss from 'grapesjs-parser-postcss';
import grapesjsmautic from './grapesjs-preset-mautic.min';

export default class BuilderService {
  assetManagerConf;

  presetMauticConf;

  editor;

  // components that are on the canvas
  canvasContent;

  textareaAssets;

  textareaHtml;

  textareaMjml;

  // Redefine Keyboard shortcuts due to unbind won't works with multiple keys.
  keymapsConf = {
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

  constructor(content) {
    console.warn(content);
    this.canvasContent = content;
  }

  /**
   * Initialize GrapesJsBuilder
   *
   * @param object
   */
  setListeners() {
    if (!this.editor) {
      throw Error('No editor found');
    }
    this.editor.on('load', () => {
      const um = this.editor.UndoManager;

      Mautic.grapesConvertDynamicContentTokenToSlot(this.editor);

      // Clear stack of undo/redo
      um.clear();
    });

    this.editor.on('component:add', (component) => {
      const type = component.get('type');

      // Create dynamic-content on Mautic side
      if (type === 'dynamic-content') {
        manageDynamicContentTokenToSlot(component);
      }
    });

    this.editor.on('component:remove', (component) => {
      const type = component.get('type');

      // Delete dynamic-content on Mautic side
      if (type === 'dynamic-content') {
        this.deleteDynamicContentItem(component);
      }
    });

    const keymaps = this.editor.Keymaps;
    let allKeymaps;

    this.editor.on('modal:open', () => {
      // Save all keyboard shortcuts
      allKeymaps = { ...keymaps.getAll() };

      // Remove keyboard shortcuts to prevent launch behind popup
      keymaps.removeAll();
    });

    this.editor.on('modal:close', () => {
      const commands = this.editor.Commands;
      const cmdCodeEdit = 'preset-mautic:code-edit';
      const cmdDynamicContent = 'preset-mautic:dynamic-content';

      // Launch preset-mautic:code-edit command stop
      if (commands.isActive(cmdCodeEdit)) {
        commands.stop(cmdCodeEdit, { editor: this.editor });
      }

      // Launch preset-mautic:dynamic-content command stop
      if (commands.isActive(cmdDynamicContent)) {
        commands.stop(cmdDynamicContent, { editor: this.editor });
      }

      // ReMap keyboard shortcuts on modal close
      Object.keys(allKeymaps).map((objectKey) => {
        const shortcut = allKeymaps[objectKey];

        keymaps.add(shortcut.id, shortcut.keys, shortcut.handler);
        return keymaps;
      });

      const modalContent = mQuery('#dynamic-content-popup');

      // On modal close -> move editor within Mautic
      if (modalContent) {
        const dynamicContentContainer = mQuery('#dynamicContentContainer');
        const content = mQuery(modalContent).contents().first();

        dynamicContentContainer.append(content.detach());
      }
    });

    this.editor.on('asset:add', () => {
      // Save assets list in textarea to keep new uploaded files without reload page
      builder.textareaAssets.val(JSON.stringify(builder.getAssetsList()));
    });

    this.editor.on('asset:remove', (response) => {
      // Save assets list in textarea to keep new deleted files without reload page
      builder.textareaAssets.val(JSON.stringify(builder.getAssetsList()));

      // Delete file on server
      mQuery.ajax({
        url: builder.textareaAssets.data('delete'),
        data: { filename: response.getFilename() },
      });
    });
  }

  initGrapesJS(object) {
    // disable mautic global shortcuts
    Mousetrap.reset();

    if (object === 'page') {
      this.editor = this.initPage();
    } else if (object === 'emailform') {
      if (this.textareaMjml.val().length) {
        this.editor = this.initEmailMjml();
      } else {
        this.editor = this.initEmailHtml();
      }
    } else {
      throw Error(`not supported builder type: ${object}`);
    }

    this.setListeners(this.editor);
  }

  setTextareas(textareaHtml, textareaAssets, textareaMjml) {
    if (!textareaHtml || !textareaAssets || !textareaMjml) {
      console.debug('not all textareas loaded');
    }

    this.textareaHtml = textareaHtml;
    this.textareaMjml = textareaMjml;
    this.textareaAssets = textareaAssets;
  }

  getHtmlValue() {
    if (this.textareaHtml && this.textareaHtml.val() && this.textareaHtml.val().length > 0) {
      return this.textareaHtml.val();
    }
    return null;
  }

  getMjmlValue() {
    if (this.textareaMjml && this.textareaMjml.val() && this.textareaMjml.val().length > 0) {
      return this.textareaMjml.val();
    }
    return null;
  }

  getAssetValue() {
    if (this.textareaAssets && this.textareaAssets.val() && this.textareaAssets.val().length > 0) {
      return this.textareaAssets.val();
    }
    return null;
  }

  setAssetManagerConf() {
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

  setPresetMauticConf() {
    console.log(Mautic);
    this.presetMauticConf = {
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

  initPage() {
    console.warn('this.keymapsConf ');
    console.warn(this.keymapsConf);
    // Launch GrapesJS with body part
    this.editor = grapesjs.init({
      clearOnRender: true,
      container: '.builder-panel',
      components: this.canvasContent,
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
    this.getCloseButtonPage();
    return this.editor;
  }

  initEmailMjml() {
    // EmailBuilder -> MJML

    this.editor = grapesjs.init({
      clearOnRender: true,
      container: '.builder-panel',
      components: this.canvasContent,
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

    this.editor.BlockManager.get('mj-button').set({
      content: '<mj-button href="https://">Button</mj-button>',
    });

    this.getCloseButtonMjml();
    return this.editor;
  }

  initEmailHtml() {
    // Launch GrapesJS with body part
    this.editor = grapesjs.init({
      clearOnRender: true,
      container: '.builder-panel',
      components: this.canvasContent,
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

    // add a Mautic custom block Button
    this.editor.BlockManager.get('button').set({
      content:
        '<a href="#" target="_blank" style="display:inline-block;text-decoration:none;border-color:#4e5d9d;border-width: 10px 20px;border-style:solid; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; background-color: #4e5d9d; display: inline-block;font-size: 16px; color: #ffffff; ">\n' +
        'Button\n' +
        '</a>',
    });

    // Customize GrapesJS -> add close button with save for Mautic
    this.getCloseButtonHtml();
    return this.editor;
  }

  /**
   * Convert dynamic content slots to tokens
   *
   * @param editor
   */
  grapesConvertDynamicContentSlotsToTokens() {
    const dc = this.editor.DomComponents;

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
  }

  /**
   * Customize GrapesJS -> add close button with save for Mautic in the Page Builder Mode
   */
  getCloseButtonPage() {
    const parser = new DOMParser();
    const fullHtml = parser.parseFromString(this.getHtmlValue(), 'text/html');

    const commands = this.editor.Commands;
    commands.add('mautic:editor:close', (editor) => {
      this.grapesConvertDynamicContentSlotsToTokens(editor);

      // Update textarea for save (part that is different from other modes)
      fullHtml.body.innerHTML = `${editor.getHtml()}<style>${editor.getCss({
        avoidProtected: true,
      })}</style>`;
      mQuery('textarea.builder-html').val(fullHtml.documentElement.outerHTML);

      // Reset HTML
      mQuery('.builder').removeClass('builder-active').addClass('hide');
      mQuery('html').css('font-size', '');
      mQuery('body').css('overflow-y', '');

      //   // Destroy GrapesJS
      this.editor.destroy();
    });

    this.editor.Panels.addButton('views', [
      {
        id: 'close',
        className: 'fa fa-times-circle',
        attributes: { title: 'Close' },
        command: 'mautic:editor:close',
        // command() {
        //   this.grapesConvertDynamicContentSlotsToTokens(this.editor);

        //   // Update textarea for save (part that is different from other modes)
        //   fullHtml.body.innerHTML = `${this.editor.getHtml()}<style>${this.editor.getCss({
        //     avoidProtected: true,
        //   })}</style>`;
        //   mQuery('textarea.builder-html').val(fullHtml.documentElement.outerHTML);

        //   // Reset HTML
        //   mQuery('.builder').removeClass('builder-active').addClass('hide');
        //   mQuery('html').css('font-size', '');
        //   mQuery('body').css('overflow-y', '');

        //   // Destroy GrapesJS
        //   this.editor.destroy();
        // },
      },
    ]);
  }

  /**
   * Customize GrapesJS -> add close button with save for Mautic
   */
  getCloseButtonMjml() {
    this.editor.Panels.addButton('views', [
      {
        id: 'close',
        className: 'fa fa-times-circle',
        attributes: { title: 'Close' },
        command() {
          this.grapesConvertDynamicContentSlotsToTokens(this.editor);

          let code = '';

          // Try catch for mjml parser error
          try {
            code = this.editor.runCommand('mjml-get-code');
          } catch (error) {
            console.log(error.message);
            alert('Errors inside your template. Template will not be saved.');
          }

          // Update textarea for save (only individual part)
          if (!code.length) {
            mQuery('textarea.builder-html').val(code.html);
            mQuery('textarea.builder-mjml').val(this.editor.getHtml());
          }

          // Reset HTML
          // mQuery('.builder').removeClass('builder-active').addClass('hide');
          mQuery('html').css('font-size', '');
          mQuery('body').css('overflow-y', '');

          // Destroy GrapesJS
          this.editor.destroy();
        },
      },
    ]);

    return this.editor.Panels;
  }

  /**
   * Get a custom close button for the Mautic Email mode where the template is HTML
   */
  getCloseButtonHtml() {
    const parser = new DOMParser();
    const fullHtml = parser.parseFromString(this.getHtmlValue(), 'text/html');

    this.editor.Panels.addButton('views', [
      {
        id: 'close',
        className: 'fa fa-times-circle',
        attributes: { title: 'Close' },
        command() {
          this.grapesConvertDynamicContentSlotsToTokens(this.editor);

          // Update textarea for save
          fullHtml.body.innerHTML = this.editor.runCommand('gjs-get-inlined-html');
          mQuery('textarea.builder-html').val(fullHtml.documentElement.outerHTML);

          // Reset HTML
          // mQuery('.builder').removeClass('builder-active').addClass('hide');
          mQuery('html').css('font-size', '');
          mQuery('body').css('overflow-y', '');

          // Destroy GrapesJS
          this.editor.destroy();
        },
      },
    ]);
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

      if (dynConTarget) {
        dynConTarget.find('a.remove-item:first').click();
        // remove vertical tab in outside form
        const dynCon = mQuery('.dynamicContentFilterContainer').find(`a[href=${dynConId}]`);
        if (dynCon && dynCon.parent()) {
          dynCon.parent().remove();
        }
      }
    }
  }

  /**
   * Init GrapesJS to generate HTML
   *
   * @param mjmlTextarea - Textarea where MJML is stored
   * @param htmlTextarea - Textarea where HTML will be stored
   * @param container - Invisible container to init GrapesJS
   */
  // mjmlToHtml(mjmlTextarea, htmlTextarea, container = '.builder-panel') {
  //   console.warn(mjmlTextarea);
  //   console.warn(htmlTextarea);
  //   console.warn(container);

  //   let code = '';
  //   this.editor = grapesjs.init({
  //     clearOnRender: true,
  //     container,
  //     components: mjmlTextarea.val(),
  //     storageManager: false,
  //     panels: { defaults: [] },

  //     plugins: [grapesjsmjml, grapesjspostcss],
  //     pluginsOpts: {
  //       grapesjsmjml: {},
  //     },
  //   });
  //   console.log(this.editor);
  //   // Try catch for MJML parser error
  //   try {
  //     code = this.editor.runCommand('mjml-get-code');
  //   } catch (error) {
  //     console.log(error.message);
  //     alert('Errors inside your template. Template will not be saved.');
  //   }

  //   // Set result to htmlTextarea
  //   if (!code.length) {
  //     htmlTextarea.val(code.html);
  //   }

  //   // Destroy GrapesJS
  //   this.editor.destroy();

  // try {
  //   editor.destroy();
  // } catch (error) {
  //   console.log(error);
  // }
  // }

  /**
   * Manage button loading indicator
   *
   * @param activate - true or false
   */
  static setupButtonLoadingIndicator(activate) {
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
   */
  getAssetsList() {
    const assetManager = this.editor.AssetManager;
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
}
