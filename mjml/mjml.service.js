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
      // html needs to be beautified for the click tracking to work. Therefore, we can
      // not use the built in command: mjml-get-code
      const mjml = this.getEditorMjmlContent(editor);
      const code = this.mjmlToHtml(mjml);

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
    // cleanId: Remove unnecessary IDs (eg. those created automatically)
    return editor.getHtml({ cleanId: true }).trim();
  }

  /**
   * Transform MJML to HTML
   * @todo show validation errors in the UI
   * @returns string
   */
  static mjmlToHtml(mjml, endpoint = '') {
    let html = '';

    try {
      if (typeof mjml !== 'string' || !mjml.includes('<mjml>')) {
        throw new Error('No valid MJML provided');
      }

      if (endpoint !== '') {
        html = MjmlService.mjmlToHtmlViaEndpoint(mjml, endpoint);
      } else {
        // html needs to be beautified for the click tracking to work.
        // strict mode not working with e.g. id="" and data-type parameters that
        // are e.g. used for Dynamic Content
        html = mjml2html(mjml, { beautify: true });
      }
    } catch (error) {
      console.warn(error);
    }

    return html;
  }

  /**
   * Transform MJML to HTML via endpoint
   * @returns string
   */
  static mjmlToHtmlViaEndpoint(mjml, endpoint) {
    const xmlHttpRequest = new XMLHttpRequest();
    xmlHttpRequest.open('POST', endpoint, false);
    xmlHttpRequest.setRequestHeader('Content-type', 'application/json');
    xmlHttpRequest.send(JSON.stringify({ mjml }));

    return xmlHttpRequest.responseText ? JSON.parse(xmlHttpRequest.responseText) : '';
  }
}
