import CodeEditor from './codeEditor';

export default class CodeModeCommand {
  // The command to run on button click
  static get name() {
    return 'preset-mautic:code-edit';
  }

  static launchCodeEditorModal(editor, sender, opts = {}) {
    if (!editor) throw new Error('CodeModeCommand: editor is required');

    // If previously created, ensure any guards are cleaned up before making a new instance
    if (CodeModeCommand.codeEditor && typeof CodeModeCommand.codeEditor.cleanupModalGuards === 'function') {
      try {
        CodeModeCommand.codeEditor.cleanupModalGuards();
      } catch (e) {
        // Log and continue—better to show the modal than block on cleanup
        console.error('CodeModeCommand: cleanupModalGuards failed', e); // NOSONAR
      }
    }

    CodeModeCommand.codeEditor = new CodeEditor(editor, opts);

    // Deactivate the toolbar button if present
    if (sender && typeof sender.set === 'function') {
      try {
        sender.set('active', 0);
      } catch (e) {
        console.error('CodeModeCommand: failed to deactivate sender', e); // NOSONAR
      }
    }

    CodeModeCommand.codeEditor.showCodePopup(editor);

    // Transform DC Component to token
    try {
      editor.runCommand('preset-mautic:dynamic-content-components-to-tokens');
    } catch (e) {
      console.error('CodeModeCommand: DC components to tokens failed', e); // NOSONAR
    }
  }

  static stopCodeEditorModal(editor) {
    if (!editor) throw new Error('CodeModeCommand: editor is required');

    // Transform Token to Components
    try {
      editor.runCommand('preset-mautic:update-dc-components-from-dc-store');
    } catch (e) {
      console.error('CodeModeCommand: update-dc-components-from-dc-store failed', e); // NOSONAR
    }
  }
}

// ES5-safe static property:
CodeModeCommand.codeEditor = null;
