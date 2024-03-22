export default class ButtonBlock {
  blockManager;

  constructor(editor) {
    this.blockManager = editor.BlockManager;
  }

  addButtonBlock() {
    const style = `<style>
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
                    </style>`;
    this.blockManager.add('button', {
      label: Mautic.translate('grapesjsbuilder.buttonBlockLabel'),
      category: Mautic.translate('grapesjsbuilder.categoryBlockLabel'),
      attributes: {
        class: 'gjs-fonts gjs-f-button',
      },
      content: `${style}
         <a href="#" target="_blank" class="button">Button</a>`,
      media: `<svg viewBox="0 0 24 24">
        <path fill="currentColor" d="M20 20.5C20 21.3 19.3 22 18.5 22H13C12.6 22 12.3 21.9 12 21.6L8 17.4L8.7 16.6C8.9 16.4 9.2 16.3 9.5 16.3H9.7L12 18V9C12 8.4 12.4 8 13 8S14 8.4 14 9V13.5L15.2 13.6L19.1 15.8C19.6 16 20 16.6 20 17.1V20.5M20 2H4C2.9 2 2 2.9 2 4V12C2 13.1 2.9 14 4 14H8V12H4V4H20V12H18V14H20C21.1 14 22 13.1 22 12V4C22 2.9 21.1 2 20 2Z" />
    </svg>`,
    });
  }
}
