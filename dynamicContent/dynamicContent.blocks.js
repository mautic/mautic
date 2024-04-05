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
      media: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
        <path d="M0 80V229.5c0 17 6.7 33.3 18.7 45.3l176 176c25 25 65.5 25 90.5 0L418.7 317.3c25-25 25-65.5 0-90.5l-176-176c-12-12-28.3-18.7-45.3-18.7H48C21.5 32 0 53.5 0 80zm112 32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/>
      </svg>`,
      content: {
        type: 'dynamic-content',
        content: '{dynamiccontent="Dynamic Content"}',
        style: { padding: '10px' },
        activeOnRender: 1,
      },
    });
  }
}
