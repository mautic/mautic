import mjml2html from 'mjml-browser';

export default class mjmlDocument {
  content;

  constructor(mjml) {
    this.content = mjml;
    // validate mjml
    this.toHtml(mjml);
  }

  /**
   * Get the mjml document
   * @returns string
   */
  getContent() {
    return this.content;
  }

  /**
   * Transform MJML to HTML
   * @todo show validation erros in the UI
   * @returns string
   */
  toHtml() {
    try {
      return mjml2html(this.content, { validationLevel: 'strict' });
    } catch (error) {
      console.log(error);
      return null;
    }
  }

  serialize() {
    return this.content;
  }
}
