export const pluginId = 'mautic-mjml-custom-blocks';

export default (editor, opts = {}) => {
  const options = {
    category: 'Blocks',
    label: 'Secondary Button',
    id: 'mj-button-secondary',
    content: '<mj-button mj-class="t-btn t-btn-secondary" href="https://">Secondary Button</mj-button>',
    ...opts,
  };

  const bm = editor.BlockManager;

  bm.add(options.id, {
    label: options.label,
    category: options.category,
    content: options.content,
    // Optional: replace with your own SVG
    media: `<svg viewBox="0 0 24 24">
      <path fill="currentColor" d="M7 7h10a4 4 0 0 1 0 8H7a4 4 0 0 1 0-8Zm0 2a2 2 0 0 0 0 4h10a2 2 0 0 0 0-4H7Z"/>
    </svg>`,
  });
};

export const grapesjsMjmlCustomBlocks = pluginId;