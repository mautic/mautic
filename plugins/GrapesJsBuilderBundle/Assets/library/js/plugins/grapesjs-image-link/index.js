export const pluginId = 'grapesjs-image-link';

const escapeAttrValue = (value) => String(value).replace(/"/g, '&quot;');

const buildAttributesString = (attributes) => {
  let str = '';

  Object.keys(attributes).forEach((name) => {
    const value = attributes[name];
    if (typeof value !== 'undefined' && value !== '') {
      str += ` ${name}="${escapeAttrValue(value)}"`;
    }
  });

  return str;
};

export default (editor) => {
  const domComponents = editor.DomComponents;
  const imageType = domComponents.getType('image');

  if (!imageType || !imageType.model) {
    return;
  }

  const { model: ImageModel } = imageType;
  const originalGetAttrToHTML = ImageModel.prototype.getAttrToHTML;
  const originalToHTML = ImageModel.prototype.toHTML;

  domComponents.addType('image', {
    extend: 'image',
    model: {
      defaults: {
        traits: ['alt', 'title', 'href', 'target', 'rel'],
      },

      getAttrToHTML() {
        const attributes = { ...originalGetAttrToHTML.call(this) };
        delete attributes.href;
        delete attributes.target;
        delete attributes.rel;
        return attributes;
      },

      toHTML() {
        const attributes = { ...(this.get('attributes') || {}) };
        const href = attributes.href;

        if (!href) {
          return originalToHTML.call(this);
        }

        const target = attributes.target;
        const rel = attributes.rel;
        const imgHtml = originalToHTML.call(this);
        const anchorAttributes = buildAttributesString({ href, target, rel });

        return `<a${anchorAttributes} style="display:inline-block;">${imgHtml}</a>`;
      },
    },
  });
};
