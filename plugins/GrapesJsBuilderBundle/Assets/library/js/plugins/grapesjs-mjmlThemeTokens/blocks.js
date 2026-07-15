export const createBlockPatcher = ({ editor, options, classNames }) => {
  const splitTokens = (value = '') => value.split(/\s+/).filter(Boolean);
  const getDefaultValue = (key) => options.defaults?.[key] || '';
  const getMjClassAttribute = (mjClass) => (mjClass ? ` mj-class="${mjClass}"` : '');
  const getAttributes = (attributes) =>
    Object.entries(attributes)
      .map(([key, value]) => ` ${key}="${value}"`)
      .join('');
  const getMjTextAttributes = (mjClass, fallbackAttributes) =>
    `${getMjClassAttribute(mjClass)}${
      mjClass ? '' : `${getAttributes(fallbackAttributes)} padding="10px 25px"`
    }`;
  const getTypographyCategory = () => ({
    label: Mautic.translate('grapesjsbuilder.categoryTypographyLabel'),
    order: -0.5,
    open: false,
  });
  const typographyBlockIds = new Set([
    'mj-heading-1',
    'mj-heading-2',
    'mj-heading-3',
    'mj-heading-4',
    'mj-subtitle',
  ]);

  const hasAllTokens = (value) => {
    const tokens = splitTokens(value);
    return tokens.length > 0 && classNames.size > 0 && tokens.every((t) => classNames.has(t));
  };

  const getBlockId = (block) => block?.get?.('id') || block?.id || block?.attributes?.id;
  const getDroppedComponent = (component) => (Array.isArray(component) ? component[0] : component);

  const selectComponentText = (component, attempts = 8) => {
    const element = component?.getEl?.();
    const editable = element?.querySelector?.('[contenteditable="true"]') || element;
    const ownerDocument = editable?.ownerDocument;
    const selection = ownerDocument?.defaultView?.getSelection?.();

    if (!editable || !ownerDocument || !selection || !editable.textContent.trim()) {
      if (attempts > 0) {
        setTimeout(() => selectComponentText(component, attempts - 1), 50);
      }

      return;
    }

    const range = ownerDocument.createRange();
    range.selectNodeContents(editable);
    selection.removeAllRanges();
    selection.addRange(range);
    editable.focus?.({ preventScroll: true });
  };

  const selectDroppedTypographyText = (component, block) => {
    if (!typographyBlockIds.has(getBlockId(block))) {
      return;
    }

    const droppedComponent = getDroppedComponent(component);
    if (!droppedComponent) {
      return;
    }

    setTimeout(() => selectComponentText(droppedComponent), 0);
  };

  editor.on('block:drag:stop', selectDroppedTypographyText);

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
      requiresTokens: false,
      getPatch: (mjClass) => ({
        category: getTypographyCategory(),
        content: `<mj-text${getMjClassAttribute(mjClass)}>Insert text here</mj-text>`,
      }),
    },
    {
      id: 'mj-heading-1',
      action: 'add',
      defaultKey: 'heading1',
      requiresTokens: false,
      getBlock: (mjClass) => ({
        label: Mautic.translate('grapesjsbuilder.blocks.typography.h1'),
        category: getTypographyCategory(),
        content: `<mj-text${getMjTextAttributes(mjClass, {
          'font-size': '32px',
          'font-weight': 'bold',
          'line-height': '1.2',
        })}>Heading 1</mj-text>`,
        activate: true,
        media:
          '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M13 20H11V13H4V20H2V4H4V11H11V4H13V20ZM21.0005 8V20H19.0005L19 10.204L17 10.74V8.67L19.5005 8H21.0005Z"></path></svg>',
      }),
    },
    {
      id: 'mj-heading-2',
      action: 'add',
      defaultKey: 'heading2',
      requiresTokens: false,
      getBlock: (mjClass) => ({
        label: Mautic.translate('grapesjsbuilder.blocks.typography.h2'),
        category: getTypographyCategory(),
        content: `<mj-text${getMjTextAttributes(mjClass, {
          'font-size': '28px',
          'font-weight': 'bold',
          'line-height': '1.2',
        })}>Heading 2</mj-text>`,
        activate: true,
        media:
          '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4V11H11V4H13V20H11V13H4V20H2V4H4ZM18.5 8C20.5711 8 22.25 9.67893 22.25 11.75C22.25 12.6074 21.9623 13.3976 21.4781 14.0292L21.3302 14.2102L18.0343 18H22V20H15L14.9993 18.444L19.8207 12.8981C20.0881 12.5908 20.25 12.1893 20.25 11.75C20.25 10.7835 19.4665 10 18.5 10C17.5818 10 16.8288 10.7071 16.7558 11.6065L16.75 11.75H14.75C14.75 9.67893 16.4289 8 18.5 8Z"></path></svg>',
      }),
    },
    {
      id: 'mj-heading-3',
      action: 'add',
      defaultKey: 'heading3',
      requiresTokens: false,
      getBlock: (mjClass) => ({
        label: Mautic.translate('grapesjsbuilder.blocks.typography.h3'),
        category: getTypographyCategory(),
        content: `<mj-text${getMjTextAttributes(mjClass, {
          'font-size': '24px',
          'font-weight': 'bold',
          'line-height': '1.3',
        })}>Heading 3</mj-text>`,
        activate: true,
        media:
          '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M22 8L21.9984 10L19.4934 12.883C21.0823 13.3184 22.25 14.7728 22.25 16.5C22.25 18.5711 20.5711 20.25 18.5 20.25C16.674 20.25 15.1528 18.9449 14.8184 17.2166L16.7821 16.8352C16.9384 17.6413 17.6481 18.25 18.5 18.25C19.4665 18.25 20.25 17.4665 20.25 16.5C20.25 15.5335 19.4665 14.75 18.5 14.75C18.214 14.75 17.944 14.8186 17.7056 14.9403L16.3992 13.3932L19.3484 10H15V8H22ZM4 4V11H11V4H13V20H11V13H4V20H2V4H4Z"></path></svg>',
      }),
    },
    {
      id: 'mj-heading-4',
      action: 'add',
      defaultKey: 'heading4',
      requiresTokens: false,
      getBlock: (mjClass) => ({
        label: Mautic.translate('grapesjsbuilder.blocks.typography.h4'),
        category: getTypographyCategory(),
        content: `<mj-text${getMjTextAttributes(mjClass, {
          'font-size': '20px',
          'font-weight': 'bold',
          'line-height': '1.3',
        })}>Heading 4</mj-text>`,
        activate: true,
        media:
          '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M13 20H11V13H4V20H2V4H4V11H11V4H13V20ZM22 8V16H23.5V18H22V20H20V18H14.5V16.66L19.5 8H22ZM20 11.133L17.19 16H20V11.133Z"></path></svg>',
      }),
    },
    {
      id: 'mj-subtitle',
      action: 'add',
      defaultKey: 'subtitle',
      requiresTokens: false,
      getBlock: (mjClass) => ({
        label: Mautic.translate('grapesjsbuilder.blocks.typography.subtitle'),
        category: getTypographyCategory(),
        content: `<mj-text${getMjTextAttributes(mjClass, {
          'font-size': '18px',
          color: '#666666',
          'font-style': 'italic',
          'line-height': '1.4',
        })}>Subtitle text goes here</mj-text>`,
        activate: true,
        media:
          '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 5h14v2H5V5zm0 4h14v2H5V9zm0 4h10v2H5v-2zm0 4h7v2H5v-2z"/></svg>',
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
      const defaultMjClass = def.defaultKey ? getDefaultValue(def.defaultKey) : '';
      const hasDefaultTokens = hasAllTokens(defaultMjClass);
      if (def.requiresTokens !== false && !hasDefaultTokens) return;
      const mjClass = hasDefaultTokens ? defaultMjClass : '';

      if (def.action === 'patch') {
        const block = bm.get(def.id);
        if (block) block.set(def.getPatch(mjClass));
        return;
      }

      if (def.action === 'add') {
        const block = bm.get(def.id);
        const nextBlock = def.getBlock(mjClass);

        if (block) {
          block.set(nextBlock);
        } else {
          bm.add(def.id, nextBlock);
        }
      }
    });
  };
};
