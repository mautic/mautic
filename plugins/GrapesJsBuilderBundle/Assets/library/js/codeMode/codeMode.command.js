import CodeEditor from './codeEditor';

export default class CodeModeCommand {
  /**
   * The command to run on button click
   */
  static name = 'preset-mautic:code-edit';

  static codeEditor;

  static launchCodeEditorModal(editor, sender, opts) {
    if (!editor) {
      throw new Error('no editor');
    }

    CodeModeCommand.codeEditor = new CodeEditor(editor, opts);

    if (sender) {
      sender.set('active', 0);
    }

    CodeModeCommand.codeEditor.showCodePopup(editor);

    // Transform DC Component to token
    editor.runCommand('preset-mautic:dynamic-content-components-to-tokens');
  }

  static stopCodeEditorModal(editor) {
    if (!editor) {
      throw new Error('no editor');
    }
    // Transform Token to Components
    editor.runCommand('preset-mautic:update-dc-components-from-dc-store');
  }
}
