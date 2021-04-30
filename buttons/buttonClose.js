import UtilService from '../util.service';
import ButtonCloseCommands from './buttonClose.command';

export default class ButtonClose {
  editor;

  static modeEmailHtml = 'email-html';

  static modeEmailMjml = 'email-mjml';

  static modePageHtml = 'page-html';

  /**
   * Add close button with save for Mautic
   */
  constructor(editor) {
    if (!editor) {
      throw new Error('no editor');
    }
    this.editor = editor;

    const command = this.getCommand();

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
    cmd.add(command, {
      run: ButtonCloseCommands.closeEditorPageHtml,
    });
  }

  /**
   * Get the close command based on the editor mode
   */
  getCommand() {
    const mode = UtilService.getMode(this.editor);

    if (mode === ButtonClose.modePageHtml) {
      return 'mautic-editor-email-html-close';
    } else if (mode === ButtonClose.modeEmailHtml) {
      return 'mautic-editor-email-html-close';
    } else if (mode === ButtonClose.modeEmailMjml) {
      return 'mautic-editor-email-html-close';
    }
    throw new Error(`no valid builder mode: ${mode}`);
  }
}
