import CodeModeCommand from './codeMode.command';

export default class CodeModeButton {
  #editor;

  /**
   * Add close button with save for Mautic
   */
  constructor(editor) {
    if (!editor) {
      throw new Error('no editor');
    }
    this.setEditor(editor);
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

  addButton() {
    this.getEditor().Panels.addButton('options', [
      {
        id: 'code-edit',
        className: 'fa fa-edit',
        attributes: {
          title: Mautic.translate('grapesjsbuilder.sourceEditModalTitle'),
        },
        command: CodeModeCommand.name,
      },
    ]);
  }

  addCommand() {
    this.getEditor().Commands.add(CodeModeCommand.name, {
      run: CodeModeCommand.launchCodeEditorModal,
      stop: CodeModeCommand.stopCodeEditorModal,
    });
  }
}
