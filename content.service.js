export default class ContentService {
  static modeEmailHtml = 'email-html';

  static modeEmailMjml = 'email-mjml';

  static modePageHtml = 'page-html';

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
   */
  static getOriginalContent() {
    // Parse HTML theme/template
    const parser = new DOMParser();
    const textareaHtml = mQuery('textarea.builder-html');
    const fullHtml = parser.parseFromString(textareaHtml.val(), 'text/html');

    const content = fullHtml.body.innerHTML
      ? fullHtml.body.innerHTML
      : mQuery('textarea.builder-mjml').val();

    const { head } = fullHtml;

    return {
      head,
      content,
    };
  }
}
