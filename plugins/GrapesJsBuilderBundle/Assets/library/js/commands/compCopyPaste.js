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
            editor.trigger('component:paste', added);
            this.setStyles(added);
          }
        });
        selected.emitUpdate();
      } else {
        components = editor.addComponents(components);
        components.forEach((comp) => {
          this.setStyles(comp);
        });
      }
    }
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
      let filteredSelected = selected.filter((item) => item.attributes.copyable == true);
      if (filteredSelected.length) {
        this.newCopy(filteredSelected);
      }
    });

    this.editor.Commands.add('core:paste', (ed) => {
      const selected = ed.getSelected();
      this.newPaste(selected);
    });
  }
}
