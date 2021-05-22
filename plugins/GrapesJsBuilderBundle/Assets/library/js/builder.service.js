import grapesjs from 'grapesjs';
import grapesjsmjml from 'grapesjs-mjml';
import grapesjsnewsletter from 'grapesjs-preset-newsletter';
import grapesjswebpage from 'grapesjs-preset-webpage';
import grapesjspostcss from 'grapesjs-parser-postcss';
// @todo set to grapesjs preset path in node_modules
// import grapesjsmautic from 'grapesjs-preset-mautic/src';
import grapesjsmautic from '../../../../../../grapesjs-preset-mautic/src';

export default class BuilderService {
  presetMauticConf;

  editor;

  // components that are on the canvas
  canvasContent;

  assets;

  uploadPath;

  deletePath;

  // HTMLHeadElement
  head;

  constructor(content, assets, uploadPath, deletePath, head) {
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
    this.head = head;
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

      // Clear stack of undo/redo
      um.clear();
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

      // Launch preset-mautic:code-edit command stop
      if (commands.isActive(cmdCodeEdit)) {
        commands.stop(cmdCodeEdit, { editor: this.editor });
      }

      // ReMap keyboard shortcuts on modal close
      Object.keys(allKeymaps).map((objectKey) => {
        const shortcut = allKeymaps[objectKey];

        keymaps.add(shortcut.id, shortcut.keys, shortcut.handler);
        return keymaps;
      });
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
    const styles = this.getStyles();
    // Launch GrapesJS with body part
    this.editor = grapesjs.init({
      clearOnRender: true,
      container: '.builder-panel',
      components: this.canvasContent,
      height: '100%',
      canvas: {
        styles,
      },
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

    return this.editor;
  }

  /**
   * Extract all stylesheets from the template <head>
   */
  getStyles() {
    if (!this.head) {
      return [];
    }
    const children = this.head.querySelectorAll('link');
    const styles = [];

    children.forEach((link) => {
      if (link && link.rel === 'stylesheet') {
        styles.push(link.href);
      }
    });

    return styles;
  }

  /**
   * Add Mautic specific commands
   */
  addMauticCommands() {
    if (!this.editor) {
      throw Error('No editor found');
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
