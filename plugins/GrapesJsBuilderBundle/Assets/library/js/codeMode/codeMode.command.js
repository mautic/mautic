import CodeEditor from './codeEditor';

export default class CodeModeCommand {
  /**
   * The command to run on button click
   */
  static name = 'preset-mautic:code-edit';

  codeEditor;

  static launchCodeEditorModal(editor, sender, opts) {
    if (!editor) {
      throw new Error('no editor');
    }

    if (!CodeModeCommand.codeEditor) {
      CodeModeCommand.codeEditor = new CodeEditor(editor, opts);
    }

    if (sender) {
      sender.set('active', 0);
    }

    CodeModeCommand.codeEditor.showCodePopup();

    // Transform DC to token
    editor.runCommand('preset-mautic:dynamic-content-slots-to-tokens');
  }

  static stopCodeEditorModal(editor) {
    if (!editor) {
      throw new Error('no editor');
    }
    // Transform Token to Slots
    editor.runCommand('preset-mautic:dynamic-content-tokens-to-slots');
  }
}
