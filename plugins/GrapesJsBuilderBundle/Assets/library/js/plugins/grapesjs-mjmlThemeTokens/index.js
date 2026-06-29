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
      heading1: 't-h1',
      heading2: 't-h2',
      heading3: 't-h3',
      heading4: 't-h4',
      subtitle: 't-lead',
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

  // Parse mj-class definitions into a map: className -> { attr: value, ... }
  const parseMjClassDefinitions = (mjHeadContent) => {
    const definitions = new Map();
    if (!mjHeadContent) return definitions;

    const re = /<mj-class\s+([^>]*)>/gi;
    let m;
    while ((m = re.exec(mjHeadContent)) !== null) {
      const attrString = m[1];
      const nameMatch = attrString.match(/\bname\s*=\s*["']([^"']+)["']/);
      if (!nameMatch) continue;

      const name = nameMatch[1];
      const attrs = {};
      const attrRe = /([\w-]+)\s*=\s*["']([^"']*)["']/g;
      let attrM;
      while ((attrM = attrRe.exec(attrString)) !== null) {
        if (attrM[1] !== 'name') {
          attrs[attrM[1]] = attrM[2];
        }
      }
      definitions.set(name, attrs);
    }
    return definitions;
  };

  const mjClassDefinitions = parseMjClassDefinitions(headContent);

  // Resolve mj-class tokens into a merged attribute object
  const resolveMjClassAttrs = (mjClassValue) => {
    if (!mjClassValue || !mjClassDefinitions.size) return {};
    const tokens = mjClassValue.split(/\s+/).filter(Boolean);
    let resolved = {};
    tokens.forEach((token) => {
      const def = mjClassDefinitions.get(token);
      if (def) {
        resolved = { ...resolved, ...def };
      }
    });
    return resolved;
  };

  // Check if an attribute is "covered" by an existing explicit attribute
  const shorthandGroups = {
    padding: ['padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left'],
    'border-radius': ['border-radius', 'border-top-left-radius', 'border-top-right-radius', 'border-bottom-left-radius', 'border-bottom-right-radius'],
  };

  const isAttrCoveredByExisting = (key, existingAttrs) => {
    for (const group of Object.values(shorthandGroups)) {
      if (group.includes(key)) {
        for (const member of group) {
          if (member in existingAttrs) return true;
        }
      }
    }
    return false;
  };

  // Apply resolved mj-class attributes to component so views render correctly
  const applyMjClassAttrsToComponent = (component) => {
    if (!component) return;
    const attrs = component.get('attributes') || {};
    const mjClass = attrs['mj-class'];
    if (!mjClass) return;

    const resolved = resolveMjClassAttrs(mjClass);
    if (!Object.keys(resolved).length) return;

    const currentAttrs = { ...attrs };
    let changed = false;
    Object.entries(resolved).forEach(([key, value]) => {
      if (key in currentAttrs) return;
      if (isAttrCoveredByExisting(key, currentAttrs)) return;

      currentAttrs[key] = value;
      changed = true;
    });

    if (changed) {
      component.set('attributes', currentAttrs);
    }
  };

  const applyMjClassAttrsToAllComponents = () => {
    const wrapper = editor.getWrapper?.();
    if (!wrapper) return;

    const walk = (cmp) => {
      applyMjClassAttrsToComponent(cmp);
      const children = cmp.components?.();
      if (children && children.length) children.forEach((c) => walk(c));
    };

    wrapper.components?.().forEach((c) => walk(c));
  };

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

    // Apply resolved mj-class attrs so the view renders them
    applyMjClassAttrsToComponent(component);
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
    applyMjClassAttrsToAllComponents();
    patchBlocksWithContext();
    readyForNewDrops = true;
  });
};
