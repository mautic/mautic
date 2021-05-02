import DynamicContentCommands from './dynamicContent.commands';

export default class DynamicContentListeners {
  editor;

  constructor(editor) {
    this.editor = editor;
  }

  /**
   * On editor load: convert tokens to slots
   */
  onLoad() {
    this.editor.on('load', DynamicContentCommands.grapesConvertDynamicContentTokenToSlot);
  }
}
