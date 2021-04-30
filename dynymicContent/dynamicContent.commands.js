import DynamicContentService from './dynamicContent.service';
import CodeEditor from '../codeEditor';

export default class DynamicContentCommands {
  // constructor(editor, opts = {}) {
  //   console.warn({ editor });
  //   this.editor = editor;
  //   this.opts = opts;
  //   this.dcService = new DynamicContentService();
  // }

  /**
   * Launch Code Editor popup
   */
  static launchCodeEdit(editor, sender) {
    const codeEditor = new CodeEditor(editor, this.opts);
    console.warn({ editor });

    if (sender) {
      sender.set('active', 0);
    }

    // Transform DC to token
    this.dcService.grapesConvertDynamicContentSlotsToTokens(editor);
    console.warn({ codeEditor });
    codeEditor.showCodePopup();
  }

  static launchDynamicContent(editor) {
    // const { target } = options;
    // const component = target || editor.getSelected();
    DynamicContentCommands.showCodePopup(editor);

    // Transform DC to token
    DynamicContentService.grapesConvertDynamicContentSlotsToTokens(editor);
  }

  // Build popup content, Dynamic Content area and buttons
  static buildCodePopup(editor) {
    const cfg = editor.getConfig();

    const codePopup = document.createElement('div');
    const content = document.createElement('div');
    content.setAttribute('id', 'dynamic-content-popup');
    const btnEdit = document.createElement('button');
    const btnLabel = Mautic.translate('grapesjsbuilder.dynamicContentBtnLabel');

    btnEdit.innerHTML = btnLabel;
    btnEdit.className = `${cfg.stylePrefix}btn-prim ${cfg.stylePrefix}btn-dynamic-content`;
    btnEdit.onclick = DynamicContentCommands.updateCode.bind(this);

    codePopup.appendChild(content);
    codePopup.appendChild(btnEdit);

    return codePopup;
  }

  // Load content and show popup
  static showCodePopup(editor) {
    // this.updatePopupContents(component);
    const codePopup = DynamicContentCommands.buildCodePopup(editor);
    const title = Mautic.translate('grapesjsbuilder.dynamicContentBlockLabel');

    editor.Modal.setContent('');
    editor.Modal.setContent(codePopup);
    editor.Modal.setTitle(title);
    editor.Modal.open();
  }

  // Close popup
  static updateCode(editor) {
    console.warn('updateCode button', editor);
    editor.Modal.close();
  }

  /**
   * Load Dynamic Content editor and append to the Modal
   * @param {*} component
   */
  updatePopupContents(component) {
    const self = this;
    const popupContent = this.codePopup.querySelector('#dynamic-content-popup');
    const attributes = component.getAttributes();
    const focusForm = mQuery(`#emailform_dynamicContent_${attributes['data-param-dec-id']}`);

    // Remove Mautic Froala and reload one with custom setting
    focusForm.find('textarea.editor').each(function () {
      const buttons = self.opts.dynamicContentFroalaButtons;
      const froalaOptions = {
        toolbarButtons: buttons,
        toolbarButtonsMD: buttons,
        toolbarButtonsSM: buttons,
        toolbarButtonsXS: buttons,
        toolbarSticky: false,
        linkList: [],
        imageEditButtons: [
          'imageReplace',
          'imageAlign',
          'imageRemove',
          'imageAlt',
          'imageSize',
          '|',
          'imageLink',
          'linkOpen',
          'linkEdit',
          'linkRemove',
        ],
      };

      mQuery(this).froalaEditor('destroy');
      mQuery(this).froalaEditor(mQuery.extend({}, Mautic.basicFroalaOptions, froalaOptions));
    });

    // Show if hidden
    focusForm.removeClass('fade');
    // Hide delete default button
    focusForm.find('.tab-pane:first').find('.remove-item').hide();
    // Insert inside popup
    mQuery(popupContent).empty().append(focusForm.detach());
  }
}
