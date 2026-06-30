import { pluginId, extractMjHeadContent, createHeadInjectingMjmlParser } from './utils';
import { patchBlocks, createBlockPatcher } from './blocks';

export { pluginId, extractMjHeadContent, createHeadInjectingMjmlParser };

export default (editor, opts = {}) => {
  const options = {
    // Provide mj-head inner content (preferred) or full original MJML
    headContent: '',
    originalMjml: '',

    // Default token mapping for newly dropped components
    defaults: {
      text: 't-body',
      button: 't-btn t-btn-primary',
      buttonSecondary: 't-btn t-btn-secondary',
      section: 't-section t-surface-1',
    },

    // Types to auto-apply defaults to
    applyDefaultsToTypes: ['mj-text', 'mj-button', 'mj-section'],

    ...opts,
  };

  const headContent = options.headContent || extractMjHeadContent(options.originalMjml || '');

  const parseMjClassNames = (mjHeadContent) => {
    const out = new Set();
    if (!mjHeadContent) return out;

    const re = /<mj-class\s+[^>]*\bname\s*=\s*["']([^"']+)["'][^>]*>/gi;
    let m;
    while ((m = re.exec(mjHeadContent)) !== null) out.add(m[1]);
    return out;
  };

  const classNames = parseMjClassNames(headContent);

  const registerHiddenMjAttributesTypes = () => {
    const isTag = (el, tag) => (el?.tagName || '').toLowerCase() === tag;
    const parentIs = (el, tag) => isTag(el?.parentElement, tag);

    const hiddenDefaults = {
      selectable: false,
      hoverable: false,
      highlightable: false,
      layerable: false,
      draggable: false,
      droppable: false,
      copyable: false,
      removable: false,
      editable: false,
    };

    const hiddenView = {
      tagName: 'div',
      attributes: { style: 'display:none !important;' },
      getTemplateFromMjml() {
        return '';
      },
      render() {
        this.el.innerHTML = '';
        return this;
      },
    };

    // Container <mj-attributes>
    editor.DomComponents.addType('mj-attributes', {
      isComponent: (el) => isTag(el, 'mj-attributes'),
      model: {
        defaults: {
          tagName: 'mj-attributes',
          ...hiddenDefaults,
        },
      },
      view: hiddenView,
    });

    // Leaf tags inside <mj-attributes>
    editor.DomComponents.addType('mj-all', {
      isComponent: (el) => isTag(el, 'mj-all') && parentIs(el, 'mj-attributes'),
      model: {
        defaults: {
          tagName: 'mj-all',
          void: false,
          ...hiddenDefaults,
        },
      },
      view: hiddenView,
    });

    editor.DomComponents.addType('mj-class', {
      isComponent: (el) => isTag(el, 'mj-class') && parentIs(el, 'mj-attributes'),
      model: {
        defaults: {
          tagName: 'mj-class',
          void: false,
          ...hiddenDefaults,
        },
      },
      view: hiddenView,
    });

    // Head-default tags like <mj-text ...></mj-text> inside <mj-attributes>
    // Extend the existing body types (must exist => plugin must run AFTER grapesjs-mjml)
    const addHiddenAttrType = (typeName, baseType, tagName) => {
      editor.DomComponents.addType(typeName, {
        extend: baseType,
        isComponent: (el) => isTag(el, tagName) && parentIs(el, 'mj-attributes'),
        model: {
          defaults: {
            tagName,
            ...hiddenDefaults,
          },
        },
        view: hiddenView,
      });
    };

    addHiddenAttrType('mj-attr-text', 'mj-text', 'mj-text');
    addHiddenAttrType('mj-attr-button', 'mj-button', 'mj-button');
    addHiddenAttrType('mj-attr-section', 'mj-section', 'mj-section');
    addHiddenAttrType('mj-attr-column', 'mj-column', 'mj-column');
  };

  const stripDefaultAttrsForComponent = (component) => {
    if (!component) return;

    const attrs = { ...(component.get('attributes') || {}) };
    const styleDefault = component.get('style-default') || {};

    let changed = false;
    Object.keys(styleDefault).forEach((key) => {
      if (key in attrs && attrs[key] === styleDefault[key]) {
        delete attrs[key];
        changed = true;
      }
    });

    if (changed) {
      component.set('attributes', attrs);
    }
  };

  const stripDefaultAttrsForTokenizedComponents = () => {
    const wrapper = editor.getWrapper?.();
    if (!wrapper) return;

    const walk = (cmp) => {
      const attrs = { ...(cmp.get('attributes') || {}) };
      if (attrs['mj-class']) stripDefaultAttrsForComponent(cmp);

      const children = cmp.components?.();
      if (children && children.length) children.forEach((c) => walk(c));
    };

    wrapper.components?.().forEach((c) => walk(c));
  };

  const getDefaultMjClassForType = (type) => {
    if (type === 'mj-text') return options.defaults.text || '';
    if (type === 'mj-button') return options.defaults.button || '';
    if (type === 'mj-section') return options.defaults.section || '';
    return '';
  };

  // Apply defaults only AFTER initial content import is done
  let readyForNewDrops = false;

  const onComponentAdd = (component) => {
    if (!readyForNewDrops) return;

    const type = component?.get?.('type');
    if (!type || !options.applyDefaultsToTypes.includes(type)) return;

    const attrs = { ...(component.get('attributes') || {}) };

    // If block didn't specify mj-class, apply theme token (only if token exists in theme)
    if (!attrs['mj-class'] && classNames.size) {
      const token = getDefaultMjClassForType(type);
      if (token) {
        const parts = token.split(/\s+/).filter(Boolean);
        const allExist = parts.every((p) => classNames.has(p));
        if (allExist) {
          component.set('attributes', { ...attrs, 'mj-class': token });
        }
      }
    }

    // Always strip defaults on new drops (lets theme <mj-attributes> and/or mj-class win)
    stripDefaultAttrsForComponent(component);
  };

  // Must be executed during init (before setComponents) so mj-attributes content is hidden on parse
  registerHiddenMjAttributesTypes();

  editor.on('component:add', onComponentAdd);


  const patchBlocksWithContext = createBlockPatcher({
    editor,
    options,
    classNames,
  });

  // Patch blocks when they appear (preset plugins may add them later)
  editor.on('load', patchBlocksWithContext);
  const blockColl = editor.BlockManager.getAll?.();
  if (blockColl?.on) {
    blockColl.on('add reset', patchBlocksWithContext);
  }

  // Service will call this after its setComponents + reparse workaround
  editor.on('mjml-theme-tokens:content:ready', () => {
    stripDefaultAttrsForTokenizedComponents();
    patchBlocksWithContext();
    readyForNewDrops = true;
  });
};
