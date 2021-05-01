export default class DynamicContent {
  editor;

  codePopup;

  constructor(editor) {
    console.log('did initialize dynamic content');
    this.editor = editor;
  }

  // Build the content for the Modal: Dynamic Content area (and buttons)
  buildCodePopup() {
    console.log('buildCodePopup');
    this.codePopup = document.createElement('div');
    const content = document.createElement('div');
    content.setAttribute('id', 'dynamic-content-popup');
    this.codePopup.appendChild(content);

    // const btnEdit = document.createElement('button');
    // const btnLabel = Mautic.translate('grapesjsbuilder.dynamicContentBtnLabel');
    // btnEdit.innerHTML = btnLabel;
    // btnEdit.className = `${cfg.stylePrefix}btn-prim ${cfg.stylePrefix}btn-dynamic-content`;
    // btnEdit.onclick = this.updateCode.bind(this);
    // codePopup.appendChild(btnEdit);

    return this.codePopup;
  }

  /**
   * Build and display the Dynamic Content editor popup/modal window.
   * @param {Model} component The current grapesjs component
   */
  showCodePopup(component) {
    if (!component) {
      throw new Error('No components found');
    }
    console.log('showCodePopup');

    this.codePopup = this.buildCodePopup();
    this.updatePopupContents(component);

    const title = Mautic.translate('grapesjsbuilder.dynamicContentBlockLabel');
    this.editor.Modal.setContent('');
    this.editor.Modal.setContent(this.codePopup);
    this.editor.Modal.setTitle(title);
    this.editor.Modal.open();
  }

  // Close popup
  //   updateCode() {
  //     console.warn('updateCode button', this.editor);
  //     this.editor.Modal.close();
  //   }

  /**
   * Load Dynamic Content editor and append to the Modal
   * @param {*} component
   */
  updatePopupContents(component) {
    const popupContent = this.codePopup.querySelector('#dynamic-content-popup');
    const attributes = component.getAttributes();
    const focusForm = mQuery(`#emailform_dynamicContent_${attributes['data-param-dec-id']}`);

    // Remove Mautic Froala and reload one with custom setting
    // focusForm.find('textarea.editor').each(function () {

    //   mQuery(this).froalaEditor('destroy');
    //   mQuery(this).froalaEditor(mQuery.extend({}, Mautic.basicFroalaOptions, froalaOptions));
    // });

    // Show if hidden
    focusForm.removeClass('fade');
    // Hide delete default button
    focusForm.find('.tab-pane:first').find('.remove-item').hide();
    // Insert inside popup
    mQuery(popupContent).empty().append(focusForm.detach());
  }
}
