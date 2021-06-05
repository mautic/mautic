import ContentService from '../../../../../../../grapesjs-preset-mautic/src/content.service';

// import grapesjsmautic from 'grapesjs-preset-mautic';

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

    btnEdit.innerHTML = this.opts.sourceEditBtnLabel;
    btnEdit.className = `${cfg.stylePrefix}btn-prim ${cfg.stylePrefix}btn-code-edit`;
    btnEdit.onclick = this.updateCode.bind(this);

    btnCancel.innerHTML = this.opts.sourceCancelBtnLabel;
    btnCancel.className = `${cfg.stylePrefix}btn-prim ${cfg.stylePrefix}btn-code-cancel`;
    btnCancel.onclick = this.cancelCode.bind(this);

    codePopup.appendChild(textarea);
    codePopup.appendChild(btnEdit);
    codePopup.appendChild(btnCancel);

    this.codeEditor.init(textarea);

    return codePopup;
  }

  // Load content and show popup
  showCodePopup() {
    this.updateEditorContents();
    this.codeEditor.editor.refresh();

    this.editor.Modal.setContent('');
    this.editor.Modal.setContent(this.codePopup);
    this.editor.Modal.setTitle(this.opts.sourceEditModalTitle);
    this.editor.Modal.open();
  }

  // Update GrapesJs content
  updateCode() {
    const code = this.codeEditor.editor.getValue();
    const codeSave = this.getEditorContent();

    // Catch error of code
    try {
      this.editor.DomComponents.getWrapper().set('content', '');
      this.editor.setComponents(code.trim());
      this.editor.Modal.close();
    } catch (e) {
      window.alert(`Template error, you should fix your code before save! \n${e.message}`);
      this.editor.DomComponents.getWrapper().set('content', '');
      this.editor.setComponents(codeSave.trim());
    }
  }

  // Close popup
  cancelCode() {
    this.editor.Modal.close();
  }

  // Update CodeMirror content
  updateEditorContents() {
    // CodeModeCommand.codeEditor.codeEditor.setContent(htmlcontent);
    this.codeEditor.setContent(this.getEditorContent());
  }

  /**
   * Get complete current html. Including doctype and original header.
   * @returns string
   */
  getEditorContent() {
    const cfg = this.editor.getConfig();
    let content;

    // Check if MJML plugin is on
    if ('grapesjsmjml' in cfg.pluginsOpts) {
      content = this.editor.getHtml();
    } else {
      const contentDocument = ContentService.getHtmlDocument(this.editor);
      console.warn(contentDocument.doctype);

      if (!contentDocument || !contentDocument.body) {
        throw new Error('No html content found');
      }
      content =
        ContentService.serializeDoctype(contentDocument.doctype) +
        contentDocument.head.outerHTML +
        contentDocument.body.outerHTML;
    }

    return content;
  }
}

export default CodeEditor;
