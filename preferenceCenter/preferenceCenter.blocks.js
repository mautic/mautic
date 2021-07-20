export default class PreferenceCenterBlocks {
  editor;

  opts;

  blockManager;

  constructor(editor, opts = {}) {
    this.editor = editor;
    this.opts = opts;
    this.blockManager = this.editor.BlockManager;
  }

  addPreferenceCenterBlock() {
    console.log('add preference center block');
    this.blockManager.add('category-list', {
      label: 'Category List',
      category : 'Prefrence Center',
      attributes: {
        class: 'gjs-fonts gjs-f-button'
      },
      content: 
        `<a href="#" target="_blank" class="button">Button</a>`,
    });
    this.blockManager.add('segment', {
      label: 'Segement',
      category : 'Prefrence Center',
      attributes: {
        class: 'gjs-fonts gjs-f-bars'
      },
      content: 
        `<a href="#" target="_blank" class="button">Button</a>`,
    });
    // this.blockManager.add('preference-center', {
    //   label: Mautic.translate('grapesjsbuilder.dynamicContentBlockLabel'),
    //   activate: true,
    //   select: true,
    //   attributes: { class: 'fa fa-tag' },
    //   content: {
    //     type: 'preference-center',
    //     content: '{preferencecenter="Preference Center"}',
    //     style: { padding: '10px' },
    //     activeOnRender: 1,
    //   },
    // });
  }

  
}
