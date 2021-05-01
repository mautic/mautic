import DynamicContentCommands from './dynamicContent.commands';
import DynamicContentService from './dynamicContent.service';

export default class DynamicContentListeners {
  editor;

  dcs;

  constructor(editor) {
    this.editor = editor;
    this.dcs = new DynamicContentService();
  }

  /**
   * On editor load: convert tokens to slots
   */
  onLoad() {
    this.editor.on('load', DynamicContentCommands.grapesConvertDynamicContentTokenToSlot);
  }
}
