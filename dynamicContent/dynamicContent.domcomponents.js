import DynamicContentService from './dynamicContent.service';

export default class DynamicContentDomComponents {
  dcService;

  static addDynamicContentType(editor) {
    const dc = editor.DomComponents;
    const baseType = dc.getType('mj-text');
    const baseModel = baseType.model;

    // Keep style-default from mj-text so padding etc. render correctly via MJML
    // compilation. Do not spread baseModel attributes — coreMjmlModel.init() will
    // re-derive them from style-default, avoiding duplicate style props as raw
    // HTML attributes on the saved <mj-text> element.
    const styleDefault = baseModel.prototype.defaults['style-default'];

    const model = {
      defaults: {
        ...baseModel.prototype.defaults,
        name: 'Dynamic Content',
        tagName: 'mj-text',
        draggable: '[data-gjs-type=mj-column]',
        droppable: false,
        editable: false,
        stylable: ['padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left'],
        propagate: ['droppable', 'editable'],
        'style-default': styleDefault,
        attributes: {
          'data-gjs-type': 'dynamic-content', // Type for GrapesJS
          'data-slot': 'dynamicContent', // used to find the DC component on the canvas for e.g. token transformation
        },
      },
      /**
       * Initilize the component
       */
      init() {
        // coreMjmlModel.init() syncs style-default into attributes and style
        if (typeof baseModel.prototype.init === 'function') {
          baseModel.prototype.init.call(this);
        }

        // link component to the corresponding html store item
        this.em
          .get('Commands')
          .run('preset-mautic:link-component-to-store-item', { component: this });

        // Add toolbar edit button if it's not already in
        const toolbar = this.get('toolbar');
        const id = 'toolbar-dynamic-content';

        if (!toolbar.filter((tlb) => tlb.id === id).length) {
          toolbar.unshift({
            id,
            command: 'preset-mautic:dynamic-content-open',
            attributes: { class: 'fa fa-pencil-square-o' },
          });
        }
      },
    };

    // Extend mj-text's view to inherit the full MJML rendering pipeline:
    // tagName:'tr', getTemplateFromMjml, renderChildren, renderStyle, etc.
    const view = {
      tagName: 'tr',
      attributes: {
        style: 'pointer-events: all; display: table; width: 100%; user-select: none;',
      },
      getMjmlTemplate() {
        return {
          start: `<mjml><mj-body><mj-column>`,
          end: `</mj-column></mj-body></mjml>`,
        };
      },
      getTemplateFromEl(sandboxEl) {
        return sandboxEl.querySelector('tr').innerHTML;
      },
      getChildrenSelector() {
        return 'td > div';
      },
      rerender() {
        this.render();
      },
      // After the MJML pipeline renders the <tr> shell, replace the inner
      // content with the DC item's human-readable HTML.
      onRender({ editor, model }) {
        const dcService = new DynamicContentService(editor);
        const decId = DynamicContentService.getDataParamDecid(model);
        const dcItem = dcService.getStoreItem(decId);
        if (typeof dcItem !== 'undefined') {
          const container = this.el.querySelector('td > div') || this.el.querySelector('td');
          if (container) {
            container.innerHTML = dcItem.content;
          }
          dcService.logger.debug('DC: Updated view', dcItem);
        }
      },
      onActive() {
        const target = this.model;
        this.em.get('Commands').run('preset-mautic:dynamic-content-open', { target });
      },
    };

    // add the Dynamic Content component
    dc.addType('dynamic-content', {
      extend: 'mj-text',
      extendFnView: ['onActive'],
      // Dynamic Content component detection
      isComponent: (el) => {
        if (
          typeof el.getAttribute !== 'undefined' &&
          el.getAttribute('data-slot') === 'dynamicContent'
        ) {
          return {
            type: 'dynamic-content',
          };
        }
        return false;
      },
      model,
      view,
    });
  }
}
