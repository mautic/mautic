/**
 * Applies mj-class styles that grapesjs-mjml does not render on mj-column.
 * Remove this workaround once mj-column inheritance is supported upstream.
 */

const columnVisualProperties = [
  'background-color',
  'border',
  'border-top',
  'border-right',
  'border-bottom',
  'border-left',
  'border-radius',
  'border-top-left-radius',
  'border-top-right-radius',
  'border-bottom-left-radius',
  'border-bottom-right-radius',
  'padding',
  'padding-top',
  'padding-right',
  'padding-bottom',
  'padding-left',
  'vertical-align',
];

const parseMjClassDefinitions = (headContent) => {
  const definitions = new Map();
  const classRegex = /<mj-class\s+([^>]*)>/gi;
  let match;

  while ((match = classRegex.exec(headContent || '')) !== null) {
    const attributeString = match[1];
    const nameMatch = attributeString.match(/\bname\s*=\s*["']([^"']+)["']/i);
    if (!nameMatch) continue;

    const attributes = {};
    const attributeRegex = /(\w[\w-]*)\s*=\s*["']([^"']*)["']/gi;
    let attributeMatch;

    while ((attributeMatch = attributeRegex.exec(attributeString)) !== null) {
      const name = attributeMatch[1].toLowerCase();
      if (name !== 'name') {
        attributes[name] = attributeMatch[2];
      }
    }

    const className = nameMatch[1];
    definitions.set(className, { ...(definitions.get(className) || {}), ...attributes });
  }

  return definitions;
};

const resolveMjClassStyles = (mjClass, definitions) =>
  mjClass
    .split(/\s+/)
    .filter(Boolean)
    .reduce((styles, className) => ({ ...styles, ...(definitions.get(className) || {}) }), {});

export const createMjColumnClassStyles = (editor, initialHeadContent) => {
  let definitions = parseMjClassDefinitions(initialHeadContent);
  const columnType = editor.DomComponents.getType('mj-column');
  const originalRenderStyle = columnType?.view?.prototype?.renderStyle;

  if (originalRenderStyle) {
    editor.DomComponents.addType('mj-column', {
      view: {
        renderStyle() {
          const modelStyle = this.model.get('style') || {};
          const stylable = this.model.get('stylable');
          const mjClass = this.model.get('attributes')?.['mj-class'];
          const renderedStyle = this.el.getAttribute('style') || '';
          const resolvedStyles = mjClass ? resolveMjClassStyles(mjClass, definitions) : {};
          const explicitStyles = Object.keys(modelStyle)
            .filter((property) => stylable.includes(property))
            .map((property) => `${property}:${modelStyle[property]};`);
          const inheritedStyles = columnVisualProperties
            .filter((property) => resolvedStyles[property] && !modelStyle[property])
            .map((property) => `${property}:${resolvedStyles[property]};`);

          originalRenderStyle.call(this);

          if (inheritedStyles.length) {
            this.el.setAttribute(
              'style',
              `${this.attributes.style || ''} ${inheritedStyles.join(' ')} ${explicitStyles.join(
                ' '
              )} ${renderedStyle}`.trim()
            );
            this.checkVisibility();
          }
        },
      },
    });
  }

  return {
    updateHeadContent(headContent) {
      definitions = parseMjClassDefinitions(headContent);
    },
  };
};
