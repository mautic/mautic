class CodeEditor {
  constructor(editor, opts = {}) {
    this.editor = editor;
    this.opts = opts;

    this.codeEditor = this.buildCodeEditor();
    this.codePopup = this.buildCodePopup();
  }

  // Build codeEditor (CodeMirror instance)
  buildCodeEditor() {
    let codeEditor = this.editor.CodeManager.getViewer('CodeMirror').clone();

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

    let codePopup = document.createElement('div');
    let btnEdit = document.createElement('button');
    let btnCancel = document.createElement('button');
    let textarea = document.createElement('textarea');

    btnEdit.innerHTML = this.opts.sourceEditBtnLabel;
    btnEdit.className = cfg.stylePrefix + 'btn-prim ' + cfg.stylePrefix + 'btn-code-edit';
    btnEdit.onclick = this.updateCode.bind(this);

    btnCancel.innerHTML = this.opts.sourceCancelBtnLabel;
    btnCancel.className = cfg.stylePrefix + 'btn-prim ' + cfg.stylePrefix + 'btn-code-cancel';
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
    let code = this.codeEditor.editor.getValue();
    let codeSave = this.getEditorContent();

    // Catch error of code
    try {
      this.editor.DomComponents.getWrapper().set('content', '');
      this.editor.setComponents(code.trim());
      this.editor.Modal.close();
    } catch (e) {
      window.alert('Template error, you should fix your code before save! \n' + e.message);
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
    this.codeEditor.setContent(this.getEditorContent());
  }

  // Get formated GrapesJs code
  getEditorContent() {
    const cfg = this.editor.getConfig();
    let content;

    // Check if MJML plugin is on
    if ('grapesjsmjml' in cfg.pluginsOpts) {
      content = this.editor.getHtml();
    } else {
      content =
        this.editor.getHtml() +
        '<style>' +
        this.editor.getCss({ avoidProtected: true }) +
        '</style>';
    }

    return content;
  }
}

export default CodeEditor;
