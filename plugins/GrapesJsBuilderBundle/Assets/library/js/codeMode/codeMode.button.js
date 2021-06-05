import CodeModeCommand from './codeMode.command';

export default class CodeModeButton {
  editor;

  /**
   * The command to run on button click
   */
  command = 'preset-mautic:code-edit';

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
        className: 'fa fa-edit',
        attributes: {
          title: 'Edit Code', // @todo opts.sourceEditModalTitle
        },
        command: this.command,
      },
    ]);
  }

  addCommand() {
    this.editor.Commands.add(this.command, {
      run: CodeModeCommand.launchCodeEditorModal,
    });
  }
}
