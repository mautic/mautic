export default class ButtonService {
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
   * Get a form fields value by ID
   * @param string elementId
   * @return string
   */
  static getElementValue(elementId) {
    const field = document.getElementById(elementId);
    if (!field) {
      throw new Error(`Element '${elementId}' not found`);
    }

    return field.value;
  }

  /**
   * Set a form fields value by ID
   * @param elementId
   * @param value
   */
  static setElementValue(elementId, value) {
    const field = document.getElementById(elementId);

    if (!field) {
      throw new Error(`Element '${elementId}' not found`);
    }

    field.value = value;
  }

  /**
   * Get the email|page id to open preview
   *
   * @return string|null
   */
  static getInstanceId(form) {
    const url = form[0].action;
    const regexpEmailId = /(emails|pages)\/edit\/(\d+)$/g;
    const match = regexpEmailId.exec(url);

    return match ? match[2] : null;
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
    const date = ButtonService.getCurrentDate();

    return `${item} ${date}`;
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
   * Get the current date
   *
   * @return string
   */
  static getCurrentDate() {
    const today = new Date();

    return today.toLocaleString();
  }
}
