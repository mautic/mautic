import Logger from '../logger';

export default class DynamicContentService {
  /**
   * Dynamic content tabs on the base html (hidden)
   * Each "tab" corresponds to a dynamic content block on the canvas.
   * References by the tab.title (e.g. Dynamic Content)
   */
  dynamicContentItems = [];

  constructor(editor) {
    this.logger = new Logger(editor);
  }

  /**
   * Get the name of the dynamic content element.
   * Used as identifier
   * E.g. Dynamic Content from: {dynamiccontent="Dynamic Content"}
   * @param {GrapesJS Component} component
   * @returns string | null
   */
  static getTokenName(component) {
    const regex = RegExp(/\{dynamiccontent="(.*)"\}/, 'g');
    const regexEx = regex.exec(component.get('content'));

    return regexEx[1] || null;
  }

  /**
   * Convert a text token to the default variant
   * Wire up the ids
   *
   * @param {GrapesJS Component} component
   */
  manageDynamicContentTokenToSlot(component) {
    this.getDynamicContentItems();

    let dynContentName = DynamicContentService.getTokenName(component);
    if (!dynContentName) {
      return false;
    }

    // if it is a new component (dropped to the canvas)
    // add an id and create a corresponding item in the html store
    if (dynContentName === 'Dynamic Content') {
      console.log(this.dynamicContentItems.length);
      dynContentName += ` ${this.dynamicContentItems.length}`;
    }

    // on the other hand, load the existing meta data from the html store

    console.log('this.dynamicContentItems');
    console.log(this.dynamicContentItems);

    // get the tab/item matching the dynamic content on the canvas
    const dynamicContentTab = this.dynamicContentItems.find((tab) => tab.title === dynContenName);

    // If dynamic content item exists -> fill
    // Hint: the first dynamic content item (tab) is created from php: #emailform_dynamicContent_0
    if (dynamicContentTab) {
      this.logger.debug('Using existing dynamic content item', { dynamicContentTab });
      const dynContentTarget = DynamicContentService.getDynContentTarget(dynamicContentTab.href);

      let dynConContent = '';
      if (dynContentTarget.decId) {
        const dynConContainer = mQuery(dynContentTarget.htmlId).find(dynContentTarget.content);

        // is there content in the current editor?
        if (dynConContainer.hasClass('editor')) {
          dynConContent = dynConContainer.froalaEditor('html.get');
        } else {
          dynConContent = dynConContainer.html();
        }
      }

      if (dynConContent === '') {
        // fallback to component label text on canvas
        dynConContent = dynamicContentTab.title;
      }

      component.addAttributes({
        'data-param-dec-id': dynContentTarget.decId,
      });
      component.set('content', dynConContent);
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
        dynContenName,
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
  static getDynContentName(dynContentTarget) {
    return `Dynamic Content ${dynContentTarget.decId + 1}`;
  }

  /**
   * Get the default content
   * @param {string} id id of the textarea
   * @returns string with the html in the textarea
   */
  static getDynContentContent(id) {
    return mQuery(id).val();
  }

  /**
   * Get all Dynamic Content items from the HTML store
   * @returns array of objects with title and href
   */
  getDynamicContentItems() {
    this.dynamicContentItems = [];

    //
    mQuery('#dynamicContentContainer .dynamic-content').each((index, value) => {
      console.warn({ index });
      console.warn(value);
      const dynContentTarget = DynamicContentService.getDynContentTarget(`#${value.id}`);
      console.warn({ dynContenttarget: dynContentTarget });

      // if (value.href.indexOf('javascript') >= 0) {
      //   return;
      // }
      this.dynamicContentItems.push({
        identifier: value.id, // emailform_dynamicContent_0
        id: dynContentTarget.decId, // 0
        name: DynamicContentService.getDynContentName(dynContentTarget), // Dynamic Content 1
        content: DynamicContentService.getDynContentContent(dynContentTarget.content), // Default Dynamic Content
      });
    });

    if (!this.dynamicContentItems) {
      throw Error('No dynamic content store item found');
    }
  }
}
