import DynamicContentService from './dynamicContent.service';
import CodeEditor from '../codeEditor';

export default class DynamicContentCommands {
  constructor(editor, opts = {}) {
    console.warn({ editor });
    this.editor = editor;
    this.opts = opts;
    this.dcService = new DynamicContentService();

    this.codePopup = this.buildCodePopup();
  }

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

  launchDynamicContent(editor, sender, options) {
    const { target } = options;
    const component = target || editor.getSelected();

    this.showCodePopup(component);

    // Transform DC to token
    DynamicContentService.grapesConvertDynamicContentSlotsToTokens(editor);
  }

  // Build popup content, Dynamic Content area and buttons
  buildCodePopup() {
    const cfg = this.editor.getConfig();

    const codePopup = document.createElement('div');
    const content = document.createElement('div');
    content.setAttribute('id', 'dynamic-content-popup');
    const btnEdit = document.createElement('button');

    btnEdit.innerHTML = this.opts.dynamicContentBtnLabel;
    btnEdit.className = `${cfg.stylePrefix}btn-prim ${cfg.stylePrefix}btn-dynamic-content`;
    btnEdit.onclick = this.updateCode.bind(this);

    codePopup.appendChild(content);
    codePopup.appendChild(btnEdit);

    return codePopup;
  }

  // Load content and show popup
  showCodePopup(component) {
    // this.updatePopupContents(component);
    console.warn({ component });
    this.editor.Modal.setContent('');
    this.editor.Modal.setContent(this.codePopup);
    this.editor.Modal.setTitle(this.opts.dynamicContentModalTitle);
    this.editor.Modal.open();
  }

  // Close popup
  updateCode() {
    this.editor.Modal.close();
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
