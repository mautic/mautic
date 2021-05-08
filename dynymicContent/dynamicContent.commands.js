import DynamicContentService from './dynamicContent.service';

export default class DynamicContentCommands {
  editor;

  dcService;

  constructor() {
    this.dcService = new DynamicContentService();
  }

  launchDynamicContentPopup(editor, sender, options) {
    this.showCodePopup(editor, options);

    // Transform DC to token
    editor.runCommand('preset-mautic:dynamic-content-slots-to-tokens');
  }

  /**
   * Convert dynamic content tokens to slot and load content
   * {dynamiccontent} => Dynamic Content
   */
  // eslint-disable-next-line class-methods-use-this
  convertDynamicContentTokenToSlot(editor) {
    const getHtml = editor.getHtml();
    const dc = editor.DomComponents;

    const dynamicContents = dc.getWrapper().find('[data-slot="dynamicContent"]');
    if (dynamicContents.length) {
      dynamicContents.forEach((dynamicContent) => {
        this.dcService.manageDynamicContentTokenToSlot(dynamicContent);
      });
    }
  }

  /**
   * Convert dynamic content slots to tokens
   * Dynamic Content => {dynamicContent}
   *
   * @param editor
   */
  // eslint-disable-next-line class-methods-use-this
  convertDynamicContentSlotsToTokens(editor) {
    const dc = editor.DomComponents;
    // console.warn('grapesConvertDynamicContentSlotsToTokens');
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
   * Build and display the Dynamic Content editor popup/modal window.
   * @param {Model} component The current grapesjs component
   */
  // eslint-disable-next-line class-methods-use-this
  showCodePopup(editor, options) {
    const { target } = options;
    const component = target || editor.getSelected();
    if (!component) {
      throw new Error('No components found');
    }

    let dcPopup = DynamicContentCommands.buildDynamicContentPopup();

    dcPopup = DynamicContentCommands.addDynamicContentEditor(component, dcPopup);

    const title = Mautic.translate('grapesjsbuilder.dynamicContentBlockLabel');
    const modal = editor.Modal;
    modal.setTitle(title);
    modal.setContent('test');

    // editor.Modal.setContent(dcPopup);
    modal.open();
    modal.onceClose(() => editor.stopCommand('preset-mautic:dynamic-content-open'));
  }

  // Build the content for the popup/modal: Dynamic Content area (and buttons)
  static buildDynamicContentPopup() {
    const codePopup = document.createElement('div');
    const content = document.createElement('div');
    content.setAttribute('id', 'dynamic-content-popup');
    codePopup.appendChild(content);

    return codePopup;
  }

  // undo last commit before next commit!!

  /**
   * Load Dynamic Content editor and append to the codePopup Modal
   */
  static addDynamicContentEditor(component, dcPopup) {
    const attributes = component.getAttributes();
    const popupContent = dcPopup.querySelector('#dynamic-content-popup');

    // do we need both?
    // console.warn({ dcPopup });
    // console.warn({ popupContent });

    // get the dynamic content editor
    const focusForm = mQuery(`#emailform_dynamicContent_${attributes['data-param-dec-id']}`);

    // Show if hidden
    focusForm.removeClass('fade');
    // Hide delete default button
    focusForm.find('.tab-pane:first').find('.remove-item').hide();

    // Insert inside popup
    mQuery(popupContent).empty().append(focusForm.detach());
    return popupContent;
  }
}
