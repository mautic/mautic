// import ContentService from '../../../../../../../grapesjs-preset-mautic/src/content.service';
import MjmlService from 'grapesjs-preset-mautic/dist/mjml/mjml.service';
import ContentService from 'grapesjs-preset-mautic/dist/content.service';
import ContentServiceExtension from '../content.service.extension.js';

class CodeEditor {
  editor;

  opts;

  codeEditor;

  codePopup;

  constructor(editor, opts = {}) {
    this.editor = editor;
    this.opts = opts;

    this.codeEditor = this.buildCodeEditor();
    this.codePopup = this.buildCodePopup();
  }

  // Build codeEditor (CodeMirror instance)
  buildCodeEditor() {
    const codeEditor = this.editor.CodeManager.getViewer('CodeMirror').clone();

    codeEditor.set({
      codeName: 'htmlmixed',
      readOnly: false,
      theme: 'hopscotch',
      autoBeautify: true,
      autoCloseTags: true,
      autoCloseBrackets: true,
      lineWrapping: true,
      styleActiveLine: true,
      smartIndent: true,
      indentWithTabs: true,
    });

    return codeEditor;
  }

  // Build popup content, codeEditor area and buttons
  buildCodePopup() {
    const cfg = this.editor.getConfig();

    const codePopup = document.createElement('div');
    const btnEdit = document.createElement('button');
    const btnCancel = document.createElement('button');
    const textarea = document.createElement('textarea');

    btnEdit.innerHTML = Mautic.translate('grapesjsbuilder.sourceEditBtnLabel');
    btnEdit.className = `${cfg.stylePrefix}btn-prim ${cfg.stylePrefix}btn-code-edit`;
    btnEdit.onclick = this.updateCode.bind(this);

    btnCancel.innerHTML = Mautic.translate('grapesjsbuilder.sourceCancelBtnLabel');
    btnCancel.className = `${cfg.stylePrefix}btn-prim ${cfg.stylePrefix}btn-code-cancel`;
    btnCancel.onclick = this.cancelCode.bind(this);

    codePopup.appendChild(textarea);
    codePopup.appendChild(btnEdit);
    codePopup.appendChild(btnCancel);

    this.codeEditor.init(textarea);

    return codePopup;
  }

  // Load content and show popup
  showCodePopup(editor) {
    this.updateEditorContents();
    // this.codeEditor.editor.refresh();
    // editor.Modal.setContent('');
    editor.Modal.setContent(this.codePopup);
    editor.Modal.setTitle(Mautic.translate('grapesjsbuilder.sourceEditModalTitle'));
    editor.Modal.open();

    editor.Modal.onceClose(() => editor.stopCommand('preset-mautic:code-edit'));
  }

  /**
   * Extract body content from full HTML document
   * @param {string} fullHtml - Complete HTML document
   * @returns {string} - Only the body content
   */
  extractBodyContent(fullHtml) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(fullHtml, 'text/html');

    // If there's no body tag, return the content as-is (it's already body content)
    if (!doc.body || !doc.body.innerHTML) {
      return fullHtml;
    }

    return doc.body.innerHTML;
  }

  /**
   * Extract head content from full HTML document
   * @param {string} fullHtml - Complete HTML document
   * @returns {string} - Only the head content
   */
  extractHeadContent(fullHtml) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(fullHtml, 'text/html');

    // If there's no head tag, return empty string
    if (!doc.head || !doc.head.innerHTML) {
      return '';
    }

    return doc.head.innerHTML;
  }

  /**
   * Update the main editors canvas content with the
   * content from modals editor.
   * @todo show validation results in UI
   */
  updateCode() {
    const code = this.codeEditor.editor.getValue();
    // validate MJML code
    if (ContentService.isMjmlMode(this.editor)) {
      MjmlService.mjmlToHtml(code);
    }

    try {
      // delete canvas and set new content
      this.editor.DomComponents.getWrapper().set('content', '');

      // Fix for head duplication issue: extract only body content when setting components
      let contentToSet = code.trim();
      let updatedHeadContent = '';

      if (!ContentService.isMjmlMode(this.editor)) {
        // For HTML mode, extract only body content to prevent head duplication
        contentToSet = this.extractBodyContent(code.trim());
        updatedHeadContent = this.extractHeadContent(code.trim());
      }

      this.editor.setComponents(contentToSet);

      // Store updated head content for later use by ContentService extension
      if (updatedHeadContent) {
        ContentServiceExtension.setUpdatedHeadContent(this.editor, updatedHeadContent);
      }

      // Reinitialize the content after parsing MJML.
      // This can be removed once the issue with self-closing tags is resolved in grapesjs-mjml.
      // See: https://github.com/GrapesJS/mjml/issues/149
      if (ContentService.isMjmlMode(this.editor)) {
        const parsedContent = MjmlService.getEditorMjmlContent(this.editor);
        this.editor.setComponents(parsedContent);
      }

      this.editor.Modal.close();
    } catch (e) {
      window.alert(`${Mautic.translate('grapesjsbuilder.sourceSyntaxError')} \n${e.message}`);
    }
  }

  // Close popup
  cancelCode() {
    this.editor.Modal.close();
  }

  /**
   * Set the content to be edited in the popup editor
   */
  updateEditorContents() {
    // Check if MJML plugin is on
    let content;
    if (ContentService.isMjmlMode(this.editor)) {
      content = MjmlService.getEditorMjmlContent(this.editor);
    } else {
      // Use extended ContentService to get content with updated head if available
      const extendedContentService = ContentServiceExtension.getExtendedContentService(this.editor);
      content = extendedContentService.getEditorHtmlContent();
    }
    this.codeEditor.setContent(content);
  }
}

export default CodeEditor;
