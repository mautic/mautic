export const pluginId = 'grapesjs-image-link';

const escapeAttrValue = (value) => String(value).replace(/"/g, '&quot;');

const normalizeHref = (href) => {
  if (!href) {
    return href;
  }
  const value = String(href).trim();
  if (!value) {
    return value;
  }
  // schemes, protocol-relative, anchors/relative paths, Mautic tokens - leave as-is
  if (
    /^[a-zA-Z][a-zA-Z\d+\-.]*:/.test(value) ||
    value.startsWith('//') ||
    /^[#/.]/.test(value) ||
    value.startsWith('{')
  ) {
    return value;
  }
  // prepend https only when it looks like a domain (host.tld)
  const host = value.split(/[/?#]/)[0];
  const tld = host.includes('.') ? host.split('.').pop() : '';
  if (/^[a-zA-Z]{2,}$/.test(tld)) {
    return `https://${value}`;
  }
  return value;
};

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
    isComponent: (el) => el.tagName === 'IMG',
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
        const href = normalizeHref(attributes.href);

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
