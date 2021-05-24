export default class ContentService {
  static modeEmailHtml = 'email-html';

  static modeEmailMjml = 'email-mjml';

  static modePageHtml = 'page-html';

  static isMjmlMode(editor) {
    if (!editor) {
      throw new Error('editor is required.');
    }
    return ContentService.getMode(editor) === ContentService.modeEmailMjml;
  }

  static getMode(editor) {
    const cfg = editor.getConfig();

    if (
      !cfg.pluginsOpts ||
      !cfg.pluginsOpts.grapesjsmautic ||
      !cfg.pluginsOpts.grapesjsmautic.mode
    ) {
      throw new Error('Wrong Mautic Grapesjs mode');
    }

    return cfg.pluginsOpts.grapesjsmautic.mode;
  }

  /**
   * Get the selected themes original or the users last saved
   * content from the db. Loaded via Mautic PHP into the textarea.
   * @todo: add header for mjml
   * @returns object  head and body as string
   */
  static getOriginalContent() {
    // Parse HTML theme/template
    const parser = new DOMParser();
    const textareaHtml = mQuery('textarea.builder-html');
    const textareaMjml = mQuery('textarea.builder-mjml');
    const htmlDocument = parser.parseFromString(textareaHtml.val(), 'text/html');

    return {
      head: htmlDocument.head,
      body: htmlDocument.body.innerHTML || textareaMjml.val(),
    };
  }
}
