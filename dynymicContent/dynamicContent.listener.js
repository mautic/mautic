import DynamicContentService from './dynamicContent.service';

export default class DynamicContentListeners {
  constructor(editor, opts = {}) {
    this.editor = editor;
    this.opts = opts;

    this.editor.on('load', DynamicContentService.grapesConvertDynamicContentTokenToSlot);
  }
}
