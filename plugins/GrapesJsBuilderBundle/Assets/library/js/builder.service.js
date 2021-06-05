import grapesjs from 'grapesjs';
import grapesjsmjml from 'grapesjs-mjml';
import grapesjsnewsletter from 'grapesjs-preset-newsletter';
import grapesjswebpage from 'grapesjs-preset-webpage';
import grapesjspostcss from 'grapesjs-parser-postcss';
import grapesjsmautic from '../../../../../../grapesjs-preset-mautic/src';
// import grapesjsmautic from 'grapesjs-preset-mautic';
import ContentService from './content.service';
import CodeModeButton from './codeMode/codeMode.button';

export default class BuilderService {
  editor;

  assets;

  uploadPath;

  deletePath;

  /**
   * @param {*} assets
   */
  constructor(assets) {
    if (!assets.conf.uploadPath) {
      throw Error('No uploadPath found');
    }
    if (!assets.conf.deletePath) {
      throw Error('No deletePath found');
    }
    if (!assets.files || !assets.files[0]) {
      console.warn('no assets');
    }

    this.assets = assets.files;
    this.uploadPath = assets.conf.uploadPath;
    this.deletePath = assets.conf.deletePath;
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

    // Why would we not want to keep the history?
    //
    // this.editor.on('load', () => {
    //   const um = this.editor.UndoManager;
    //   // Clear stack of undo/redo
    //   um.clear();
    // });

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

  /**
   * Initialize the grapesjs build in the
   * correct mode
   */
  initGrapesJS(object) {
    // disable mautic global shortcuts
    Mousetrap.reset();

    if (object === 'page') {
      this.editor = this.initPage();
    } else if (object === 'emailform') {
      if (
        ContentService.getOriginalContent().body &&
        ContentService.getOriginalContent().body.indexOf('<mjml>') !== -1
      ) {
        this.editor = this.initEmailMjml();
      } else {
        this.editor = this.initEmailHtml();
      }
    } else {
      throw Error(`not supported builder type: ${object}`);
    }

    // add code mode button
    const codeModeButton = new CodeModeButton(this.editor);
    codeModeButton.addCommand();
    codeModeButton.addButton();

    this.setListeners();
  }

  static getMauticConf(mode) {
    return {
      sourceEditBtnLabel: Mautic.translate('grapesjsbuilder.sourceEditBtnLabel'),
      sourceCancelBtnLabel: Mautic.translate('grapesjsbuilder.sourceCancelBtnLabel'),
      sourceEditModalTitle: Mautic.translate('grapesjsbuilder.sourceEditModalTitle'),
      deleteAssetConfirmText: Mautic.translate('grapesjsbuilder.deleteAssetConfirmText'),
      categorySectionLabel: Mautic.translate('grapesjsbuilder.categorySectionLabel'),
      categoryBlockLabel: Mautic.translate('grapesjsbuilder.categoryBlockLabel'),
      dynamicContentBlockLabel: Mautic.translate('grapesjsbuilder.dynamicContentBlockLabel'),
      dynamicContentBtnLabel: Mautic.translate('grapesjsbuilder.dynamicContentBtnLabel'),
      dynamicContentModalTitle: Mautic.translate('grapesjsbuilder.dynamicContentModalTitle'),
      mode,
    };
  }

  /**
   * Initialize the builder in the landingapge mode
   */
  initPage() {
    // Launch GrapesJS with body part
    this.editor = grapesjs.init({
      clearOnRender: true,
      container: '.builder-panel',
      components: ContentService.getOriginalContent().body,
      height: '100%',
      canvas: {
        styles: ContentService.getStyles(),
      },
      storageManager: false, // https://grapesjs.com/docs/modules/Storage.html#basic-configuration
      assetManager: BuilderService.getMauticConf('page-html'),
      styleManager: {
        clearProperties: true, // Temp fix https://github.com/artf/grapesjs-preset-webpage/issues/27
      },
      plugins: [grapesjswebpage, grapesjspostcss, grapesjsmautic],
      pluginsOpts: {
        [grapesjswebpage]: {
          formsOpts: false,
        },
        grapesjsmautic: BuilderService.getMauticConf('page-html'),
      },
    });

    return this.editor;
  }

  initEmailMjml() {
    // EmailBuilder -> MJML
    this.editor = grapesjs.init({
      clearOnRender: true,
      container: '.builder-panel',
      components: ContentService.getOriginalContent().body,
      height: '100%',
      storageManager: false,
      assetManager: this.getAssetManagerConf(),

      plugins: [grapesjsmjml, grapesjspostcss, grapesjsmautic],
      pluginsOpts: {
        grapesjsmjml: {},
        grapesjsmautic: BuilderService.getMauticConf('email-mjml'),
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
      components: ContentService.getOriginalContent().body,
      height: '100%',
      storageManager: false,
      assetManager: this.getAssetManagerConf(),
      plugins: [grapesjsnewsletter, grapesjspostcss, grapesjsmautic],
      pluginsOpts: {
        grapesjsnewsletter: {},
        grapesjsmautic: BuilderService.getMauticConf('email-html'),
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
  // getAssetsList() {
  //   const assetManager = this.editor.AssetManager;
  //   const assets = assetManager.getAll();
  //   const assetsList = [];

  //   assets.forEach((asset) => {
  //     if (asset.get('type') === 'image') {
  //       assetsList.push({
  //         src: asset.get('src'),
  //         width: asset.get('width'),
  //         height: asset.get('height'),
  //       });
  //     } else {
  //       assetsList.push(asset.get('src'));
  //     }
  //   });

  //   return assetsList;
  // }
}
