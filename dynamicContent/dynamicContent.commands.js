import Logger from '../logger';
import DynamicContentService from './dynamicContent.service';

export default class DynamicContentCommands {
  editor;

  dcPopup;

  dcService;

  logger;

  constructor(editor) {
    this.dcService = new DynamicContentService(editor);
    this.logger = new Logger(editor);
  }

  launchDynamicContentPopup(editor, sender, options) {
    // Transform DC slots to token
    // editor.runCommand('preset-mautic:dynamic-content-slots-to-tokens');

    this.showDynamicContentEditor(editor, options);
  }

  // eslint-disable-next-line class-methods-use-this
  stopDynamicContentPopup(editor) {
    editor.runCommand('preset-mautic:dynamic-content-tokens-to-slots');
  }

  /**
   * Convert all dynamic content tokens on the canvas to
   * human readable texts/slots/component.
   * {dynamiccontent} => Dynamic Content
   */
  convertDynamicContentTokensToSlots() {
    const components = this.dcService.getDcComponents();
    components.forEach((comp) => {
      if (!this.dcService.transformDcTokenToSlot(comp)) {
        this.logger.warning('DynamicContent component not updated', { comp });
      }
    });
  }

  /**
   * Convert dynamic content slots to tokens
   * Dynamic Content => {dynamicContent}
   *
   * @param editor
   * @returns integer nr of dynamic content slots found
   */
  // eslint-disable-next-line class-methods-use-this
  convertDynamicContentSlotsToTokens(editor) {
    // get all dynamic content elements loaded in the editor
    const dynamicContents = editor.DomComponents.getWrapper().find('[data-slot="dynamicContent"]');
    dynamicContents.forEach((dynamicContent) => {
      const attributes = dynamicContent.getAttributes();
      const decId = attributes['data-param-dec-id'];
      // If it's not a token -> convert to token
      if (decId >= 0) {
        const dynConId = DynamicContentCommands.getDcStoreId(attributes['data-param-dec-id']);

        const dynConTarget = mQuery(dynConId);
        const dynConName = dynConTarget.find(`${dynConId}_tokenName`).val();
        const dynConToken = `{dynamiccontent="${dynConName}"}`;

        // Clear id because it's reloaded by Mautic and this prevent slot to be destroyed by GrapesJs destroy event on close.
        // dynamicContent.addAttributes({ 'data-param-dec-id': '' });
        dynamicContent.set('content', dynConToken);
      }
    });

    return dynamicContents.length;
  }

  /**
   * Build and display the Dynamic Content editor popup/modal window.
   * @param {Model} component The current grapesjs component
   */
  // eslint-disable-next-line class-methods-use-this
  showDynamicContentEditor(editor, options) {
    this.dcPopup = DynamicContentCommands.buildDynamicContentPopup();

    this.addDynamicContentEditor(editor, options);

    const title = Mautic.translate('grapesjsbuilder.dynamicContentBlockLabel');
    const modal = editor.Modal;
    modal.setTitle(title);

    editor.Modal.setContent(this.dcPopup);
    modal.open();
    modal.onceClose(() => editor.stopCommand('preset-mautic:dynamic-content-open'));
  }

  /**
   * Build the basic popup/modal frame to hold the dynamic content editor
   * @returns HTMLDivElement
   */
  static buildDynamicContentPopup() {
    const content = document.createElement('div');
    content.setAttribute('id', 'dynamic-content-popup');

    const codePopup = document.createElement('div');
    codePopup.appendChild(content);

    return codePopup;
  }

  /**
   * Load Dynamic Content editor and append to the codePopup Modal
   */
  addDynamicContentEditor(editor, options) {
    const { target } = options;
    const dcComponent = target || editor.getSelected();
    if (!dcComponent) {
      throw new Error('No dc components found');
    }

    const attributes = dcComponent.getAttributes();
    // const popupContent = dcPopup.querySelector('#dynamic-content-popup');

    // do we need both?
    // console.warn({ dcPopup });
    // console.warn({ popupContent });

    // get the dynamic content editor
    const focusForm = mQuery(DynamicContentCommands.getDcStoreId(attributes['data-param-dec-id']));
    if (focusForm.length <= 0) {
      throw new Error(
        `No dynamicContent email form found for '${attributes['data-param-dec-id']}'`
      );
    }

    // Show if hidden
    focusForm.removeClass('fade');
    // Hide delete default button
    focusForm.find('.tab-pane:first').find('.remove-item').hide();

    // Insert inside popup
    mQuery(this.dcPopup).empty().append(focusForm.detach());
  }

  /**
   * Delete DynamicContent on Mautic side
   *
   * @param component
   */
  // eslint-disable-next-line class-methods-use-this
  deleteDynamicContentStoreItem(editor, sender, options) {
    const { component } = options;
    const attributes = component.getAttributes();
    if (!attributes['data-param-dec-id']) {
      this.logger.warning('no dec-id found. Can not delete', attributes);
    }

    const dcStoreId = DynamicContentCommands.getDcStoreId(attributes['data-param-dec-id']);
    const dcStoreItem = mQuery(dcStoreId);
    if (!dcStoreItem) {
      this.logger.warning('No DynamicContent store item found', { dcStoreId });
    }

    // remove the store item
    dcStoreItem.find('a.remove-item:first').click();
    // remove store navigation item
    const dynCon = mQuery('.dynamicContentFilterContainer').find(`a[href='${dcStoreId}']`);
    if (!dynCon || !dynCon.parent()) {
      this.logger.warning('No DynamicContent store item to delete found', { dcStoreId });
    }
    dynCon.parent().remove();
    this.logger.debug('DynamicContent store item removed', { dcStoreId });
  }

  /**
   * Get the DynamicContent identifier of the html store item
   * e.g. emailform_dynamicContent_0
   * @param {integer} id
   * @returns string
   */
  static getDcStoreId(id) {
    if (id < 0) {
      throw new Error('no dynamic content ID');
    }
    return `#emailform_dynamicContent_${id}`;
  }
}
