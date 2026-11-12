/**
 * Normalize encoded CSS double-quoted strings in HTML style attributes.
 * Preserve markup outside style attributes and leave ambiguous values unchanged.
 */
export default function normalizeInlineStyleEntities(mjml) {
  if (typeof mjml !== 'string' || !/&quot;/i.test(mjml)) {
    return mjml;
  }

  const normalizeStyle = (value) => {
    const matches = [...value.matchAll(/&quot;/gi)];
    if (matches.length === 0 || matches.length % 2 !== 0) {
      return value;
    }

    let quoted = false;
    let output = '';
    let cursor = 0;

    for (const match of matches) {
      const segment = value.slice(cursor, match.index);

      if (quoted) {
        output += segment.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
      } else {
        output += segment;
      }

      output += "'";
      quoted = !quoted;
      cursor = match.index + match[0].length;
    }

    return output + value.slice(cursor);
  };

  const normalizeTag = (tag) => tag.replace(
    /(\sstyle\s*=\s*")([^"]*)(")/gi,
    (match, prefix, value, suffix) => prefix + normalizeStyle(value) + suffix
  );

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
    output += /^<[a-z][\w:-]*(?=\s|\/?>)/i.test(tag)
      ? normalizeTag(tag)
      : tag;

    if (/^<mj-style(?=\s|>)/i.test(tag) && !/\/\s*>$/.test(tag)) {
      rawStyle = true;
    }

    cursor = end + 1;
  }

  return output;
}
