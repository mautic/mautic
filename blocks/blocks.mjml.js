export default class BlocksMjml {
  blockManager;

  editor;

  constructor(editor) {
    this.editor = editor;
    this.blockManager = editor.BlockManager;
  }

  addBlocks() {
    const sections37 = `<mj-column width="30%"><mj-text>Content 1</mj-text></mj-column>
        <mj-column width="70%"><mj-text>Content 2</mj-text></mj-column>`;

    this.blockManager.add('mj-37-columns', {
      label: Mautic.translate('grapesjsbuilder.components.names.twoColumnThirdSevens'),
      category: Mautic.translate('grapesjsbuilder.categorySectionLabel'),
      attributes: { class: 'gjs-fonts gjs-f-b37' },
      content: `<mj-section>${sections37}</mj-section>`,
    });

    const textSect = `<mj-column>
          <mj-text font-size="18px" font-weight="bold">
            Insert title here
          </mj-text>
          <mj-text>
            Insert text here
          </mj-text>
        </mj-column>`;

    this.blockManager.add('text-sect', {
      label: Mautic.translate('grapesjsbuilder.components.names.textSectionBlkLabel'),
      category: Mautic.translate('grapesjsbuilder.reusableDynamicContentBlockLabel'),
      attributes: { class: 'gjs-fonts gjs-f-h1p' },
      content: `<mj-section>${textSect}</mj-section>`,
    });

    const gridItem = `<mj-group>
        <mj-column>
          <mj-image height="auto" src="https://via.placeholder.com/172x215/#7f7f7f/ffffff?text=172x215+x2"></mj-image>
          <mj-text font-size="18px" font-weight="bold" align="center">
            Insert title here
          </mj-text>
          <mj-text align="center">
            Insert text here
          </mj-text>
        </mj-column>
        <mj-column>
          <mj-image height="auto" src="https://via.placeholder.com/172x215/#7f7f7f/ffffff?text=172x215+x2"></mj-image>
          <mj-text font-size="18px" font-weight="bold" align="center">
            Insert title here
          </mj-text>
          <mj-text align="center">
            Insert text here
          </mj-text>
        </mj-column>
      </mj-group>`;

    this.blockManager.add('grid-items', {
      label: Mautic.translate('grapesjsbuilder.components.names.gridItemsBlkLabel'),
      category: Mautic.translate('grapesjsbuilder.reusableDynamicContentBlockLabel'),
      attributes: { class: 'fa fa-th' },
      content: `<mj-section>${gridItem}</mj-section>`,
    });

    const listItem = `<mj-group>
        <mj-column width="30%">
          <mj-image height="auto" src="https://via.placeholder.com/172x215/#7f7f7f/ffffff?text=172x215+x2"></mj-image>
        </mj-column>
        <mj-column width="70%">
          <mj-text font-size="18px" font-weight="bold" align="center">
            Insert title here
          </mj-text>
          <mj-text align="center">
            Insert text here
          </mj-text>
        </mj-column>
      </mj-group>`;

    this.blockManager.add('list-items', {
      label: Mautic.translate('grapesjsbuilder.components.names.listItemsBlkLabel'),
      category: Mautic.translate('grapesjsbuilder.reusableDynamicContentBlockLabel'),
      attributes: { class: 'fa fa-th-list' },
      content: `<mj-section>${listItem}</mj-section>`,
    });
  }
}
