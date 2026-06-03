export const createBlockPatcher = ({ editor, options, classNames }) => {
  const splitTokens = (value = '') => value.split(/\s+/).filter(Boolean);
  const getDefaultValue = (key) => options.defaults?.[key] || '';

  const hasAllTokens = (value) => {
    const tokens = splitTokens(value);
    return tokens.length > 0 && classNames.size > 0 && tokens.every((t) => classNames.has(t));
  };

  const getBlockDefinitions = () => [
    {
      id: 'mj-button',
      action: 'patch',
      defaultKey: 'button',
      getPatch: (mjClass) => ({
        content: `<mj-button mj-class="${mjClass}" href="https://">Button</mj-button>`,
      }),
    },
    {
      id: 'mj-text',
      action: 'patch',
      defaultKey: 'text',
      getPatch: (mjClass) => ({
        content: `<mj-text mj-class="${mjClass}">Insert text here</mj-text>`,
      }),
    },
    {
      id: 'mj-button-secondary',
      action: 'add',
      defaultKey: 'buttonSecondary',
      getBlock: (mjClass) => ({
        label: Mautic.translate('grapesjsbuilder.secondaryButtonBlockLabel'),
        category: Mautic.translate('grapesjsbuilder.categoryBlockLabel'),
        content: `<mj-button mj-class="${mjClass}" href="https://">Button</mj-button>`,
        media: `<svg viewBox="0 0 24 24">
            <path fill="currentColor" d="M20 20.5C20 21.3 19.3 22 18.5 22H13C12.6 22 12.3 21.9 12 21.6L8 17.4L8.7 16.6C8.9 16.4 9.2 16.3 9.5 16.3H9.7L12 18V9C12 8.4 12.4 8 13 8S14 8.4 14 9V13.5L15.2 13.6L19.1 15.8C19.6 16 20 16.6 20 17.1V20.5M20 2H4C2.9 2 2 2.9 2 4V12C2 13.1 2.9 14 4 14H8V12H4V4H20V12H18V14H20C21.1 14 22 13.1 22 12V4C22 2.9 21.1 2 20 2Z" />
          </svg>`,
      }),
    },
  ];

  return () => {
    const bm = editor.BlockManager;

    getBlockDefinitions().forEach((def) => {
      const mjClass = getDefaultValue(def.defaultKey);
      if (!hasAllTokens(mjClass)) return;

      if (def.action === 'patch') {
        const block = bm.get(def.id);
        if (block) block.set(def.getPatch(mjClass));
        return;
      }

      if (def.action === 'add') {
        if (!bm.get(def.id)) {
          bm.add(def.id, def.getBlock(mjClass));
        }
      }
    });
  };
};
