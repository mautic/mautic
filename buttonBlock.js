export default class ButtonBlock {
  editor;

  opts;

  blockManager;

  constructor(editor, opts = {}) {
    this.editor = editor;
    this.opts = opts;
    this.blockManager = this.editor.BlockManager;
  }

  addButtonBlock() {
    this.blockManager.add('button', {
      label: Mautic.translate('grapesjsbuilder.buttonBlockLabel'),
      category : Mautic.translate('grapesjsbuilder.categoryBlockLabel'),
      attributes: {
        class: 'gjs-fonts gjs-f-button'
      },
      content: 
        `<style>
            .button {
              display:inline-block;
              text-decoration:none;
              border-color:#4e5d9d;
              border-width:10px 20px;
              border-style:solid;
              -webkit-border-radius:3px;
              -moz-border-radius:3px;
              border-radius:3px;
              background-color:#4e5d9d;
              font-size:16px;
              color:#ffffff;
            }           
         </style>
         <a href="#" target="_blank" class="button">Button</a>`,
    });
  }
}
