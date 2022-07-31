import grapesjs from 'grapesjs';
import grapesjsmjml from 'grapesjs-mjml';
import grapesjsnewsletter from 'grapesjs-preset-newsletter';
import grapesjswebpage from 'grapesjs-preset-webpage';
import grapesjspostcss from 'grapesjs-parser-postcss';
// import contentService from 'grapesjs-preset-mautic/src/content.service';
// import grapesjsmautic from 'grapesjs-preset-mautic';
// import mjmlService from 'grapesjs-preset-mautic/src/mjml/mjml.service';
import 'grapesjs-plugin-ckeditor';

// for local dev
import contentService from '../../../../../../grapesjs-preset-mautic/src/content.service';
import grapesjsmautic from '../../../../../../grapesjs-preset-mautic/src';
import mjmlService from '../../../../../../grapesjs-preset-mautic/src/mjml/mjml.service';

import CodeModeButton from './codeMode/codeMode.button';
import ContentService from 'grapesjs-preset-mautic/dist/content.service';
import Logger from 'grapesjs-preset-mautic/dist/logger';


export default class BuilderService {
  #editor;

  assets;

  uploadPath;

  deletePath;

  /**
   * @param {Editor} editor GrapesJS Editor
   * @param {Object} assets GrapesJS Asset Config Object
   */
  constructor(editor, assets) {
    if (!assets.conf || !assets.conf.uploadPath) {
      throw Error('No uploadPath found');
    }
    if (!assets.conf.deletePath) {
      throw Error('No deletePath found');
    }
    if (!assets.files || !assets.files[0]) {
      console.warn('no assets');
    }

    if (editor) {
      this.setEditor(editor);
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
    if (!this.getEditor()) {
      throw Error('No editor found');
    }
    const editor = this.getEditor();

    // Why would we not want to keep the history?
    //
    // this.editor.on('load', () => {
    //   const um = this.editor.UndoManager;
    //   // Clear stack of undo/redo
    //   um.clear();
    // });

    const keymaps = this.getEditor().Keymaps;
    let allKeymaps;

    editor.on('modal:open', () => {
      // Save all keyboard shortcuts
      allKeymaps = { ...keymaps.getAll() };

      // Remove keyboard shortcuts to prevent launch behind popup
      keymaps.removeAll();
    });

    editor.on('modal:close', () => {
      // ReMap keyboard shortcuts on modal close
      Object.keys(allKeymaps).map((objectKey) => {
        const shortcut = allKeymaps[objectKey];

        keymaps.add(shortcut.id, shortcut.keys, shortcut.handler);
        return keymaps;
      });
    });

    editor.on('asset:remove', (response) => {
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
   * @returns GrapesJsBuilder
   */
  initGrapesJS(type) {
    let editor

    // is there an existing editor in the correct mode?
    if (this.getEditor() && BuilderService.getRequestedMode(type) === ContentService.getMode(this.getEditor())) {
      this.logger = new Logger(this.getEditor());
      this.logger.debug('Using the existing editor', {mode: ContentService.getMode(this.getEditor())})
      return this.getEditor();
    }
    // initialize the editor in the correct mode
    if (ContentService.modePageHtml === BuilderService.getRequestedMode(type)) {
      editor = this.initPage();
    } else if (ContentService.modeEmailMjml === BuilderService.getRequestedMode(type)) {
      editor = this.initEmailMjml();
    } else if (ContentService.modeEmailHtml === BuilderService.getRequestedMode(type)) {
      editor = this.initEmailHtml();
    }
    this.setEditor(editor);
    this.addCodeModeButton();

    this.setListeners();

    return this.getEditor();
  }

  /**
   * Check if the editor needs to be in MJML mode
   * @returns boolean
   */
  static isMjmlModeRequested() {
    return mjmlService.getOriginalContentMjml().length > 0;
  }
  static getRequestedMode(type) {
    if (type === 'page') {
      return ContentService.modePageHtml;
    } else if (type === 'emailform') {
      if (BuilderService.isMjmlModeRequested()) {
        return ContentService.modeEmailMjml;
      } else {
        return ContentService.modeEmailHtml;
      }
    } else {
      throw Error(`Not supported builder type: ${type}`);
    }
  }

  static getMauticConf(mode) {
    return {
      mode,
    };
  }

  /**
   * Add the code mode button
   * @todo: only show button if configured: sourceEdit: 1,
   */
  addCodeModeButton() {
    const codeModeButton = new CodeModeButton(this.getEditor());
    codeModeButton.addCommand();
    codeModeButton.addButton();
  }


  static getCkeConf() {
    return {
      options: {
        language: 'en',
        toolbar: [
          { name: 'links', items: ['Link', 'Unlink'] },
          { name: 'basicstyles', items: ['Bold', 'Italic', 'Strike', '-', 'RemoveFormat'] },
          { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-'] },
          { name: 'colors', items: ['TextColor', 'BGColor'] },
          { name: 'document', items: ['Source'] },
          { name: 'insert', items: ['SpecialChar'] },
        ],
        extraPlugins: ['sharedspace', 'colorbutton'],
      },
    };
  }

  /**
   * Initialize the builder in the landingapge mode
   */
  initPage() {
    // Launch GrapesJS with body part
    return grapesjs.init({
      clearOnRender: true,
      container: '.builder-panel',
      height: '100%',
      canvas: {
        styles: contentService.getStyles(),
      },
      storageManager: false, // https://grapesjs.com/docs/modules/Storage.html#basic-configuration
      assetManager: this.getAssetManagerConf(),
      styleManager: {
        clearProperties: true, // Temp fix https://github.com/artf/grapesjs-preset-webpage/issues/27
      },
      plugins: [grapesjswebpage, grapesjspostcss, grapesjsmautic, 'gjs-plugin-ckeditor'],
      pluginsOpts: {
        [grapesjswebpage]: {
          formsOpts: false,
        },
        grapesjsmautic: BuilderService.getMauticConf('page-html'),
        'gjs-plugin-ckeditor': BuilderService.getCkeConf(),
      },
    });
  }

  initEmailMjml() {

    const editor = grapesjs.init({
      clearOnRender: true,
      container: '.builder-panel',
      height: '100%',
      storageManager: false,
      assetManager: this.getAssetManagerConf(),
      plugins: [grapesjsmjml, grapesjspostcss, grapesjsmautic, 'gjs-plugin-ckeditor'],
      pluginsOpts: {
        grapesjsmjml: {},
        grapesjsmautic: BuilderService.getMauticConf('email-mjml'),
        'gjs-plugin-ckeditor': BuilderService.getCkeConf(),
      },
    });

    editor.BlockManager.get('mj-button').set({
      content: '<mj-button href="https://">Button</mj-button>',
    });

    return editor;
  }

  initEmailHtml() {

    // Launch GrapesJS with body part
    const editor = grapesjs.init({
      clearOnRender: true,
      container: '.builder-panel',
      height: '100%',
      storageManager: false,
      assetManager: this.getAssetManagerConf(),
      plugins: [grapesjsnewsletter, grapesjspostcss, grapesjsmautic, 'gjs-plugin-ckeditor'],
      pluginsOpts: {
        grapesjsnewsletter: {},
        grapesjsmautic: BuilderService.getMauticConf('email-html'),
        'gjs-plugin-ckeditor': BuilderService.getCkeConf(),
      },
    });

    // add a Mautic custom block Button
    editor.BlockManager.get('button').set({
      content:
        '<a href="#" target="_blank" style="display:inline-block;text-decoration:none;border-color:#4e5d9d;border-width: 10px 20px;border-style:solid; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; background-color: #4e5d9d; display: inline-block;font-size: 16px; color: #ffffff; ">\n' +
        'Button\n' +
        '</a>',
    });

    return editor;
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

  getEditor(){
    return this.#editor;
  }
  setEditor(editor){
    if (!editor) {
      throw new Error('no editor');
    }
    console.warn('setting the editor',{ editor });
    this.#editor = editor;
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
