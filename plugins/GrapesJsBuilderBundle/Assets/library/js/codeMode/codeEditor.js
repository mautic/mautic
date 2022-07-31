// import ContentService from '../../../../../../../grapesjs-preset-mautic/src/content.service';
import MjmlService from 'grapesjs-preset-mautic/dist/mjml/mjml.service';
import ContentService from 'grapesjs-preset-mautic/dist/content.service';
import Logger from 'grapesjs-preset-mautic/dist/logger';

class CodeEditor {
  #editor;

  logger;

  opts;

  codeEditor;

  codePopup;

  constructor(editor, opts = {}) {
    this.setEditor(editor);
    this.opts = opts;

    this.logger = new Logger(this.getEditor());
    this.codeEditor = this.buildCodeEditor();
    this.codePopup = this.buildCodePopup();
  }

  getEditor(){
    return this.#editor;
  }
  setEditor(editor){
    if (!editor) {
      throw new Error('no editor');
    }
    console.warn('setting the editor for code editor',{ editor });
    this.#editor = editor;
  }

  // Build codeEditor (CodeMirror instance)
  buildCodeEditor() {
    const codeEditor = this.getEditor().CodeManager.getViewer('CodeMirror').clone();

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
    const cfg = this.getEditor().getConfig();

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

  /**
   * Load content and show popup
   * @param {Editor} editor GrapesJs Editor
   */
  showCodePopup(editor) {
    this.logger.debug('Show the CodePopup',{ editor });
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
    if (ContentService.isMjmlMode(this.getEditor())) {
      MjmlService.mjmlToHtml(code);
    }

    try {
      // delete canvas and set new content
      this.getEditor().DomComponents.getWrapper().set('content', '');
      this.getEditor().setComponents(code.trim());
      this.getEditor().Modal.close();
    } catch (e) {
      window.alert(`${Mautic.translate('grapesjsbuilder.sourceSyntaxError')} \n${e.message}`);
    }
  }

  // Close popup
  cancelCode() {
    this.getEditor().Modal.close();
  }

  /**
   * Set the content to be edited in the popup editor
   */
  updateEditorContents() {

    // Check if MJML plugin is on
    let content;
    if (ContentService.isMjmlMode(this.getEditor())) {
      this.logger.debug('updateEditorContents mjml');
      content = MjmlService.getEditorMjmlContent(this.getEditor());
    } else {
      this.logger.debug('updateEditorContents html');
      content = ContentService.getEditorHtmlContent(this.getEditor());
    }
    this.codeEditor.setContent(content);
  }
}

export default CodeEditor;
