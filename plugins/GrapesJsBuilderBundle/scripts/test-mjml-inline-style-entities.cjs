const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

global.window = global;

const mjml2html = require('mjml-browser');
const helperPath = path.resolve(
  __dirname,
  '../Assets/library/js/preset-mautic/mjml/normalize-inline-style-entities.js'
);

async function main() {
  const source = fs.readFileSync(helperPath, 'utf8');
  const url = `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`;
  const { default: normalize } = await import(url);

  const cases = [
    [
      'BASIC',
      '<p style="font-family:&quot;Segoe UI&quot;, Arial;">x</p>',
      `<p style="font-family:'Segoe UI', Arial;">x</p>`,
    ],
    [
      'TEXT',
      '<mj-text>Example: style="font-family:&quot;Segoe UI&quot;"</mj-text>',
      '<mj-text>Example: style="font-family:&quot;Segoe UI&quot;"</mj-text>',
    ],
    [
      'COMMENT',
      '<!-- <p style="font-family:&quot;Segoe UI&quot;"> -->',
      '<!-- <p style="font-family:&quot;Segoe UI&quot;"> -->',
    ],
    [
      'MJ_STYLE',
      '<mj-style>p::after { content: "&quot;"; }</mj-style>',
      '<mj-style>p::after { content: "&quot;"; }</mj-style>',
    ],
    [
      'APOSTROPHE',
      '<p style="font-family:&quot;Bob\'s Font&quot;;">x</p>',
      `<p style="font-family:'Bob\\'s Font';">x</p>`,
    ],
    [
      'HREF',
      '<a href="?q=&quot;x&quot;" style="font-family:&quot;Segoe UI&quot;;">x</a>',
      `<a href="?q=&quot;x&quot;" style="font-family:'Segoe UI';">x</a>`,
    ],
    [
      'UNBALANCED',
      '<p style="font-family:&quot;Segoe UI;">x</p>',
      '<p style="font-family:&quot;Segoe UI;">x</p>',
    ],
    [
      'UPPERCASE_ENTITY',
      '<p style="font-family:&QUOT;Segoe UI&QUOT;;">x</p>',
      `<p style="font-family:'Segoe UI';">x</p>`,
    ],
    [
      'MIXED_CASE_ENTITY',
      '<p style="font-family:&QuOt;Segoe UI&qUoT;;">x</p>',
      `<p style="font-family:'Segoe UI';">x</p>`,
    ],
    [
      'GREATER_THAN_ATTRIBUTE',
      '<p data-condition="a > b" style="font-family:&quot;Segoe UI&quot;;">x</p>',
      `<p data-condition="a > b" style="font-family:'Segoe UI';">x</p>`,
    ],
    [
      'EXISTING_CSS_SINGLE_QUOTES',
      `<p style="font-family:'Arial', &quot;Segoe UI&quot;;">x</p>`,
      `<p style="font-family:'Arial', 'Segoe UI';">x</p>`,
    ],
    [
      'ENCODED_QUOTES_IN_CSS_STRING',
      `<p style="content:'&quot;Hello&quot;'; font-family:&quot;Segoe UI&quot;;">x</p>`,
      `<p style="content:'&quot;Hello&quot;'; font-family:'Segoe UI';">x</p>`,
    ],
    [
      'ENCODED_QUOTES_IN_ENCODED_CSS_STRING',
      `<p style="content:&apos;&quot;Hello&quot;&apos;; font-family:&quot;Segoe UI&quot;;">x</p>`,
      `<p style="content:&apos;&quot;Hello&quot;&apos;; font-family:'Segoe UI';">x</p>`,
    ],
    [
      'SINGLE_QUOTED_ATTRIBUTE',
      `<p style='font-family:&quot;Segoe UI&quot;, Arial;'>x</p>`,
      `<p style="font-family:'Segoe UI', Arial;">x</p>`,
    ],
    [
      'LITERAL_CSS_DOUBLE_QUOTES',
      `<p style='content:"Hello"; font-family:&quot;Segoe UI&quot;;'>x</p>`,
      `<p style="content:'Hello'; font-family:'Segoe UI';">x</p>`,
    ],
    [
      'STYLE_TEXT_IN_UNRELATED_ATTRIBUTE',
      `<p title='Example style="font-family:&quot;Segoe UI&quot;"' style="content:&quot;Hello&quot;;">x</p>`,
      `<p title='Example style="font-family:&quot;Segoe UI&quot;"' style="content:'Hello';">x</p>`,
    ],
    [
      'CSS_COMMENT',
      `<p style="/* &quot; */ font-family:&quot;Segoe UI&quot;;">x</p>`,
      `<p style="/* &quot; */ font-family:&quot;Segoe UI&quot;;">x</p>`,
    ],
    [
      'UNTERMINATED_CSS_STRING',
      `<p style="font-family:&quot;Segoe UI&quot;; content:'unfinished;">x</p>`,
      `<p style="font-family:&quot;Segoe UI&quot;; content:'unfinished;">x</p>`,
    ],
    [
      'UNTERMINATED_ATTRIBUTE',
      `<p style="font-family:&quot;Segoe UI&quot;;>x</p>`,
      `<p style="font-family:&quot;Segoe UI&quot;;>x</p>`,
    ],
    [
      'UNTERMINATED_COMMENT',
      `<!-- <p style="font-family:&quot;Segoe UI&quot;;">x</p>`,
      `<!-- <p style="font-family:&quot;Segoe UI&quot;;">x</p>`,
    ],
    [
      'UNTERMINATED_MJ_STYLE',
      `<mj-style><p style="font-family:&quot;Segoe UI&quot;;">x</p>`,
      `<mj-style><p style="font-family:&quot;Segoe UI&quot;;">x</p>`,
    ],
    [
      'UNKNOWN_CHARACTER_REFERENCE',
      `<p style="content:&quot;Hello&NewLine;world&quot;;">x</p>`,
      `<p style="content:&quot;Hello&NewLine;world&quot;;">x</p>`,
    ],
    [
      'AMP_DOUBLE_ENCODED_WITH_QUOTES',
      `<p style="font-family:&quot;Segoe UI&quot;;content:'R&amp;amp;D';">x</p>`,
      `<p style="font-family:'Segoe UI';content:'R&amp;amp;D';">x</p>`,
    ],

    [
      'ENCODED_BACKSLASH',
      `<p style="content:&quot;Hello&#92;&quot;world&quot;;">x</p>`,
      `<p style="content:&quot;Hello&#92;&quot;world&quot;;">x</p>`,
    ],
    [
      'ENCODED_HEX_BACKSLASH',
      `<p style="content:&quot;Hello&#x5c;&quot;world&quot;;">x</p>`,
      `<p style="content:&quot;Hello&#x5c;&quot;world&quot;;">x</p>`,
    ],
    [
      'ESCAPED_CLOSING_QUOTE',
      String.raw`<p style="content:&quot;unfinished\&quot;;">x</p>`,
      String.raw`<p style="content:&quot;unfinished\&quot;;">x</p>`,
    ],
    [
      'UNESCAPED_NEWLINE',
      `<p style="content:&quot;Hello\nworld&quot;;">x</p>`,
      `<p style="content:&quot;Hello\nworld&quot;;">x</p>`,
    ],
    [
      'UNRELATED_LITERAL_CSS_STRING',
      `<p title="&quot;" style='content:"Hello";'>x</p>`,
      `<p title="&quot;" style='content:"Hello";'>x</p>`,
    ],
    [
      'ESCAPED_QUOTE_OUTSIDE_STRING',
      String.raw`<p style="--label:\&quot;word\&quot;; font-family:&quot;Segoe UI&quot;;">x</p>`,
      String.raw`<p style="--label:\&quot;word\&quot;; font-family:'Segoe UI';">x</p>`,
    ],
  ];

  const apostropheCases = [];
  for (const apostrophe of ["'", '&apos;', '&#39;', '&#039;', '&#x27;', '&#X0027;']) {
    // A CSS backslash cannot escape an HTML delimiter: use an entity in single-quoted attributes.
    for (const delimiter of apostrophe === "'" ? ['"'] : ['"', "'"]) {
      cases.push([
        `APOSTROPHE_${apostrophe}_${delimiter}`,
        `<p style=${delimiter}font-family:&quot;Bob${apostrophe}s Font&quot;;${delimiter}>x</p>`,
        `<p style="font-family:'Bob\\${apostrophe}s Font';">x</p>`,
      ]);
      apostropheCases.push([
        `APOSTROPHE_${apostrophe}_${delimiter}`,
        `font-family:&quot;Bob${apostrophe}s Font&quot;;`,
        `font-family:'Bob\\${apostrophe}s Font';`,
        delimiter,
      ]);
    }
  }

  const escapeCases = [
    ['HEX_ESCAPE', String.raw`A\26 B`, String.raw`A\26 B`],
    ['SIX_DIGIT_ESCAPE', String.raw`A\000026B`, String.raw`A\000026B`],
    ['ESCAPED_BACKSLASH', String.raw`C:\\fonts`, String.raw`C:\\fonts`],
    [
      'ESCAPED_DOUBLE_QUOTE',
      String.raw`Say \&quot;Hello\&quot;`,
      String.raw`Say \&quot;Hello\&quot;`,
    ],
    ['ESCAPED_APOSTROPHE', String.raw`Bob\'s Font`, String.raw`Bob\'s Font`],
    ['ESCAPED_ENCODED_APOSTROPHE', String.raw`Bob\&#39;s Font`, String.raw`Bob\&#39;s Font`],
    ['EVEN_BACKSLASHES_BEFORE_APOSTROPHE', String.raw`Bob\\'s Font`, String.raw`Bob\\\'s Font`],
    ['TRAILING_BACKSLASH_PAIR', String.raw`path\\`, String.raw`path\\`],
    ['LINE_CONTINUATION', 'Hello\\\nworld', 'Hello\\\nworld'],
  ];

  for (const [name, encoded, baseline] of escapeCases) {
    cases.push([
      name,
      `<p style="content:&quot;${encoded}&quot;;">x</p>`,
      `<p style="content:'${baseline}';">x</p>`,
    ]);
  }

  for (const input of [null, undefined, 123, '', '<p style="color:red;">x</p>']) {
    assert.equal(normalize(input), input, 'non-encoded input');
  }

  for (const [name, input, expected] of cases) {
    assert.equal(normalize(input), expected, name);
    assert.equal(normalize(normalize(input)), expected, `${name} idempotence`);
    console.log(`${name}_GATE=PASS`);
  }

  const mjml = `<mjml>
    <mj-head><mj-style inline="inline">p { color: black; }</mj-style></mj-head>
    <mj-body><mj-section><mj-column><mj-text>
      <p style="font-family:&quot;Segoe UI&quot;, Arial;">x</p>
    </mj-text></mj-column></mj-section></mj-body>
  </mjml>`;

  const result = mjml2html(normalize(mjml));
  assert.ok(result.html);
  assert.equal(result.errors?.length || 0, 0);
  assert.match(result.html, /Segoe UI/);

  const differentialCases = [
    ['FONT_FAMILY', 'font-family:&quot;Segoe UI&quot;, Arial;', "font-family:'Segoe UI', Arial;"],
    ['CONTENT', 'content:&quot;Hello&quot;;', "content:'Hello';"],
    [
      'FONT_VARIATION',
      'font-variation-settings:&quot;wght&quot; 400;',
      "font-variation-settings:'wght' 400;",
    ],
    ['CUSTOM_PROPERTY', '--custom-label:&quot;Hello&quot;;', "--custom-label:'Hello';"],
    ['APOSTROPHE', 'font-family:&quot;Bob&#39;s Font&quot;;', "font-family:'Bob\\&#39;s Font';"],
    ['NESTED_QUOTES', "content:'&quot;Hello&quot;';", "content:'&quot;Hello&quot;';"],
    ...apostropheCases,
    ...escapeCases.map(([name, encoded, baseline]) => [
      name,
      `content:&quot;${encoded}&quot;;`,
      `content:'${baseline}';`,
    ]),
  ];

  const convertedStyle = (declaration, applyNormalization, delimiter = '"') => {
    const input = `<mjml>
      <mj-head><mj-style inline="inline">p { color: black; }</mj-style></mj-head>
      <mj-body><mj-section><mj-column><mj-text>
        <p data-probe="target" style=${delimiter}${declaration}${delimiter}>Probe</p>
      </mj-text></mj-column></mj-section></mj-body>
    </mjml>`;

    const conversion = mjml2html(applyNormalization ? normalize(input) : input);

    assert.ok(conversion.html);
    assert.equal(conversion.errors?.length || 0, 0);

    const tag = conversion.html.match(/<p\b(?=[^>]*data-probe="target")[^>]*>/i)?.[0];
    assert.ok(tag, 'target paragraph must survive conversion');

    const style = tag.match(/\bstyle="([^"]+)"/i)?.[1];
    assert.ok(style, 'target style must survive conversion');
    return style;
  };

  for (const [name, encoded, baseline, delimiter] of differentialCases) {
    assert.equal(
      convertedStyle(encoded, true, delimiter),
      convertedStyle(baseline, false),
      `${name} CSS equivalence`
    );
    console.log(`${name}_DIFFERENTIAL_GATE=PASS`);
  }

  assert.equal(
    convertedStyle('font-family:&quot;Segoe UI&quot;, Arial;', true, "'"),
    convertedStyle("font-family:'Segoe UI', Arial;", false),
    'single-quoted HTML attribute CSS equivalence'
  );
  console.log('SINGLE_QUOTED_ATTRIBUTE_DIFFERENTIAL_GATE=PASS');

  console.log('DIFFERENTIAL_CSS_GATE=PASS');

  console.log('NORMALIZATION_CONTRACT_GATE=PASS');
  console.log('MJML_CONVERSION_GATE=PASS');
}

main().catch((error) => {
  console.error(`${error.name}: ${error.message}`);
  process.exitCode = 1;
});
