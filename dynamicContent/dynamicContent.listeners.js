export default class DynamicContentListeners {
  editor;

  constructor(editor) {
    this.editor = editor;
  }

  /**
   * On editor load: convert existing tokens to slots
   */
  onLoad() {
    this.editor.on('load', () => {
      this.editor.runCommand('preset-mautic:update-dc-components-from-dc-store');
    });
  }
}
