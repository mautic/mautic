import CodeModeCommand from './codeMode.command';

export default class CodeModeButton {
  editor;

  /**
   * Add close button with save for Mautic
   */
  constructor(editor) {
    if (!editor) {
      throw new Error('no editor');
    }
    this.editor = editor;
  }

  addButton() {
    this.editor.Panels.addButton('options', [
      {
        id: 'code-edit',
        className: 'ri-edit-line',
        attributes: {
          title: Mautic.translate('grapesjsbuilder.sourceEditModalTitle'),
        },
        command: CodeModeCommand.name,
      },
    ]);
  }

  addCommand() {
    this.editor.Commands.add(CodeModeCommand.name, {
      run: CodeModeCommand.launchCodeEditorModal,
      stop: CodeModeCommand.stopCodeEditorModal,
    });
  }
}
