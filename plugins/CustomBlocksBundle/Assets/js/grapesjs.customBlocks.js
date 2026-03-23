(function () {
  window.MauticGrapesJsPlugins = window.MauticGrapesJsPlugins || [];

  // Avoid double-registration if the asset is injected multiple times
  const alreadyRegistered = window.MauticGrapesJsPlugins.some(p => p && p.name === 'customblocks-mjml-blocks');
  if (alreadyRegistered) return;

  window.MauticGrapesJsPlugins.push({
    name: 'customblocks-mjml-blocks',
    context: ['email-mjml'],
    plugin: (editor, opts = {}) => {
      const bm = editor.BlockManager;

      // Block: Section (Surface 2)
      const sectionSurface2Id = 'customblocks-section-surface-2';
      if (!bm.get(sectionSurface2Id)) {
        bm.add(sectionSurface2Id, {
          label: 'Section (Surface 2)',
          category: 'Custom Blocks',
          content:
            '<mj-section mj-class="t-section t-surface-2">' +
              '<mj-column>' +
                '<mj-text mj-class="t-body">Section content...</mj-text>' +
              '</mj-column>' +
            '</mj-section>',
          media: `<svg viewBox="0 0 24 24">
            <path fill="currentColor" d="M4 6h16v4H4V6zm0 8h16v4H4v-4z"/>
          </svg>`,
        });
      }

      // Block: Secondary Button
      const secondaryButtonId = 'customblocks-button-secondary';
      if (!bm.get(secondaryButtonId)) {
        bm.add(secondaryButtonId, {
          label: 'Secondary Button',
          category: 'Custom Blocks',
          content: '<mj-button mj-class="t-btn t-btn-secondary" href="https://">Secondary Button</mj-button>',
          media: `<svg viewBox="0 0 24 24">
            <path fill="currentColor" d="M7 7h10a4 4 0 0 1 0 8H7a4 4 0 0 1 0-8Zm0 2a2 2 0 0 0 0 4h10a2 2 0 0 0 0-4H7Z"/>
          </svg>`,
        });
      }
    },
  });
})();
