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
      this.logger.debug('DC: No dynamic content tokens to get', { content });
      return null;
    }
    return regexEx[1];
  }

  /**
   * Get dec ID from the store items identifier: e.g. #emailform_dynamicContent_1
   * @returns integer
   */
  static getDecId(storeItemIdentifier) {
    // dec id starts with 1, so we add +1
    const decId = parseInt(storeItemIdentifier.replace(/[^0-9]/g, ''), 10) + 1;
    if (decId <= 0) {
      throw new Error('DC: no valid decId');
    }
    return decId;
  }

  /**
   * Returns the decId from the component
   * @returns integer
   */
  static getDataParamDecid(component) {
    return parseInt(component.getAttributes()['data-param-dec-id'], 10) || 0;
  }

  /**
   * Link the component on the canvas with the item in the HTML store
   * If it does not exist, create a new store item
   */
  linkComponentToStoreItem(component) {
    // get components data-param-dec-id (can come from token from db)
    let decId = DynamicContentService.getDataParamDecid(component);
    if (decId > 0) {
      this.logger.debug('DC: Already wired up', { decId });
      return decId;
    }
    // not wired up yet - get component token id
    const tokenName = this.getTokenName(component);
    const tokenNr = DynamicContentService.getDecId(tokenName);
    decId = tokenNr;
    if (decId > 0) {
      this.logger.debug('DC: Using decId from token nr', { tokenName, decId });
      return decId;
    }

    // double check if a store item exists - if not found - create new store item
    if (!this.getStoreItem(decId)) {
      this.createNewStoreItem(component);
    }

    // return components dec-id (also from the new one)
    return DynamicContentService.getDataParamDecid(component);
  }

  /**
   * Get the content from the Html store and put the default content on the canvas.
   * Creates a store item (filter) in Mautic Form if new.
   * Wires up the ids.
   * E.g. if they are initialized from a {token}
   *
   * @param {GrapesJS Component} component
   */
  updateComponentFromDcStore(component) {
    // get the item/tab matching the dynamic content on the canvas
    let dataParamDecId = DynamicContentService.getDataParamDecid(component);
    let dcItem = this.getStoreItem(dataParamDecId);

    // Load the html store item
    dataParamDecId = DynamicContentService.getDataParamDecid(component);
    dcItem = this.getStoreItem(dataParamDecId);

    // Update the Grapesjs component with the content from the HTML store item
    this.updateComponent(component, dcItem);
    return true;
  }

  /**
   * if the editors modal is closed/stopped the Components content visible
   * on the canvas and the html store item has to be updated
   */
  updateDcStoreItem() {
    // For the editing inside grapesjs the dynamcicontent popup is moved to the "grapesjs dom"
    // so it has to be moved back to the Mautic email form for saving.
    const modalContent = mQuery('#dynamic-content-popup');
    // On modal close -> move editor within Mautic
    if (!modalContent) {
      throw new Error('DC: No dynamic content popup found');
    }

    const dynamicContentContainer = mQuery('#dynamicContentContainer');
    const content = mQuery(modalContent).contents().first();
    dynamicContentContainer.append(content);
    modalContent.detach(); // remove the modal

    this.logger.debug('DC: store item updated', {
      id: content.attr('id'),
    });
  }

  /**
   * Get a dynamic content item from its html store
   */
  getStoreItem(decId) {
    // get all items
    this.getDcStoreItems();
    const item = this.dcStoreItems.find((itm) => itm.decId === decId);
    return item;
  }

  /**
   * Set the html/content of the visible component on the canvas
   */
  updateComponent(component, dcItem) {
    if (!component || !dcItem) {
      throw new Error('No component or dynamic content item');
    }

    this.logger.debug('DC: Updating DC component with values from store', { dcItem });
    // Update the component on the canvas with new values from the html store
    // and link it  with the id to the html store
    // needed for new components
    component.addAttributes({ 'data-param-dec-id': dcItem.decId });
    component.set('content', dcItem.content);

    return component;

    // gets the default content to be displayed on the canvas. Should have been replaced by the `dcItem.content`
    // let dynConContent = '';
    // if (dcItem.decId) {
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
  createNewStoreItem(component) {
    const storeItemIdentifier = Mautic.createNewDynamicContentItem(mQuery);
    const decId = DynamicContentService.getDecId(storeItemIdentifier);
    component.addAttributes({
      'data-param-dec-id': decId,
    });

    // Replace token on canvas with user facing name: dcName
    component.set('content', `Dynamic Content ${decId}`);
    this.logger.debug('DC: Created a new Dynamic Content item in store', {
      decId,
      component,
    });

    return component;
  }

  /**
   * Extract the dynamic content index and the html id from a string:
   * e.g. href from dcTarget
   * decId = the readable number in the token. Starts with 1
   * htmlId = the html store item Id. It stars with 0
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
      decId: parseInt(result[2], 10) + 1, // 2
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
        decId: dcTarget.decId, // 1
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
