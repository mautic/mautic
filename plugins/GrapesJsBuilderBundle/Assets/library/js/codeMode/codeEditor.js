import ContentService from '../../../../../../../grapesjs-preset-mautic/src/content.service';
// import grapesjsmautic from 'grapesjs-preset-mautic/src/content.service';

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
  showCodePopup() {
    this.updateEditorContents();
    // this.codeEditor.editor.refresh();
    // this.editor.Modal.setContent('');
    this.editor.Modal.setContent(this.codePopup);
    this.editor.Modal.setTitle(Mautic.translate('grapesjsbuilder.sourceEditModalTitle'));
    this.editor.Modal.open();
    this.editor.Modal.onceClose(() => {
      this.editor.stopCommand('preset-mautic:code-edit');
    });
  }

  // Update GrapesJs content
  updateCode() {
    const code = this.codeEditor.editor.getValue();
    // const codeToSave = ContentService.getCanvasAsHtmlDocument(this.editor);

    try {
      // delete canvas and set new content
      this.editor.DomComponents.getWrapper().set('content', '');
      this.editor.setComponents(code.trim());
      this.editor.Modal.close();
    } catch (e) {
      window.alert(`${Mautic.translate('grapesjsbuilder.sourceSyntaxError')} \n${e.message}`);
      // this.editor.DomComponents.getWrapper().set('content', '');
      // this.editor.setComponents(codeToSave.trim());
    }
  }

  // Close popup
  cancelCode() {
    this.editor.Modal.close();
  }

  /**
   * Set the content to be shown in the code editor
   */
  updateEditorContents() {
    // Check if MJML plugin is on
    // @todo use ContentService.getMode()
    // if ('grapesjsmjml' in cfg.pluginsOpts) {
    //   content = this.editor.getHtml();
    // } else {
    this.codeEditor.setContent(ContentService.getEditorHtmlContent(this.editor));
  }
}

export default CodeEditor;
