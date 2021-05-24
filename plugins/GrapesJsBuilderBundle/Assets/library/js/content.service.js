export default class ContentService {
  /**
   * copy of the original function in the preset.
   * @todo use the one from the preset
   * @deprecated
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

  /**
   * Extract all stylesheets from the template <head>
   * @todo move to preset
   */
  static getStyles() {
    const content = ContentService.getOriginalContent();

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
