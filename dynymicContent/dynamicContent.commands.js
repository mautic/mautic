import DynamicContentService from './dynamicContent.service';

export default class DynamicContentCommands {
  editor;

  dcService;

  constructor() {
    this.dcService = new DynamicContentService();
  }

  launchDynamicContent(editor, sender, options) {
    this.showCodePopup(editor, options);

    // Transform DC to token
    this.dcService.grapesConvertDynamicContentSlotsToTokens(editor);
  }

  /**
   * Convert dynamic content tokens to slot and load content
   */
  // eslint-disable-next-line class-methods-use-this
  grapesConvertDynamicContentTokenToSlot(editor, sender, options) {
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
