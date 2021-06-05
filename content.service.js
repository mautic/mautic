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
   * Combine original header with editor styles
   * and editor html
   *
   * @param {GrapesJs Editor} editor
   * @returns HTMLDocument
   */
  static getHtmlDocument(editor) {
    const parser = new DOMParser();
    // get original doctype, header and add it to the html
    const originalContent = ContentService.getOriginalContent();
    const doctype = originalContent.doctype || '<!DOCTYPE html>';

    const htmlCombined = `${ContentService.serializeDoctype(
      doctype
    )}${editor.getHtml()}<style>${editor.getCss({
      avoidProtected: true,
    })}</style>`;

    const htmlDocument = parser.parseFromString(htmlCombined, 'text/html');

    if (originalContent.head) {
      htmlDocument.head.innerHTML += originalContent.head.innerHTML;
    }

    return htmlDocument;
  }

  /**
   * Returns the correct string for valid (HTML5) doctypes, eg:
   * <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN" "http://www.w3.org/TR/REC-html40/strict.dtd">
   *
   * @param {DocumentType}
   * @returns string
   */
  static serializeDoctype(doctype) {
    return new XMLSerializer().serializeToString(doctype);
  }

  /**
   * Get the selected themes original or the users last saved
   * content from the db. Loaded via Mautic PHP into the textarea.
   * @todo: add header for mjml
   * @returns HTMLDocument
   */
  static getOriginalContent() {
    // Parse HTML theme/template
    const parser = new DOMParser();
    const textareaHtml = mQuery('textarea.builder-html');
    // const textareaMjml = mQuery('textarea.builder-mjml');
    // @todo textareaMjml.val(),
    return parser.parseFromString(textareaHtml.val(), 'text/html');
  }
}
