import Logger from 'grapesjs-preset-mautic/dist/logger';
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

    const logger = new Logger(editor);

    if (!CodeModeCommand.codeEditor) {
      CodeModeCommand.codeEditor = new CodeEditor(editor, opts);
      logger.debug('New CodeEditor created', CodeModeCommand.codeEditor);
    }else{
      logger.debug('Using existing CodeEditor', CodeModeCommand.codeEditor);
    }

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
