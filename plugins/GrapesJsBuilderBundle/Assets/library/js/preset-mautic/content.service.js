import Logger from './logger';

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
   * Get the current Canvas content as complete HTML document:
   * Combine original doctype, header, editor styles and content
   *
   * @param {GrapesJs Editor} editor
   * @returns HTMLDocument
   */
  static getCanvasAsHtmlDocument(editor) {
    const parser = new DOMParser();
    const logger = new Logger(editor);

    // get original doctype, header and add it to the html
    const originalContent = ContentService.getOriginalContentHtml();
    const doctype = ContentService.serializeDoctype(originalContent.doctype);

    /**
     * Sanitize the content. This updates the originalContent variable directly
     * (as it's passed by reference), so we don't need to re-assign anything here
     */
    Mautic.sanitizeHtmlBeforeSave(mQuery(originalContent));

    // FIX for issue #14409: Extract body content from editor HTML to prevent head duplication
    // editor.getHtml() may return complete HTML document (with head/body) after code edit mode
    const editorHtml = editor.getHtml();
    const editorDoc = parser.parseFromString(editorHtml, 'text/html');
    const bodyContent = editorDoc.body ? editorDoc.body.innerHTML : editorHtml;

    const htmlCombined = `${doctype}<html>${bodyContent}<style>${editor.getCss({
      avoidProtected: true,
    })}</style></html>`;

    // get a DocumentHTML from the string
    const htmlDocument = parser.parseFromString(htmlCombined, 'text/html');

    // if no header is set on the canvas, replace it with existing from theme
    if (!htmlDocument.head.innerHTML && originalContent.head.innerHTML) {
      logger.debug('Set head based on the original content', {
        head: originalContent.head.innerHTML,
      });
      htmlDocument.head.innerHTML = originalContent.head.innerHTML;
    }

    return htmlDocument;
  }

  /**
   * Get complete current html. Including doctype and original header.
   * @returns string
   */
  static getEditorHtmlContent(editor) {
    if (!editor) {
      throw new Error('Editor is required.');
    }

    const contentDocument = ContentService.getCanvasAsHtmlDocument(editor);

    if (!contentDocument || !contentDocument.body) {
      throw new Error('No html content found');
    }
    return ContentService.serializeHtmlDocument(contentDocument);
  }

  /**
   * Serialize a DocumentHTML Object to a <html> string
   * @param {DocumentHTML} contentDocument
   */
  static serializeHtmlDocument(contentDocument) {
    if (!contentDocument || !(contentDocument instanceof HTMLDocument)) {
      throw new Error('No Html Document');
    }

    // @todo add the lang parameter. E.g. <html lang="de-DE">
    return `${ContentService.serializeDoctype(contentDocument.doctype)}<html>${
      contentDocument.head.outerHTML
    }${contentDocument.body.outerHTML}</html>`;
  }

  /**
   * Returns the correct string for valid (HTML5) doctypes, eg:
   * <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN" "http://www.w3.org/TR/REC-html40/strict.dtd">
   *
   * @param {DocumentType}
   * @returns string
   */
  static serializeDoctype(doctype) {
    if (!doctype) {
      return '';
    }
    return new XMLSerializer().serializeToString(doctype);
  }

  /**
   * Get the selected themes original or the users last saved
   * content from the db. Loaded via Mautic PHP into the textarea.
   * @returns HTMLDocument
   */
  static getOriginalContentHtml() {
    // Parse HTML theme/template
    const parser = new DOMParser();
    const textareaHtml = mQuery('textarea.builder-html');
    const doc = parser.parseFromString(textareaHtml.val(), 'text/html');
    if (!doc.body.innerHTML || !doc.head.innerHTML) {
      throw new Error('No valid HTML template found');
    }
    return doc;
  }

  /**
   * Extract all stylesheets from the template <head>
   * @todo use DocumentHTML Styles directly
   */
  static getStyles() {
    const content = ContentService.getOriginalContentHtml();

    if (!content.head) {
      return [];
    }
    const links = content.head.querySelectorAll('link');
    const styles = [];

    if (links) {
      links.forEach((link) => {
        if (link && link.rel === 'stylesheet') {
          styles.push(link.href);
        }
      });
    }

    return styles;
  }
}
