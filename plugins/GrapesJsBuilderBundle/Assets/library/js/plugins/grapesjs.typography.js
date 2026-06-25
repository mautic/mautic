export default (editor, opts = {}) => {
  const bm = editor.BlockManager;
  const isMjml = opts.mode && opts.mode.includes('mjml');

  const addTypographyBlocks = () => {
    if (!isMjml) {
      return;
    }

    const categoryLabel = Mautic.translate('grapesjsbuilder.categoryTypographyLabel');
    const labelH1 = Mautic.translate('grapesjsbuilder.blocks.typography.h1');
    const labelH2 = Mautic.translate('grapesjsbuilder.blocks.typography.h2');
    const labelH3 = Mautic.translate('grapesjsbuilder.blocks.typography.h3');
    const labelH4 = Mautic.translate('grapesjsbuilder.blocks.typography.h4');
    const labelSubtitle = Mautic.translate('grapesjsbuilder.blocks.typography.subtitle');

    bm.add('mj-heading-1', {
      label: labelH1,
      category: categoryLabel,
      media:
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M13 20H11V13H4V20H2V4H4V11H11V4H13V20ZM21.0005 8V20H19.0005L19 10.204L17 10.74V8.67L19.5005 8H21.0005Z"></path></svg>',
      content:
        '<mj-text font-size="32px" font-weight="bold" line-height="1.2" padding="10px 25px">Heading 1</mj-text>',
    });

    bm.add('mj-heading-2', {
      label: labelH2,
      category: categoryLabel,
      media:
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4V11H11V4H13V20H11V13H4V20H2V4H4ZM18.5 8C20.5711 8 22.25 9.67893 22.25 11.75C22.25 12.6074 21.9623 13.3976 21.4781 14.0292L21.3302 14.2102L18.0343 18H22V20H15L14.9993 18.444L19.8207 12.8981C20.0881 12.5908 20.25 12.1893 20.25 11.75C20.25 10.7835 19.4665 10 18.5 10C17.5818 10 16.8288 10.7071 16.7558 11.6065L16.75 11.75H14.75C14.75 9.67893 16.4289 8 18.5 8Z"></path></svg>',
      content:
        '<mj-text font-size="28px" font-weight="bold" line-height="1.2" padding="10px 25px">Heading 2</mj-text>',
    });

    bm.add('mj-heading-3', {
      label: labelH3,
      category: categoryLabel,
      media:
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M22 8L21.9984 10L19.4934 12.883C21.0823 13.3184 22.25 14.7728 22.25 16.5C22.25 18.5711 20.5711 20.25 18.5 20.25C16.674 20.25 15.1528 18.9449 14.8184 17.2166L16.7821 16.8352C16.9384 17.6413 17.6481 18.25 18.5 18.25C19.4665 18.25 20.25 17.4665 20.25 16.5C20.25 15.5335 19.4665 14.75 18.5 14.75C18.214 14.75 17.944 14.8186 17.7056 14.9403L16.3992 13.3932L19.3484 10H15V8H22ZM4 4V11H11V4H13V20H11V13H4V20H2V4H4Z"></path></svg>',
      content:
        '<mj-text font-size="24px" font-weight="bold" line-height="1.3" padding="10px 25px">Heading 3</mj-text>',
    });

    bm.add('mj-heading-4', {
      label: labelH4,
      category: categoryLabel,
      media:
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M13 20H11V13H4V20H2V4H4V11H11V4H13V20ZM22 8V16H23.5V18H22V20H20V18H14.5V16.66L19.5 8H22ZM20 11.133L17.19 16H20V11.133Z"></path></svg>',
      content:
        '<mj-text font-size="20px" font-weight="bold" line-height="1.3" padding="10px 25px">Heading 4</mj-text>',
    });

    bm.add('mj-subtitle', {
      label: labelSubtitle,
      category: categoryLabel,
      media:
        '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 5h14v2H5V5zm0 4h14v2H5V9zm0 4h10v2H5v-2zm0 4h7v2H5v-2z"/></svg>',
      content:
        '<mj-text font-size="18px" color="#666666" font-style="italic" line-height="1.4" padding="10px 25px">Subtitle text goes here</mj-text>',
    });

    const mjTextBlock = bm.get('mj-text');
    if (mjTextBlock) {
      mjTextBlock.set('category', categoryLabel);
    }

    const categories = bm.getCategories && bm.getCategories();
    if (categories && categories.forEach) {
      categories.forEach((category) => {
        if (category.get && category.get('label') === categoryLabel) {
          category.set('order', -0.5);
          category.set('open', false);
        }
      });
    }
  };

  addTypographyBlocks();

  editor.on('load', () => {
    addTypographyBlocks();
  });
};
