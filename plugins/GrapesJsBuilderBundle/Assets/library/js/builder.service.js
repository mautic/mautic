import grapesjs from 'grapesjs';
import grapesjsmjml from 'grapesjs-mjml';
import grapesjsnewsletter from 'grapesjs-preset-newsletter';
import grapesjswebpage from 'grapesjs-preset-webpage';
import grapesjspostcss from 'grapesjs-parser-postcss';
import grapesjsmautic from 'grapesjs-preset-mautic/src';

export default class BuilderService {
  presetMauticConf;

  editor;

  // components that are on the canvas
  canvasContent;

  assets;

  uploadPath;

  deletePath;

  constructor(content, assets, uploadPath, deletePath) {
    if (!content) {
      throw Error('No HTML or MJML content found');
    }
    if (!uploadPath) {
      throw Error('No uploadPath found');
    }
    if (!deletePath) {
      throw Error('No deletePath found');
    }
    if (!assets || !assets[0]) {
      console.warn('no assets');
    }
    this.canvasContent = content;
    this.assets = assets;
    this.uploadPath = uploadPath;
    this.deletePath = deletePath;
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

    this.editor.on('run:mautic-editor-email-mjml-close:before', () => {
      mQuery('textarea.builder-html').val(this.canvasContent);
    });

    this.editor.on('load', () => {
      const um = this.editor.UndoManager;

      this.constructor.grapesConvertDynamicContentTokenToSlot(this.editor);

      // Clear stack of undo/redo
      um.clear();
    });

    this.editor.on('component:add', (component) => {
      const type = component.get('type');

      // Create dynamic-content on Mautic side
      if (type === 'dynamic-content') {
        this.constructor.manageDynamicContentTokenToSlot(component);
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

    this.editor.on('asset:remove', (response) => {
      // Delete file on server
      mQuery.ajax({
        url: this.deletePath,
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
      if (this.canvasContent && this.canvasContent.indexOf('<mjml>') !== -1) {
        this.editor = this.initEmailMjml();
      } else {
        this.editor = this.initEmailHtml();
      }
    } else {
      throw Error(`not supported builder type: ${object}`);
    }

    this.addMauticCommands();
    this.setListeners();
  }

  setPresetMauticConf() {
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
    // Launch GrapesJS with body part
    this.editor = grapesjs.init({
      clearOnRender: true,
      container: '.builder-panel',
      components: this.canvasContent,
      height: '100%',
      storageManager: false, // https://grapesjs.com/docs/modules/Storage.html#basic-configuration
      assetManager: this.getAssetManagerConf(),
      styleManager: {
        clearProperties: true, // Temp fix https://github.com/artf/grapesjs-preset-webpage/issues/27
      },
      plugins: [grapesjswebpage, grapesjspostcss, grapesjsmautic],
      pluginsOpts: {
        [grapesjswebpage]: {
          formsOpts: false,
        },
        grapesjsmautic: this.presetMauticConf,
      },
    });

    // Customize GrapesJS -> add close button with save for Mautic
    this.getCloseButton('mautic-editor-page-html-close');
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
      assetManager: this.getAssetManagerConf(),

      plugins: [grapesjsmjml, grapesjspostcss, grapesjsmautic],
      pluginsOpts: {
        grapesjsmjml: {},
        grapesjsmautic: this.presetMauticConf,
      },
    });

    this.editor.BlockManager.get('mj-button').set({
      content: '<mj-button href="https://">Button</mj-button>',
    });

    this.getCloseButton('mautic-editor-email-mjml-close');
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
      assetManager: this.getAssetManagerConf(),

      plugins: [grapesjsnewsletter, grapesjspostcss, grapesjsmautic],
      pluginsOpts: {
        grapesjsnewsletter: {},
        grapesjsmautic: this.presetMauticConf,
      },
    });

    // add a Mautic custom block Button
    this.editor.BlockManager.get('button').set({
      content:
        '<a href="#" target="_blank" style="display:inline-block;text-decoration:none;border-color:#4e5d9d;border-width: 10px 20px;border-style:solid; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; background-color: #4e5d9d; display: inline-block;font-size: 16px; color: #ffffff; ">\n' +
        'Button\n' +
        '</a>',
    });

    // Customize GrapesJS -> add close button with save for Mautic
    this.getCloseButton('mautic-editor-email-html-close');
    return this.editor;
  }

  /**
   * Convert dynamic content slots to tokens
   * Used in grapesjs-preset-mautic
   *
   * @param editor
   */
  static grapesConvertDynamicContentSlotsToTokens(editor) {
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
  }

  /**
   * Add Mautic specific commands
   */
  addMauticCommands() {
    if (!this.editor) {
      throw Error('No editor found');
    }
    const parser = new DOMParser();
    const fullHtml = parser.parseFromString(this.canvasContent, 'text/html');
    const commands = this.editor.Commands;

    commands.add('mautic-editor-page-html-close', (editor) => {
      if (!editor) {
        throw new Error('no page-html editor');
      }
      this.constructor.grapesConvertDynamicContentSlotsToTokens(editor);

      // Update textarea for save (part that is different from other modes)
      fullHtml.body.innerHTML = `${editor.getHtml()}<style>${editor.getCss({
        avoidProtected: true,
      })}</style>`;
      mQuery('textarea.builder-html').val(fullHtml.documentElement.outerHTML);

      // Reset HTML
      BuilderService.resetHtml(editor);
    });

    commands.add('mautic-editor-email-html-close', (editor) => {
      if (!editor) {
        throw new Error('no email-html editor');
      }
      this.constructor.grapesConvertDynamicContentSlotsToTokens(editor);

      // Update textarea for save
      fullHtml.body.innerHTML = editor.runCommand('gjs-get-inlined-html');
      mQuery('textarea.builder-html').val(fullHtml.documentElement.outerHTML);

      // Reset HTML
      BuilderService.resetHtml(editor);
    });

    commands.add('mautic-editor-email-mjml-close', (editor) => {
      if (!editor) {
        throw new Error('no email-mjml editor');
      }
      this.constructor.grapesConvertDynamicContentSlotsToTokens(editor);

      let code = '';

      // Try catch for mjml parser error
      try {
        code = this.editor.runCommand('mjml-get-code');
      } catch (error) {
        console.log(error.message);
        alert('Errors inside your template. Template will not be saved.');
      }

      // Update textarea for save
      if (!code.length) {
        mQuery('textarea.builder-html').val(code.html);
        mQuery('textarea.builder-mjml').val(editor.getHtml());
      }

      // Reset HTML
      BuilderService.resetHtml(editor);
    });
  }

  static manageDynamicContentTokenToSlot(component) {
    const regex = RegExp(/\{dynamiccontent="(.*)"\}/, 'g');

    const content = component.get('content');
    const regexEx = regex.exec(content);

    // abort if component does not contain a dynamic content element
    if (regexEx === null) {
      return null;
    }

    const dynContenName = regexEx[1];
    const dynContentTabA = mQuery('#dynamicContentTabs a').filter(
      () => mQuery(this).text().trim() === dynContenName
    );

    if (typeof dynContentTabA !== 'undefined' && dynContentTabA.length) {
      // If dynamic content item exists -> fill
      const dynContentTarget = dynContentTabA.attr('href');
      let dynConContent = '';

      if (mQuery(dynContentTarget).html()) {
        const dynConContainer = mQuery(dynContentTarget).find(`${dynContentTarget}_content`);

        if (dynConContainer.hasClass('editor')) {
          dynConContent = dynConContainer.froalaEditor('html.get');
        } else {
          dynConContent = dynConContainer.html();
        }
      }

      if (dynConContent === '') {
        dynConContent = dynContentTabA.text();
      }

      component.addAttributes({
        'data-param-dec-id': parseInt(dynContentTarget.replace(/[^0-9]/g, ''), 10),
      });
      component.set('content', dynConContent);
    } else {
      // If dynamic content item doesn't exist -> create
      const dynConTarget = Mautic.createNewDynamicContentItem(mQuery);
      const dynConTab = mQuery('#dynamicContentTabs').find(`a[href="${dynConTarget}"]`);

      component.addAttributes({
        'data-param-dec-id': parseInt(dynConTarget.replace(/[^0-9]/g, ''), 10),
      });
      component.set('content', dynConTab.text());
    }
    return true;
  }

  /**
   * Convert dynamic content tokens to slot and load content
   * Used in grapesjs-preset-mautic
   */
  static grapesConvertDynamicContentTokenToSlot(editor) {
    const dc = editor.DomComponents;

    const dynamicContents = dc.getWrapper().find('[data-slot="dynamicContent"]');

    if (dynamicContents.length) {
      dynamicContents.forEach((dynamicContent) => {
        Mautic.manageDynamicContentTokenToSlot(dynamicContent);
      });
    }
  }

  static resetHtml(editor) {
    mQuery('.builder').removeClass('builder-active').addClass('hide');
    mQuery('html').css('font-size', '');
    mQuery('body').css('overflow-y', '');

    // Destroy GrapesJS
    // workingn workaround: throws typeError: Cannot read property 'trigger'
    // since editior is destroyed, command can not be stopped anymore
    mQuery('.builder-panel').css('display', 'none');
    setTimeout(() => editor.destroy(), 1000);
    // editor.destroy();
  }

  /**
   * Add close button with save for Mautic
   */
  getCloseButton(command) {
    if (!command) {
      throw new Error('no close button command');
    }

    this.editor.Panels.addButton('views', [
      {
        id: 'close',
        className: 'fa fa-times-circle',
        attributes: { title: 'Close' },
        command,
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
   * Configure the Asset Manager for all modes
   * @link https://grapesjs.com/docs/modules/Assets.html#configuration
   */
  getAssetManagerConf() {
    return {
      assets: this.assets,
      noAssets: Mautic.translate('grapesjsbuilder.assetManager.noAssets'),
      upload: this.uploadPath,
      uploadName: 'files',
      multiUpload: 1,
      embedAsBase64: false,
      openAssetsOnDrop: 1,
      autoAdd: 1,
      headers: { 'X-CSRF-Token': mauticAjaxCsrf }, // global variable
    };
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
