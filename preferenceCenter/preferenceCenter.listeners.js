export default class PreferenceCenterListeners {
  editor;

  constructor(editor) {
    this.editor = editor;
  }

  /**
   * On editor load: convert tokens to slots
   */
  onLoad() {
    this.editor.on('load', () => {
      this.editor.runCommand('preset-mautic:preference-center-tokens-to-slots');
    });
  }
}
