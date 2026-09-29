// copied from: https://github.com/GrapesJS/grapesjs/issues/1855

export default class CompCopyPaste {
  static storage_key = 'preset-mautic:grapesjs-clipboard';
  editor;

  // COPY PASTE COMPONENTS/STYLE BETWEEN PAGES
  getStyles(components) {
    // recurse down through components and store styles in temp attribute
    components.forEach((component) => {
      const recurse = (comp) => {
        // if component has any styling
        if (Object.keys(comp.getStyle()).length !== 0) comp.attributes.savedStyle = comp.getStyle();
        if (comp.get('components').length) {
          comp.get('components').forEach((child) => {
            recurse(child);
          });
        }
      };
      recurse(component);
    });
    return components;
  }

  setStyles(component) {
    // recurse down and re-apply style back to components
    const recurse = (comp) => {
      if ('savedStyle' in comp.attributes) {
        comp.setStyle(comp.attributes.savedStyle);
        delete comp.attributes.savedStyle;
      }
      if (comp.attributes.components.length) {
        comp.attributes.components.forEach((child) => {
          recurse(child);
        });
      }
    };
    recurse(component);
  }

  newCopy(selected) {
    window.localStorage.setItem(this.storage_key, JSON.stringify(selected));
  }

  newPaste(selected) {
    let components = JSON.parse(window.localStorage.getItem(this.storage_key));
    if (components) {
      if (selected && selected.attributes.type !== 'wrapper') {
        const index = selected.index();
        // Invert the order so last item gets added first and gets pushed down as others get added.
        components.reverse();
        const currentSelection = selected.collection;
        components.forEach((comp) => {
          if (currentSelection) {
            const added = currentSelection.add(comp, { at: index + 1 });
            this.editor.trigger('component:paste', added);
            this.setStyles(added);
          }
        });
        selected.emitUpdate();
      } else {
        components = this.editor.addComponents(components);
        components.forEach((comp) => {
          this.setStyles(comp);
        });
      }
    }
  }

  // Sort components back into document order, regardless of selection order.
  sortByDocumentOrder(components) {
    const { DOCUMENT_POSITION_FOLLOWING, DOCUMENT_POSITION_PRECEDING } = Node;

    // Resolve each element once instead of on every comparison.
    const entries = components.map((component) => ({
      component,
      el: component.getEl?.() ?? null,
    }));

    const compare = (a, b) => {
      // Unrendered components sort last, keeping the comparator consistent.
      if (!a.el || !b.el) return (a.el ? 0 : 1) - (b.el ? 0 : 1);

      const position = a.el.compareDocumentPosition(b.el);
      // eslint-disable-next-line no-bitwise
      if (position & DOCUMENT_POSITION_FOLLOWING) return -1;
      // eslint-disable-next-line no-bitwise
      if (position & DOCUMENT_POSITION_PRECEDING) return 1;
      return 0;
    };

    return entries.sort(compare).map(({ component }) => component);
  }

  constructor(editor) {
    if (!editor) {
      throw new Error('no editor');
    }
    this.editor = editor;
  }

  addCommand() {
    this.editor.Commands.add('core:copy', (ed) => {
      const selected = this.getStyles([...ed.getSelectedAll()]);
      // Multi-select copy is scoped to sections, wrappers, and heroes; mixed
      // selections are allowed in the canvas, but any other type is silently dropped here.
      const copyableTypes = ['mj-section', 'mj-wrapper', 'mj-hero'];
      let filteredSelected = selected.filter(
        (item) => item.attributes.copyable == true && copyableTypes.includes(item.get('type'))
      );
      if (filteredSelected.length) {
        filteredSelected = this.sortByDocumentOrder(filteredSelected);
        this.newCopy(filteredSelected);
      }
    });

    this.editor.Commands.add('core:paste', (ed) => {
      // Paste always targets a single selected component (section or wrapper) as the anchor point.
      // If multiple components are still selected, bail out rather than guessing a target.
      if (ed.getSelectedAll().length > 1) {
        // eslint-disable-next-line no-console
        console.warn('Paste requires a single selected target.');
        return;
      }
      const selected = ed.getSelected();
      this.newPaste(selected);
    });
  }
}
