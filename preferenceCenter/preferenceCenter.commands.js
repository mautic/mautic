import Logger from 'grapesjs-preset-mautic/src/logger';
import PreferenceCenterService from 'grapesjs-preset-mautic/src/preferenceCenter/preferenceCenter.service';

export default class PreferenceCenterCommands {
  editor;

  dcPopup;

  pcService;

  logger;

  constructor(editor) {
    this.pcService = new PreferenceCenterService(editor);
    this.logger = new Logger(editor);
  }

  launchPreferenceCenterPopup(editor, sender, options) {
    // Transform DC slots to token
    // editor.runCommand('preset-mautic:dynamic-content-slots-to-tokens');

    this.showPreferenceCenterEditor(editor, options);
  }

  // eslint-disable-next-line class-methods-use-this
  stopPreferenceCenterPopup(editor) {
    editor.runCommand('preset-mautic:dynamic-content-tokens-to-slots');
  }

  /**
   * Convert all dynamic content tokens on the canvas to
   * human readable texts/slots/component.
   * {dynamiccontent} => Dynamic Content
   */
  convertPreferenceCenterTokensToSlots() {
    const components = this.pcService.getDcComponents();

    components.forEach((comp) => {
      this.pcService.transformDcTokenToSlot(comp);
    });
  }

  /**
   * Convert dynamic content slots to tokens
   * Dynamic Content => {preferenceCenter}
   *
   * @param editor
   * @returns integer nr of dynamic content slots found
   */
  // eslint-disable-next-line class-methods-use-this
  convertPreferenceCenterSlotsToTokens(editor) {
    // get all dynamic content elements loaded in the editor
    const preferenceCenter = editor.DomComponents.getWrapper().find('[data-slot="preferenceCenter"]');
    preferenceCenter.forEach((preferenceCenter) => {
      const attributes = preferenceCenter.getAttributes();
      const decId = attributes['data-param-dec-id'];
      // If it's not a token -> convert to token
      if (decId >= 0) {
        const dynConId = PreferenceCenterCommands.getPcStoreId(attributes['data-param-dec-id']);

        const dynConTarget = mQuery(dynConId);
        const dynConName = dynConTarget.find(`${dynConId}_tokenName`).val();
        const dynConToken = `{dynamiccontent="${dynConName}"}`;

        // Clear id because it's reloaded by Mautic and this prevent slot to be destroyed by GrapesJs destroy event on close.
        // preferenceCenter.addAttributes({ 'data-param-dec-id': '' });
        preferenceCenter.set('content', dynConToken);
      }
    });

    return preferenceCenter.length;
  }

  /**
   * Build and display the Dynamic Content editor popup/modal window.
   * @param {Model} component The current grapesjs component
   */
  // eslint-disable-next-line class-methods-use-this
  showPreferenceCenterEditor(editor, options) {
    this.dcPopup = PreferenceCenterCommands.buildPreferenceCenterPopup();

    this.addPreferenceCenterEditor(editor, options);

    const title = Mautic.translate('grapesjsbuilder.preferenceCenterBlockLabel');
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
  static buildPreferenceCenterPopup() {
    const content = document.createElement('div');
    content.setAttribute('id', 'dynamic-content-popup');

    const codePopup = document.createElement('div');
    codePopup.appendChild(content);

    return codePopup;
  }

  /**
   * Load Dynamic Content editor and append to the codePopup Modal
   */
  addPreferenceCenterEditor(editor, options) {
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
    const focusForm = mQuery(PreferenceCenterCommands.getPcStoreId(attributes['data-param-dec-id']));
    if (focusForm.length <= 0) {
      throw new Error(
        `No preferenceCenter email form found for '${attributes['data-param-dec-id']}'`
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
   * Delete PreferenceCenter on Mautic side
   *
   * @param component
   */
  // eslint-disable-next-line class-methods-use-this
  deletePreferenceCenterStoreItem(editor, sender, options) {
    const { component } = options;
    const attributes = component.getAttributes();
    if (!attributes['data-param-dec-id']) {
      this.logger.warning('no dec-id found. Can not delete', attributes);
    }

    const dcStoreId = PreferenceCenterCommands.getPcStoreId(attributes['data-param-dec-id']);
    const dcStoreItem = mQuery(dcStoreId);
    if (!dcStoreItem) {
      this.logger.warning('No PreferenceCenter store item found', { dcStoreId });
    }

    // remove the store item
    dcStoreItem.find('a.remove-item:first').click();
    // remove store navigation item
    const dynCon = mQuery('.preferenceCenterFilterContainer').find(`a[href='${dcStoreId}']`);
    if (!dynCon || !dynCon.parent()) {
      this.logger.warning('No PreferenceCenter store item to delete found', { dcStoreId });
    }
    dynCon.parent().remove();
    this.logger.debug('PreferenceCenter store item removed', { dcStoreId });
  }

  /**
   * Get the PreferenceCenter identifier of the html store item
   * e.g. emailform_preferenceCenter_0
   * @param {integer} id
   * @returns string
   */
  static getPcStoreId(id) {
    if (id < 0) {
      throw new Error('no dynamic content ID');
    }
    return `#emailform_preferenceCenter_${id}`;
  }
}
