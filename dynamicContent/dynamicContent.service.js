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
  manageDynamicContentTokenToSlot(component) {
    this.getDcStoreItems();
    this.getDcComponents();

    let dynContentName = this.getTokenName(component);
    if (!dynContentName) {
      return false;
    }

    // if it is a new component (dropped to the canvas)
    // give it a new id (and create a corresponding item in the html store)
    if (dynContentName === 'Dynamic Content') {
      dynContentName += ` ${this.dcComponents.length}`;
    }

    // get the item/tab matching the dynamic content on the canvas
    const dynamicContentItem = this.dcStoreItems.find((item) => item.name === dynContentName);

    // If dynamic content item exists -> fill
    // Hint: the first dynamic content item (tab) is created from php: #emailform_dynamicContent_0
    if (dynamicContentItem) {
      this.logger.debug('Using existing dynamic content item', { dynamicContentItem });

      // let dynConContent = '';
      // if (dynamicContentItem.id) {
      //   const dynConContainer = mQuery(dynContentTarget.htmlId).find(dynContentTarget.content);

      //   // is there content in the current editor?
      //   if (dynConContainer.hasClass('editor')) {
      //     dynConContent = dynConContainer.froalaEditor('html.get');
      //   } else {
      //     dynConContent = dynConContainer.html();
      //   }
      // }

      component.addAttributes({
        'data-param-dec-id': dynamicContentItem.id,
      });
      component.set('content', dynamicContentItem.content);
    } else {
      // If dynamic content item in html store doesn't exist -> create
      // @todo replace mQuery('#dynamicContentTabs') with class property
      const dynConTarget = Mautic.createNewDynamicContentItem(mQuery);
      const dynConTab = mQuery('#dynamicContentTabs').find(`a[href="${dynConTarget}"]`);

      // get ID: e.g. #emailform_dynamicContent_1
      component.addAttributes({
        'data-param-dec-id': parseInt(dynConTarget.replace(/[^0-9]/g, ''), 10),
      });
      // Replace token on canvas with user facing "label" from html store
      component.set('content', dynConTab.text());
      this.logger.debug('Created a new dynamic content item', {
        dynContentName,
        content: dynConTab.text(),
      });
    }
    return true;
  }

  /**
   * Extract the dynamic content index and the html id from a string:
   * e.g. href from dynContentTarget
   *
   * @param {string} identifier e.g. http://localhost:1234/#emailform_dynamicContent_1
   */
  static getDynContentTarget(identifier) {
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
  static getDynContentName(target) {
    return `Dynamic Content ${target.decId + 1}`;
  }

  /**
   * Get the default content
   * @param {string} id id of the textarea
   * @returns string with the html in the textarea
   */
  static getDynContentContent(target) {
    return mQuery(target.id).val() || DynamicContentService.getDynContentName(target);
  }

  /**
   * Get all Dynamic Content items from the HTML store
   * @returns array of objects with title and href
   */
  getDcStoreItems() {
    this.dcStoreItems = [];

    // #dynamicContentContainer
    mQuery('.dynamic-content').each((index, value) => {
      const dynContentTarget = DynamicContentService.getDynContentTarget(`#${value.id}`);

      this.dcStoreItems.push({
        identifier: value.id, // emailform_dynamicContent_0
        id: dynContentTarget.decId, // 0
        name: DynamicContentService.getDynContentName(dynContentTarget), // Dynamic Content 1
        content: DynamicContentService.getDynContentContent(dynContentTarget), // Default Dynamic Content
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

    const domComponents = this.editor.DomComponents;
    const wrapperChildren = domComponents.getComponents();
    this.dcComponents = [];

    wrapperChildren.forEach((comp) => {
      if (comp.is('dynamic-content')) {
        this.dcComponents.push(comp);
      }
    });

    return this.dcComponents;
  }
}
