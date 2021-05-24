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
   * Get a list of all existing assets (e.g. images)
   * to display in the assets manager, and the config
   *
   * @returns array
   */
  static getAssets() {
    const textareaAssets = mQuery('textarea#grapesjsbuilder_assets');
    const files = textareaAssets.val() ? JSON.parse(textareaAssets.val()) : [];
    const uploadPath = textareaAssets.data('upload');
    const deletePath = textareaAssets.data('delete');
    return {
      files,
      conf: {
        uploadPath,
        deletePath,
      },
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
