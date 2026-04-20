// import ContentService from '../../../../../../../grapesjs-preset-mautic/src/content.service';
import MjmlService from 'grapesjs-preset-mautic/dist/mjml/mjml.service';
import ContentService from 'grapesjs-preset-mautic/dist/content.service';

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
   * Extract body content from HTML code.
   * If the code contains a full HTML document with <html> tag,
   * only extract the body content to prevent head elements from
   * being duplicated into the body when saved.
   * @param {string} code - The HTML code from the code editor
   * @returns {string} - The body content or original code if no html tag found
   */
  extractBodyContent(code) {
    const trimmedCode = code.trim();

    if (trimmedCode.includes('<html') || trimmedCode.includes('<!DOCTYPE')) {
      const parser = new DOMParser();
      const doc = parser.parseFromString(trimmedCode, 'text/html');
      const body = doc.body;

      if (body) {
        return body.innerHTML;
      }
    }

    return trimmedCode;
  }

  /**
   * Extract head content from HTML code.
   * Used to preserve user head modifications (styles, scripts, meta) when saving.
   * @param {string} code - The HTML code from the code editor
   * @returns {string|null} - The head innerHTML or null if no head found
   */
  extractHeadContent(code) {
    const trimmedCode = code.trim();

    if (trimmedCode.includes('<html') || trimmedCode.includes('<!DOCTYPE')) {
      const parser = new DOMParser();
      const doc = parser.parseFromString(trimmedCode, 'text/html');
      const head = doc.head;

      if (head && head.innerHTML.trim()) {
        return head.innerHTML;
      }
    }

    return null;
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

      let componentCode = code.trim();
      // Preserve user head modifications before stripping to body content
      let userHeadContent = null;
      if (!ContentService.isMjmlMode(this.editor)) {
        userHeadContent = this.extractHeadContent(code);
        componentCode = this.extractBodyContent(code);
      }

      this.editor.setComponents(componentCode);

      // Restore preserved head content after setComponents() clears it
      // This fixes the data-loss regression where head edits were silently dropped
      if (userHeadContent !== null) {
        const canvasDoc = this.editor.Canvas.getDocument();
        if (canvasDoc && canvasDoc.head) {
          canvasDoc.head.innerHTML = userHeadContent;
        }
      }

      // Reinitialize the content after parsing MJML.
      // This can be removed once the issue with self-closing tags is resolved in grapesjs-mjml.
      // See: https://github.com/GrapesJS/mjml/issues/149
      const parsedContent = MjmlService.getEditorMjmlContent(this.editor);
      this.editor.setComponents(parsedContent);

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
      content = ContentService.getEditorHtmlContent(this.editor);
    }
    this.codeEditor.setContent(content);
  }
}

export default CodeEditor;
