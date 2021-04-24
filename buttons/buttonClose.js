import ButtonCloseCommands from './buttonClose.command';

export default class ButtonClose {
  /**
   * Add close button with save for Mautic
   */
  constructor(editor, command) {
    if (!command) {
      throw new Error('no close button command');
    }
    if (!editor) {
      throw new Error('no editor');
    }
    this.editor = editor;
    this.addButton(command);
    this.addCommand(command);
  }

  addButton(command) {
    this.editor.Panels.addButton('views', [
      {
        id: 'close',
        className: 'fa fa-times-circle',
        attributes: { title: 'Close' },
        command,
      },
    ]);
  }

  addCommand(command) {
    const cmd = this.editor.Commands;

    if (command === 'mautic-editor-page-html-close') {
      cmd.add(command, {
        run: ButtonCloseCommands.closeEditorPageHtml,
      });
    } else if (command === 'mautic-editor-email-mjml-close') {
      cmd.add(command, {
        run: ButtonCloseCommands.closeEditorEmailMjml,
      });
    } else if (command === 'mautic-editor-email-html-close') {
      cmd.add(command, {
        run: ButtonCloseCommands.closeEditorEmailHtml,
      });
    } else {
      throw new Error('no correct close command');
    }
  }
}
