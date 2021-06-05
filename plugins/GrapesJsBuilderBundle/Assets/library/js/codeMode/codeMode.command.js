import CodeEditor from './codeEditor';

export default class CodeModeCommand {
  codeEditor;

  static launchCodeEditorModal(editor, sender, opts) {
    if (!editor) {
      throw new Error('no editor');
    }

    if (!CodeModeCommand.codeEditor) {
      CodeModeCommand.codeEditor = new CodeEditor(editor, opts);
    }

    if (!sender) {
      sender.set('active', 0);
    }

    CodeModeCommand.codeEditor.showCodePopup();

    // Transform DC to token
    editor.runCommand('preset-mautic:dynamic-content-slots-to-tokens');
  }
}
