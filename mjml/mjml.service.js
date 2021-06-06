import mjml2html from 'mjml-browser';

export default class MjmlService {
  /**
   * Get the mjml document from the dom
   *
   * @returns string
   */
  static getOriginalContentMjml() {
    return mQuery('textarea.builder-mjml').val();
  }

  /**
   * Transform MJML to HTML
   * @todo show validation erros in the UI
   * @returns string
   */
  static mjmlToHtml(mjml) {
    try {
      if (typeof mjml !== 'string') {
        throw new Error('no valid mjml string');
      }
      return mjml2html(mjml, { validationLevel: 'strict' });
    } catch (error) {
      console.log(error);
      return null;
    }
  }
}
