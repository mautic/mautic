export default class MjmlStylesService {
  static normalizeListStyles(mjml) {
    if (typeof mjml !== 'string' || !mjml.includes('<mjml')) {
      return mjml;
    }

    const mjTextRegex = /<mj-text\b[^>]*>[\s\S]*?<\/mj-text>/gi;
    const cssRules = new Map();

    const updatedMjml = mjml.replace(mjTextRegex, (block) => {
      const openTagEnd = block.indexOf('>');
      if (openTagEnd === -1) {
        return block;
      }

      const openTag = block.slice(0, openTagEnd + 1);
      const closeTag = '</mj-text>';
      if (!block.endsWith(closeTag)) {
        return block;
      }

      const innerHtml = block.slice(openTagEnd + 1, block.length - closeTag.length);
      const implementation = (typeof document !== 'undefined' && document.implementation) ? document.implementation : null;
      if (!implementation || typeof implementation.createHTMLDocument !== 'function') {
        return block;
      }

      const workingDocument = implementation.createHTMLDocument('');
      workingDocument.body.innerHTML = innerHtml;

      const listItems = workingDocument.body.querySelectorAll('li');
      listItems.forEach((li) => {
        const liStyleText = li.getAttribute('style');
        const liStyleMap = MjmlStylesService.parseStyleText(liStyleText);

        const spans = Array.from(li.querySelectorAll('span[style]'))
          .filter(span => span.closest('li') === li);

        let spanStyleMap = null;
        if (spans.length) {
          const uniqueStyles = new Set(
            spans
              .map(span => span.getAttribute('style'))
              .filter(Boolean)
              .map(style => MjmlStylesService.normalizeStyleText(style))
              .filter(Boolean)
          );

          if (uniqueStyles.size === 1) {
            spanStyleMap = MjmlStylesService.parseStyleText([...uniqueStyles][0]);
          }
        }

        if (!liStyleMap && !spanStyleMap) {
          return;
        }

        const mergedStyles = {
          ...(liStyleMap || {}),
          ...(spanStyleMap || {})
        };

        const sanitizedStyles = MjmlStylesService.filterAllowedStyles(mergedStyles);

        const normalizedStyles = MjmlStylesService.buildStyleText(sanitizedStyles);
        if (!normalizedStyles) {
          return;
        }

        const className = MjmlStylesService.getClassName(normalizedStyles);
        li.classList.add(className);
        cssRules.set(className, sanitizedStyles);

        li.removeAttribute('style');

        if (spanStyleMap) {
          spans.forEach(span => span.removeAttribute('style'));
        }
      });

      return `${openTag}${workingDocument.body.innerHTML}${closeTag}`;
    });

    if (!cssRules.size) {
      return updatedMjml;
    }

    const cssText = MjmlStylesService.buildCssRules(cssRules);
    return MjmlStylesService.injectMjmlStyles(updatedMjml, cssText);
  }

  static normalizeStyleText(styleText) {
    if (typeof styleText !== 'string') {
      return '';
    }

    return styleText
      .split(';')
      .map(part => part.trim())
      .filter(Boolean)
      .join(';');
  }

  static parseStyleText(styleText) {
    if (typeof styleText !== 'string' || !styleText.trim()) {
      return null;
    }

    const styleMap = {};
    styleText.split(';').forEach((part) => {
      const trimmed = part.trim();
      if (!trimmed) {
        return;
      }

      const dividerIndex = trimmed.indexOf(':');
      if (dividerIndex === -1) {
        return;
      }

      const property = trimmed.slice(0, dividerIndex).trim().toLowerCase();
      const value = trimmed.slice(dividerIndex + 1).trim();
      if (!property || !value) {
        return;
      }

      styleMap[property] = value;
    });

    return Object.keys(styleMap).length ? styleMap : null;
  }

  static buildStyleText(styleMap) {
    if (!styleMap || typeof styleMap !== 'object') {
      return '';
    }

    return Object.entries(styleMap)
      .map(([property, value]) => `${property}: ${value};`)
      .join(' ')
      .trim();
  }

  static getClassName(styleText) {
    return `gjs-li-style-${MjmlStylesService.hashString(styleText)}`;
  }

  static hashString(value) {
    let hash = 0;
    for (let i = 0; i < value.length; i += 1) {
      hash = ((hash << 5) - hash) + value.charCodeAt(i);
      hash = Math.trunc(hash);
    }

    return Math.abs(hash).toString(36);
  }

  static buildCssRules(cssRules) {
    return Array.from(cssRules.entries()).map(([className, styles]) => {
      const baseStyles = MjmlStylesService.buildStyleText(styles);
      return `.${className} { ${baseStyles} }`;
    }).join('\n');
  }

  static filterAllowedStyles(styleMap) {
    const allowed = new Set([
      'color',
      'font-family',
      'font-size',
      'font-weight',
      'font-style',
      'line-height',
      'letter-spacing',
      'text-transform',
      'text-decoration'
    ]);

    return Object.entries(styleMap || {})
      .filter(([property]) => allowed.has(property))
      .reduce((acc, [property, value]) => {
        acc[property] = value;
        return acc;
      }, {});
  }

  static injectMjmlStyles(mjml, cssText) {
    const styleBlock = `<mj-style data-gjs-list-styles="true">\n${cssText}\n</mj-style>`;

    if (mjml.includes('data-gjs-list-styles="true"')) {
      return mjml.replace(/<mj-style\s+data-gjs-list-styles="true">[\s\S]*?<\/mj-style>/i, styleBlock);
    }

    if (mjml.includes('</mj-head>')) {
      return mjml.replace(/<\/mj-head>/i, `${styleBlock}\n</mj-head>`);
    }

    if (mjml.includes('<mjml')) {
      return mjml.replace(/<mjml[^>]*>/i, (match) => `${match}\n<mj-head>\n${styleBlock}\n</mj-head>`);
    }

    return mjml;
  }
}
