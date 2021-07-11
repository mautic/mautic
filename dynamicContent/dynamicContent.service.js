import Logger from '../logger';

export default class DynamicContentService {
  /**
   * Dynamic content tabs on the base html item store (hidden)
   * Each "tab" corresponds to a dynamic content block on the canvas.
   * References by the tab.title (e.g. Dynamic Content)
   */
  dcStoreItems = [];

  /**
   * Components currently on the canvas/editor
   */
  dcComponents = [];

  editor;

  constructor(editor) {
    this.logger = new Logger(editor);
    this.editor = editor;
  }

  /**
   * Get the name of the dynamic content element.
   * Used as identifier
   * E.g. Dynamic Content from: {dynamiccontent="Dynamic Content"}
   * @param {GrapesJS Component} component
   * @returns string | null
   */
  getTokenName(component) {
    const regex = RegExp(/\{dynamiccontent="(.*)"\}/, 'g');
    const content = component.get('content');
    const regexEx = regex.exec(content);

    if (!regexEx || !regexEx[1]) {
      this.logger.debug('No dynamic content tokens to get', { content });
      return null;
    }

    return regexEx[1];
  }

  /**
   * Convert a text token to the default variant
   * Wire up the ids
   *
   * @param {GrapesJS Component} component
   */
  transformDcTokenToSlot(component) {
    this.getDcStoreItems();
    this.getDcComponents();

    // get the item/tab matching the dynamic content on the canvas
    const dataParamDecId = component.getAttributes()['data-param-dec-id'] || false;

    const dcItem = this.dcStoreItems.find((item) => item.id === dataParamDecId);

    // If dynamic content item exists -> fill
    // Hint: the first dynamic content item (tab) is created from php: #emailform_dynamicContent_0
    if (dcItem) {
      this.updateComponent(component, dcItem);
    } else {
      this.initNewComponent(component);
    }
    return true;
  }

  updateComponent(component, dcItem) {
    this.logger.debug('Updating existing dynamic content item', { dcItem });
    // Update the component on the canvas with new values from the html store
    // and link it  with the id to the html store
    component.addAttributes({ 'data-param-dec-id': dcItem.id });
    component.set('content', dcItem.content);

    return component;

    // let dynConContent = '';
    // if (dcItem.id) {
    //   const dynConContainer = mQuery(dcTarget.htmlId).find(dcTarget.content);
    //   // is there content in the current editor?
    //   if (dynConContainer.hasClass('editor')) {
    //     dynConContent = dynConContainer.froalaEditor('html.get');
    //   } else {
    //     dynConContent = dynConContainer.html();
    //   }
    // }
  }

  /**
   * If dynamic content item in html store doesn't exist -> create
   * @todo replace mQuery('#dynamicContentTabs') with class property
   *
   * @param {GrapesJS Component} component
   * @param {string}
   */
  initNewComponent(component) {
    const dcTarget = Mautic.createNewDynamicContentItem(mQuery);

    // get ID from newly generated store item: e.g. #emailform_dynamicContent_1
    const dataParamDecId = parseInt(dcTarget.replace(/[^0-9]/g, ''), 10);
    component.addAttributes({
      'data-param-dec-id': dataParamDecId,
    });

    // Replace token on canvas with user facing name: dcName
    component.set('content', 'Dynamic Content ' + dataParamDecId);
    this.logger.debug('Created a new dynamic content item', {
      dataParamDecId,
      component,
    });

    return component;
  }

  /**
   * Extract the dynamic content index and the html id from a string:
   * e.g. href from dcTarget
   *
   * @param {string} identifier e.g. http://localhost:1234/#emailform_dynamicContent_1
   */
  static getDcTarget(identifier) {
    const regex = RegExp(/(#emailform_dynamicContent_)(\d*)/, 'g');
    const result = regex.exec(identifier);

    if (!result || result.length !== 3) {
      throw new Error(`No DynamicContent target found: ${identifier}`);
    }
    return {
      htmlId: `${result[1]}${result[2]}`, // #emailform_dynamicContent_1
      decId: parseInt(result[2], 10), // 1
      content: `${result[1]}${result[2]}_content`, // #emailform_dynamicContent_1_content
    };
  }

  // /**
  //  * Extract the dynamic content id from an id string:
  //  *
  //  * @param {string} identifier e.g. emailform_dynamicContent_1
  //  * @return integer
  //  */
  // static getDynContentId(identifier) {
  //   const regex = RegExp(/(emailform_dynamicContent_)(\d*)/, 'g');
  //   const result = regex.exec(identifier);
  //   console.warn({ result });
  //   if (!result || !result[2]) {
  //     throw new Error(`No DynamicContent target found: ${identifier}`);
  //   }
  //   return parseInt(result[2], 10)
  // }

  /**
   * Get the default dynamic content name (tokenName)
   * @param {string} id id of the input field
   * @returns string of the field
   */
  static getDcName(target) {
    return `Dynamic Content ${target.decId}`;
  }

  /**
   * Get the default content
   * @param {string} id id of the textarea
   * @returns string with the html in the textarea
   */
  static getDcContent(target) {
    return mQuery(target.id).val() || DynamicContentService.getDcName(target);
  }

  /**
   * Get all Dynamic Content items from the HTML store
   * @returns array of objects with title and href
   */
  getDcStoreItems() {
    this.dcStoreItems = [];

    // #dynamicContentContainer
    mQuery('.dynamic-content').each((index, value) => {
      const dcTarget = DynamicContentService.getDcTarget(`#${value.id}`);

      this.dcStoreItems.push({
        identifier: value.id, // emailform_dynamicContent_0
        id: dcTarget.decId, // 0
        name: DynamicContentService.getDcName(dcTarget), // Dynamic Content 1
        content: DynamicContentService.getDcContent(dcTarget), // Default Dynamic Content
      });
    });

    // one is always set from php
    if (!this.dcStoreItems[0]) {
      throw Error('No dynamic content store item found!');
    }
  }

  /**
   * Get all the dynamic content components currently
   * on the canvas (in the editor).
   * @return {GrapesJS Component} array
   */
  getDcComponents() {
    if (!this.editor) {
      throw new Error('no Editor');
    }

    this.dcComponents = [];

    const wrapper = this.editor.getWrapper();
    const dcCompoments = wrapper.find('[data-gjs-type="dynamic-content"]');

    dcCompoments.forEach((comp) => {
      if (!comp.is('dynamic-content')) {
        throw new Error('no dynamic-content component');
      }
      this.dcComponents.push(comp);
    });

    return this.dcComponents;
  }
}
