import Logger from '../logger';

export default class DynamicContentService {
  /**
   * Dynamic content tabs on the base html (hidden)
   * Each "tab" corresponds to a dynamic content block on the canvas.
   * References by the tab.title (e.g. Dynamic Content)
   */
  dynamicContentTabs = [];

  constructor(editor) {
    this.logger = new Logger(editor);
  }

  /**
   * Convert a text token to the default variant
   * Wire up the ids
   *
   * @param {JQuery Object} component
   */
  manageDynamicContentTokenToSlot(component) {
    const regex = RegExp(/\{dynamiccontent="(.*)"\}/, 'g');

    const content = component.get('content');
    const regexEx = regex.exec(content);
    this.getDynamicContentTabs();

    // abort if component is empty or does not appear to be valid
    if (regexEx === null || !regexEx[1]) {
      return false;
    }

    // Get the name of the dynamic content element.
    // E.g. Dynamic Content from: {dynamiccontent="Dynamic Content"}
    const dynContenName = regexEx[1];

    // get the tab/item matching the dynamic content on the canvas
    const dynamicContentTab = this.dynamicContentTabs.find((tab) => tab.title === dynContenName);

    // If dynamic content item exists -> fill
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
      throw new Error('no DynamicContent target found');
    }
    return {
      htmlId: `${result[1]}${result[2]}`, // #emailform_dynamicContent_1
      decId: parseInt(result[2], 10), // 1
      content: `${result[1]}${result[2]}_content`, // #emailform_dynamicContent_1_content
    };
  }

  /**
   * Load all
   */
  getDynamicContentTabs() {
    this.dynamicContentTabs = [];

    mQuery('#dynamicContentTabs a').each((index, value) => {
      if (value.href.indexOf('javascript') >= 0) {
        return;
      }
      this.dynamicContentTabs.push({
        title: value.innerHTML.trim(),
        href: value.href,
      });
    });

    if (!this.dynamicContentTabs) {
      throw Error('No dynamic content tabs found');
    }
  }
}
