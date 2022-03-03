export default class ButtonService {
  /**
   * Get form type. There are email and page form types
   *
   * @return string
   */
  static getFormType() {
    return document.getElementsByName('emailform').length ? 'email' : 'page';
  }

  /**
   * Get NodeList form object
   *
   * @return NodeListOf<HTMLElement>
   */
  static getForm() {
    return document.getElementsByName('emailform').length
      ? document.getElementsByName('emailform')
      : document.getElementsByName('page');
  }

  /**
   * Get the form item by ID
   *
   * @return object
   */
  static getFormItemById(itemId) {
    return document.getElementById(itemId);
  }

  /**
   * Get the email|page id to open preview
   *
   * @return string|null
   */
  static getInstanceId(form) {
    const url = form[0].action;
    const regexpEmailId = /\/edit\/(\d+)$/g;
    const match = regexpEmailId.exec(url);

    return match ? match[1] : null;
  }

  /**
   * Get jQuery email form object
   * Sent as a parameter to the Mautic functions
   *
   * @return object
   */
  static getMauticForm() {
    return mQuery('form[name=emailform]').length
      ? mQuery('form[name=emailform]')
      : mQuery('form[name=page]');
  }

  /**
   * Check if the the entity ID is temporary (for new entities)
   *
   * @return array|null
   */
  static isNewEntity() {
    return Mautic.isNewEntity('#page_sessionId, #emailform_sessionId');
  }

  /**
   * Generate a default value
   *
   * @return string
   */
  static getDefaultValue(value) {
    const item = ButtonService.capitalizeFirstLetter(value);

    return `${item} ${moment().format('YYYY-MM-D hh:mm:ss')}`;
  }

  /**
   * Replace the first letter with a capital letter
   *
   * @return string
   */
  static capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
  }

  /**
   * Compares two strings and returns an integer value that represents the result of the comparison:
   *  1 - string 1 less than string 2
   *  0 - string 1 equal string 2
   * -1 - string 1 greater than string 2
   *
   * @param string1
   * @param string2
   *
   * @returns Integer
   */
  static strcmp(string1, string2) {
    if (string1.toString() < string2.toString()) return -1;
    if (string1.toString() > string2.toString()) return 1;

    return 0;
  }
}
