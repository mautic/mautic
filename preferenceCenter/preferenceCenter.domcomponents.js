export default class PreferenceCenterDomComponents {
  editor;

  constructor(editor) {
    this.editor = editor;
  }

  addPreferenceCenterType() {
    // Not sure this is still used. https://grapesjs.com/docs/modules/Components.html#define-custom-component-type
    function isComponent(el) {
      console.trace('we use it: isComponent has been called');
      if (el.getAttribute && el.getAttribute('data-slot') === 'preferenceCenter') {
        console.warn('itistrue', el.getAttribute);
        return {
          type: 'preference-center',
        };
      }
      console.warn('itisfalse', el.getAttribute);
      return false;
    }
    const dc = this.editor.DomComponents;
    const defaultType = dc.getType('default');
    const defaultModel = defaultType.model;

    const model = defaultModel.extend({
      defaults: {
        ...defaultModel.prototype.defaults,
        name: 'Preference Center',
        // draggable: '[data-gjs-type=cell]',
        // droppable: false,
        editable: false,
        stylable: false,
        propagate: ['droppable', 'editable'],
        attributes: {
          // Default attributes
          'data-gjs-type': 'preference-center', // Type for GrapesJS
          'data-slot': 'preferenceCenter', // Retro compatibility with old template
        },
      },

      /**
       * Initilize the component
       */
      init() {
        // Add toolbar edit button if it's not already in
        const toolbar = this.get('toolbar');
        const id = 'toolbar-preference-center';

        if (!toolbar.filter((tlb) => tlb.id === id).length) {
          toolbar.unshift({
            id,
            command: 'preset-mautic:preference-center-open',
            attributes: { class: 'fa fa-pencil-square-o' },
          });
        }
      },
    });

    const view = defaultType.view.extend({
      attributes: {
        style: 'pointer-events: all; display: table; width: 100%;user-select: none;',
      },
      events: {
        dblclick: 'onActive',
      },
      // maybe use onRender for token to slot conversion
      // open the preference center modal if the editor is added or double clicked
      onActive() {
        const target = this.model;
        this.em.get('Commands').run('preset-mautic:preference-center-tokens-to-slots');
        this.em.get('Commands').run('preset-mautic:preference-center-open', { target });
      },
      // does not work: gets removed when Sorting (by grapesjs)
      // removed() {
      //   // Delete preference-center on Mautic side
      //   const component = this.model;
      //   this.em
      //     .get('Commands')
      //     .run('preset-mautic:preference-center-delete-store-item', { component });
      // },
    });

    // add the Preference Center component
    dc.addType('preference-center', {
      isComponent,
      model,
      view,
    });

    dc.addType('success-msg', {
      extend: 'text',
      isComponent: el => el.tagName == 'Text',
  
      model: {
        defaults: {         
          components: 'Text',
          editable: true,         
          type: 'text',
          content: 'Hello world!!!'         
        },
      },
    });
  }
}
