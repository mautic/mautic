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

      let components = code.trim();

      // For HTML mode (page/email-html), extract body content if the code
      // contains a complete HTML document with head/body tags.
      // This prevents head content from being duplicated into the body.
      // See: https://github.com/mautic/mautic/issues/14409
      if (!ContentService.isMjmlMode(this.editor)) {
        components = this.extractBodyContent(components);
      }
      this.editor.setComponents(components);

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

  /**
   * Extract body content from a full HTML document string.
   * If the input is not a complete HTML document, return it as-is.
   *
  * @param {string} html - The HTML string to parse
   * @returns {string} - The body innerHTML or the original string
   */
  extractBodyContent(html) {
    // Check if this looks like a complete HTML document
    const hasHtmlTag = /<html[\s>]/i.test(html);
    const hasBodyTag = /<body[\s>]/i.test(html);
    const hasHeadTag = /<head[\s>]/i.test(html);
    // Only extract body if we have a complete document structure
    if (!hasHtmlTag && !hasBodyTag && !hasHeadTag) {
      return html;
    }
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');

    // Return body innerHTML, or original if no body found
    return doc.body ? doc.body.innerHTML.trim() : html;
  }
}

export default CodeEditor;
