/**
 * Normalize encoded CSS double-quoted strings in HTML style attributes.
 * Preserve markup outside style attributes and leave ambiguous values unchanged.
 */
export default function normalizeInlineStyleEntities(mjml) {
  if (typeof mjml !== 'string' || !/&quot;/i.test(mjml)) {
    return mjml;
  }

  const normalizeStyle = (value) => {
    // Other references can hide CSS syntax. Leave those and comments untouched.
    if (
      !/&quot;/i.test(value) ||
      /&(?!quot;|apos;|#0*(?:34|39);|amp;|#x0*(?:22|27);)/i.test(value) ||
      value.includes('/*')
    ) {
      return value;
    }

    const tokens = value.match(/&quot;|&apos;|&#(?:0*(?:34|39)|x0*(?:22|27));|[\s\S]/gi) || [];
    let quote = null;
    let converting = false;
    let output = '';

    for (let index = 0; index < tokens.length; index += 1) {
      const token = tokens[index];
      let character = token;
      if (token.length > 1) {
        character = /^&(?:quot|#0*34|#x0*22);$/i.test(token) ? '"' : "'";
      }

      if (character === '\\') {
        // Copy escapes verbatim, including escaped quotes and paired backslashes.
        if (index + 1 >= tokens.length) {
          return value;
        }
        output += token + tokens[index + 1];
        index += 1;
      } else if (quote && /[\r\n\f]/.test(character)) {
        return value;
      } else if (character === quote) {
        output += converting ? "'" : token;
        quote = null;
        converting = false;
      } else if (!quote && (character === '"' || character === "'")) {
        quote = character;
        converting = character === '"';
        output += converting ? "'" : token;
      } else {
        output += converting && character === "'" ? `\\${token}` : token;
      }
    }

    return quote ? value : output;
  };

  const normalizeTag = (tag) =>
    tag.replace(/(\s[^\s=/>]+\s*=\s*)(["'])([\s\S]*?)\2/g, (match, prefix, quote, value) => {
      if (!/^\sstyle\s*=\s*$/i.test(prefix)) {
        return match;
      }

      const normalized = normalizeStyle(value);
      // CSS backslashes cannot escape HTML delimiters. MJML also expects double quotes here.
      return normalized === value ? match : `${prefix}"${normalized.replace(/"/g, '&quot;')}"`;
    });

  let output = '';
  let cursor = 0;
  let rawStyle = false;

  while (cursor < mjml.length) {
    if (rawStyle) {
      const close = mjml.slice(cursor).search(/<\/mj-style\s*>/i);
      if (close < 0) {
        return output + mjml.slice(cursor);
      }
      output += mjml.slice(cursor, cursor + close);
      cursor += close;
      rawStyle = false;
    }

    const start = mjml.indexOf('<', cursor);
    if (start < 0) {
      return output + mjml.slice(cursor);
    }

    output += mjml.slice(cursor, start);

    if (mjml.startsWith('<!--', start)) {
      const end = mjml.indexOf('-->', start + 4);
      if (end < 0) {
        return output + mjml.slice(start);
      }
      output += mjml.slice(start, end + 3);
      cursor = end + 3;
      continue;
    }

    let end = start + 1;
    let quote = null;

    for (; end < mjml.length; end++) {
      const character = mjml[end];
      if (quote) {
        if (character === quote) {
          quote = null;
        }
      } else if (character === '"' || character === "'") {
        quote = character;
      } else if (character === '>') {
        break;
      }
    }

    if (end >= mjml.length) {
      return output + mjml.slice(start);
    }

    const tag = mjml.slice(start, end + 1);
    output += /^<[a-z][\w:-]*(?=\s|\/?>)/i.test(tag) ? normalizeTag(tag) : tag;

    if (/^<mj-style(?=\s|>)/i.test(tag) && !/\/\s*>$/.test(tag)) {
      rawStyle = true;
    }

    cursor = end + 1;
  }

  return output;
}
