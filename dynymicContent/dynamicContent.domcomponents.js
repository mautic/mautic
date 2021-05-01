export default class DynamicContentDomComponents {
  editor;

  constructor(editor) {
    this.editor = editor;
  }

  addDynamicContentType() {
    // Not sure this is still used. https://grapesjs.com/docs/modules/Components.html#define-custom-component-type
    function isComponent(el) {
      console.warn('isComponent has been called');
      if (el.getAttribute && el.getAttribute('data-slot') === 'dynamicContent') {
        console.warn('itistrue', el.getAttribute);
        return {
          type: 'dynamic-content',
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
        name: 'Dynamic Content',
        // draggable: '[data-gjs-type=cell]',
        // droppable: false,
        editable: false,
        stylable: false,
        propagate: ['droppable', 'editable'],
        attributes: {
          // Default attributes
          'data-gjs-type': 'dynamic-content', // Type for GrapesJS
          'data-slot': 'dynamicContent', // Retro compatibility with old template
        },
      },

      /**
       * Initilize the component
       */
      init() {
        const toolbar = this.get('toolbar');
        const id = 'toolbar-dynamic-content';

        // Add toolbar edit button if it's not already in
        if (!toolbar.filter((tlb) => tlb.id === id).length) {
          toolbar.unshift({
            id,
            command: 'preset-mautic:dynamic-content',
            attributes: { class: 'fa fa-pencil-square-o' },
          });
        }

        // note: could also take some listeners
      },
    });

    const view = defaultType.view.extend({
      attributes: {
        style: 'pointer-events: all; display: table; width: 100%;user-select: none;',
      },
      events: {
        dblclick: 'onActive',
      },
      // onActive() {
      //   console.warn('onActive', this.model);
      //   const target = this.model;

      //   this.em.get('Commands').run('preset-mautic:dynamic-content', { target });
      // },
    });

    // add the Dynamic Content component
    dc.addType('dynamic-content', {
      isComponent,
      model,
      view,
    });
  }
}
