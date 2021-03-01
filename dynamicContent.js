class DynamicContent {
  constructor(editor, opts = {}) {
    this.editor = editor;
    this.opts = opts;

    this.codePopup = this.buildCodePopup();
  }

  // Build popup content, Dynamic Content area and buttons
  buildCodePopup() {
    const cfg = this.editor.getConfig();

    let codePopup = document.createElement('div');
    let content = document.createElement('div');
    content.setAttribute('id', 'dynamic-content-popup');
    let btnEdit = document.createElement('button');

    btnEdit.innerHTML = this.opts.dynamicContentBtnLabel;
    btnEdit.className = cfg.stylePrefix + 'btn-prim ' + cfg.stylePrefix + 'btn-dynamic-content';
    btnEdit.onclick = this.updateCode.bind(this);

    codePopup.appendChild(content);
    codePopup.appendChild(btnEdit);

    return codePopup;
  }

  // Load content and show popup
  showCodePopup(component) {
    this.updatePopupContents(component);

    this.editor.Modal.setContent('');
    this.editor.Modal.setContent(this.codePopup);
    this.editor.Modal.setTitle(this.opts.dynamicContentModalTitle);
    this.editor.Modal.open();
  }

  // Close popup
  updateCode() {
    this.editor.Modal.close();
  }

  // Load Dynamic Content editor from Mautic and append to popup
  updatePopupContents(component) {
    let self = this;
    let popupContent = this.codePopup.querySelector('#dynamic-content-popup');
    let attributes = component.getAttributes();
    let focusForm = mQuery('#emailform_dynamicContent_' + attributes['data-param-dec-id']);

    // Remove Mautic Froala and reload one with custom setting
    focusForm.find('textarea.editor').each(function () {
      var buttons = self.opts.dynamicContentFroalaButtons;
      var froalaOptions = {
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

export default DynamicContent;
