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
   * Get the editors mjml and transform it to html
   * @param {Grapesjs Editor} editor
   * @returns string
   */
  static getEditorHtmlContent(editor) {
    // Try catch for mjml parser error
    try {
      const code = editor.runCommand('mjml-get-code');
      return code.html ? code.html.trim() : null;
    } catch (error) {
      console.warn(error.message);
      alert('Errors inside your template. Template will not be saved.');
    }
    return null;
  }

  /**
   * Get the editors mjml
   * @param {Grapesjs Editor} editor
   * @returns string
   */
  static getEditorMjmlContent(editor) {
    return editor.getHtml().trim();
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
      console.warn(error);
      return null;
    }
  }
}
