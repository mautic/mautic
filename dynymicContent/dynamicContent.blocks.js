export default class DynamicContentBlocks {
  editor;

  opts;

  blockManager;

  constructor(editor, opts = {}) {
    this.editor = editor;
    this.opts = opts;
    this.blockManager = this.editor.BlockManager;
  }

  addDynamciContentBlock() {
    this.blockManager.add('dynamic-content', {
      label: Mautic.translate('grapesjsbuilder.dynamicContentBlockLabel'),
      activate: true,
      select: true,
      attributes: { class: 'fa fa-tag' },
      content: {
        type: 'dynamic-content',
        content: '{dynamiccontent="Dynamic Content"}',
        style: { padding: '10px' },
        activeOnRender: 1,
      },
    });
  }
}
