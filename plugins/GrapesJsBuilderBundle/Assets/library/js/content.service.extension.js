import ContentService from 'grapesjs-preset-mautic/dist/content.service';

/**
 * Extension to ContentService that handles updated head content from code editor
 */
class ContentServiceExtension {
  /**
   * Get the original ContentService with head content override capability
   * @param {Object} editor - GrapesJS editor instance
   * @returns {Object} - Extended ContentService methods
   */
  static getExtendedContentService(editor) {
    return {
      /**
       * Get complete current html with updated head content if available
       * @returns {string} - Complete HTML document
       */
      getEditorHtmlContent: () => {
        if (!editor) {
          throw new Error('Editor is required.');
        }

        const contentDocument = ContentService.getCanvasAsHtmlDocument(editor);

        if (!contentDocument || !contentDocument.body) {
          throw new Error('No html content found');
        }

        // If there's updated head content from code editor, use it
        if (editor.updatedHeadContent) {
          contentDocument.head.innerHTML = editor.updatedHeadContent;
        }

        return ContentService.serializeHtmlDocument(contentDocument);
      },

      /**
       * Get canvas as HTML document with updated head content if available
       * @returns {HTMLDocument} - HTML document
       */
      getCanvasAsHtmlDocument: () => {
        const contentDocument = ContentService.getCanvasAsHtmlDocument(editor);

        // If there's updated head content from code editor, use it
        if (editor.updatedHeadContent) {
          contentDocument.head.innerHTML = editor.updatedHeadContent;
        }

        return contentDocument;
      },

      /**
       * Check if editor is in MJML mode
       * @returns {boolean} - True if in MJML mode
       */
      isMjmlMode: () => {
        return ContentService.isMjmlMode(editor);
      },

      /**
       * Get the mode of the editor
       * @returns {string} - Editor mode
       */
      getMode: () => {
        return ContentService.getMode(editor);
      },
    };
  }

  /**
   * Clear updated head content from editor
   * @param {Object} editor - GrapesJS editor instance
   */
  static clearUpdatedHeadContent(editor) {
    if (editor && editor.updatedHeadContent) {
      delete editor.updatedHeadContent;
    }
  }

  /**
   * Set updated head content on editor
   * @param {Object} editor - GrapesJS editor instance
   * @param {string} headContent - Updated head content
   */
  static setUpdatedHeadContent(editor, headContent) {
    if (editor && headContent) {
      editor.updatedHeadContent = headContent;
    }
  }
}

export default ContentServiceExtension;
