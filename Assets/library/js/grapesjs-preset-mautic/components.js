export default (editor, opts = {}) => {
  const dc = editor.DomComponents;
  const defaultType = dc.getType('default');
  const defaultModel = defaultType.model;
  const cfg = editor.getConfig();

  // Add Dynamic Content block only for newsletter
  if ('grapesjsmjml' in cfg.pluginsOpts) {
    // Dynamic Content MJML block
  } else if ('grapesjsnewsletter' in cfg.pluginsOpts) {
    // Dynamic Content component
    dc.addType('dynamic-content', {
      model: defaultModel.extend(
        {
          defaults: {
            ...defaultModel.prototype.defaults,
            name: 'Dynamic Content',
            draggable: '[data-gjs-type=cell]',
            droppable: false,
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
                label: '<div class="fa fa-tag"></div>',
              });
            }
          },
        },
        {
          // Dynamic Content component detection
          isComponent(el) {
            if (el.getAttribute && el.getAttribute('data-slot') == 'dynamicContent') {
              return {
                type: 'dynamic-content',
              };
            }
          },
        }
      ),

      view: defaultType.view.extend({
        attributes: {
          style: 'pointer-events: all; display: table; width: 100%;user-select: none;',
        },
        events: {
          dblclick: 'onActive',
        },
        onActive() {
          const target = this.model;

          this.em.get('Commands').run('preset-mautic:dynamic-content', { target });
        },
      }),
    });
  }
};
