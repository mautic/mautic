import mjml2html from 'mjml-browser';

export const pluginId = 'grapesjs-mjml-theme-tokens';

export const extractMjHeadContent = (mjml) => {
  if (!mjml) return '';
  const m = mjml.match(/<mj-head[^>]*>([\s\S]*?)<\/mj-head>/i);
  return m && m[1] ? m[1].trim() : '';
};

export const createHeadInjectingMjmlParser = (headContent = '') => {
  let cleanHead = (headContent || '').replace(/<mj-preview[^>]*>[\s\S]*?<\/mj-preview>/gi, '');

  const parser = (input, opts) => {
    if (typeof input !== 'string') {
      return mjml2html(input, opts);
    }

    if (!cleanHead || !/<mjml[\s>]/i.test(input) || /<mj-head[\s>]/i.test(input)) {
      return mjml2html(input, opts);
    }

    const withHead = input.replace(
      /<mjml(\s[^>]*)?>/i,
      (m) => `${m}<mj-head>${cleanHead}</mj-head>`
    );

    return mjml2html(withHead, opts);
  };

  // Allow updating the head content before the code editor reparses components.
  parser.updateHeadContent = (newHeadContent) => {
    cleanHead = (newHeadContent || '').replace(/<mj-preview[^>]*>[\s\S]*?<\/mj-preview>/gi, '');
  };

  return parser;
};
