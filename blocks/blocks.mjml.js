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
      content: `<mj-section>${sections37}</mj-section>`,
      media: `<svg viewBox="0 0 24 24">
        <path fill="currentColor" d="M2 20h5V4H2v16Zm-1 0V4a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1ZM10 20h12V4H10v16Zm-1 0V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H10a1 1 0 0 1-1-1Z"></path>
      </svg>`,
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
      content: `<mj-section>${textSect}</mj-section>`,
      media: `<svg viewBox="0 0 24 24">
        <path fill="currentColor" d="M20,20H4A2,2 0 0,1 2,18V6A2,2 0 0,1 4,4H20A2,2 0 0,1 22,6V18A2,2 0 0,1 20,20M4,6V18H20V6H4M6,9H18V11H6V9M6,13H16V15H6V13Z" />
      </svg>`,
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
      content: `<mj-section>${gridItem}</mj-section>`,
      media: `<svg viewBox="0 0 24 24">
        <path fill="currentColor" d="M3,11H11V3H3M3,21H11V13H3M13,21H21V13H13M13,3V11H21V3"/>
      </svg>`,
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
      content: `<mj-section>${listItem}</mj-section>`,
      media: `<svg viewBox="0 0 24 24">
        <path fill="currentColor" d="M2 14H8V20H2M16 8H10V10H16M2 10H8V4H2M10 4V6H22V4M10 20H16V18H10M10 16H22V14H10"/>
      </svg>`,
    });
  }
}
