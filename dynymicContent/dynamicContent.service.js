export default class DynamicContentService {
  // dynamic content tabs on the base html (hidden)
  dynamicContentTabs;

  constructor(dynamicContentTabs) {
    if (!dynamicContentTabs) {
      throw Error('No dynamic content tabs found');
    }

    this.dynamicContentTabs = dynamicContentTabs;
  }

  /**
   * Convert dynamic content tokens to slot and load content
   * Used in grapesjs-preset-mautic
   */
  grapesConvertDynamicContentTokenToSlot(editor) {
    const dc = editor.DomComponents;

    const dynamicContents = dc.getWrapper().find('[data-slot="dynamicContent"]');

    if (dynamicContents.length) {
      dynamicContents.forEach((dynamicContent) => {
        this.manageDynamicContentTokenToSlot(dynamicContent);
      });
    }
  }

  /**
   * Convert a text token to an editor UI
   * @param {JQuery Object} component
   */
  manageDynamicContentTokenToSlot(component) {
    const regex = RegExp(/\{dynamiccontent="(.*)"\}/, 'g');

    const content = component.get('content');
    const regexEx = regex.exec(content);

    // abort if component does not contain a dynamic content element
    if (regexEx === null) {
      return false;
    }

    // Get the name of the dynamic content element.
    // E.g. Dynamic Content from: {dynamiccontent="Dynamic Content"}
    const dynContenName = regexEx[1];
    // get the tab matching the dynamic content
    const dynContentTabA = this.dynamicContentTabs.find((tab) => tab.title === dynContenName);

    if (typeof dynContentTabA !== 'undefined' && dynContentTabA.length) {
      // If dynamic content item exists -> fill
      const dynContentTarget = dynContentTabA.attr('href');
      let dynConContent = '';

      if (mQuery(dynContentTarget).html()) {
        const dynConContainer = mQuery(dynContentTarget).find(`${dynContentTarget}_content`);

        if (dynConContainer.hasClass('editor')) {
          dynConContent = dynConContainer.froalaEditor('html.get');
        } else {
          dynConContent = dynConContainer.html();
        }
      }

      if (dynConContent === '') {
        dynConContent = dynContentTabA.text();
      }

      component.addAttributes({
        'data-param-dec-id': parseInt(dynContentTarget.replace(/[^0-9]/g, ''), 10),
      });
      component.set('content', dynConContent);
    } else {
      // If dynamic content item doesn't exist -> create
      // @todo replace mQuery('#dynamicContentTabs') with class property
      const dynConTarget = Mautic.createNewDynamicContentItem(mQuery);
      const dynConTab = mQuery('#dynamicContentTabs').find(`a[href="${dynConTarget}"]`);

      // e.g. #emailform_dynamicContent_1
      component.addAttributes({
        'data-param-dec-id': parseInt(dynConTarget.replace(/[^0-9]/g, ''), 10),
      });
      component.set('content', dynConTab.text());
    }
    return true;
  }

  /**
   * Delete DC on Mautic side
   *
   * @param component
   */
  static deleteDynamicContentItem(component) {
    const attributes = component.getAttributes();

    // Only delete if we click on trash, not when GrapesJs is destroyed
    if (attributes['data-param-dec-id'] !== '') {
      const dynConId = `#emailform_dynamicContent_${attributes['data-param-dec-id']}`;
      const dynConTarget = mQuery(dynConId);

      if (dynConTarget) {
        dynConTarget.find('a.remove-item:first').click();
        // remove vertical tab in outside form
        const dynCon = mQuery('.dynamicContentFilterContainer').find(`a[href=${dynConId}]`);
        if (dynCon && dynCon.parent()) {
          dynCon.parent().remove();
        }
      }
    }
  }

  /**
   * Convert dynamic content slots to tokens
   * Used in grapesjs-preset-mautic
   *
   * @param editor
   */
  static grapesConvertDynamicContentSlotsToTokens(editor) {
    const dc = editor.DomComponents;

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
}
