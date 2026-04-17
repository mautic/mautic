import ContentService from '../content.service';
import DynamicContentService from './dynamicContent.service';

const DC_TYPE = 'dynamic-content';
const DC_SLOT = 'dynamicContent';
const TOOLBAR_ID = 'toolbar-dynamic-content';
const OPEN_COMMAND = 'preset-mautic:dynamic-content-open';
const LINK_COMMAND = 'preset-mautic:link-component-to-store-item';

function isDynamicContentComponent(el) {
  if (typeof el.getAttribute !== 'undefined' && el.getAttribute('data-slot') === DC_SLOT) {
    return {
      type: DC_TYPE,
    };
  }
  return false;
}

function addToolbarAndLink(component) {
  component.em.get('Commands').run(LINK_COMMAND, { component });

  const toolbar = component.get('toolbar') || [];

  if (!toolbar.some((item) => item.id === TOOLBAR_ID)) {
    toolbar.unshift({
      id: TOOLBAR_ID,
      command: OPEN_COMMAND,
      attributes: { class: 'fa fa-pencil-square-o' },
    });
  }

  component.set('toolbar', toolbar);
}

function renderDynamicContent(editor, model, renderContent, loggerContext = 'DC: Updated view') {
  const dcService = new DynamicContentService(editor);
  const dynamicContentId = DynamicContentService.getDataParamDecid(model);
  const dcItem = dcService.getStoreItem(dynamicContentId);

  if (dcItem) {
    renderContent(dcItem.content);
    dcService.logger.debug(loggerContext, dcItem);
  }
}

export default class DynamicContentDomComponents {
  static addDynamicContentType(editor) {
    const dc = editor.DomComponents;
    const isMjml = ContentService.isMjmlMode(editor);

    if (isMjml) {
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
            'data-gjs-type': DC_TYPE, // Type for GrapesJS
            'data-slot': DC_SLOT, // used to find the DC component on the canvas for e.g. token transformation
          },
        },
        init() {
          // coreMjmlModel.init() syncs style-default into attributes and style
          if (typeof baseModel.prototype.init === 'function') {
            baseModel.prototype.init.call(this);
          }
          addToolbarAndLink(this);
        },
      };

      const view = {
        tagName: 'tr',
        attributes: {
          style: 'pointer-events: all; display: table; width: 100%; user-select: none;',
        },
        getMjmlTemplate() {
          return {
            start: '<mjml><mj-body><mj-column>',
            end: '</mj-column></mj-body></mjml>',
          };
        },
        getTemplateFromEl(sandboxEl) {
          const row = sandboxEl.querySelector('tr');
          return row ? row.innerHTML : '';
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
          renderDynamicContent(editor, model, (content) => {
            const container = this.el.querySelector('td > div') || this.el.querySelector('td');
            if (container) {
              container.innerHTML = content;
            }
          });
        },
        onActive() {
          this.em.get('Commands').run(OPEN_COMMAND, { target: this.model });
        },
      };

      // add the Dynamic Content component
      dc.addType(DC_TYPE, {
        extend: 'mj-text',
        extendFnView: ['onActive'],
        // Dynamic Content component detection
        isComponent: isDynamicContentComponent,
        model,
        view,
      });
    } else {
      const baseType = dc.getType('text');
      const baseModel = baseType.model;

      const model = {
        defaults: {
          ...baseModel.prototype.defaults,
          name: 'Dynamic Content',
          tagName: 'div',
          draggable: '[data-gjs-type=cell],[data-gjs-type=mj-column]',
          droppable: false,
          editable: false,
          stylable: ['padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left'],
          propagate: ['droppable', 'editable'],
          style: { ...baseModel.prototype.defaults['style-default'], display: 'block' },
          attributes: {
            'data-gjs-type': DC_TYPE,
            'data-slot': DC_SLOT,
          },
        },
        init() {
          addToolbarAndLink(this);
        },
      };

      const view = {
        attributes: {
          style: 'pointer-events: all; display: table; width: 100%; user-select: none;',
        },
        events: {
          dblclick: 'onActive',
        },
        onRender({ editor, model }) {
          renderDynamicContent(editor, model, (content) => {
            this.el.innerHTML = content;
          });
        },
        onActive() {
          this.em.get('Commands').run(OPEN_COMMAND, { target: this.model });
        },
      };

      dc.addType(DC_TYPE, {
        isComponent: isDynamicContentComponent,
        model,
        view,
      });
    }
  }
}
