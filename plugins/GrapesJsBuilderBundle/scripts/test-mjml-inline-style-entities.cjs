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
  ];

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
    ['FONT_VARIATION', 'font-variation-settings:&quot;wght&quot; 400;', "font-variation-settings:'wght' 400;"],
    ['CUSTOM_PROPERTY', '--custom-label:&quot;Hello&quot;;', "--custom-label:'Hello';"],
    ['APOSTROPHE', 'font-family:&quot;Bob&#39;s Font&quot;;', "font-family:'Bob&#39;s Font';"],
  ];

  const convertedStyle = (declaration, applyNormalization) => {
    const input = `<mjml>
      <mj-head><mj-style inline="inline">p { color: black; }</mj-style></mj-head>
      <mj-body><mj-section><mj-column><mj-text>
        <p data-probe="target" style="${declaration}">Probe</p>
      </mj-text></mj-column></mj-section></mj-body>
    </mjml>`;

    const conversion = mjml2html(applyNormalization ? normalize(input) : input);

    assert.ok(conversion.html);
    assert.equal(conversion.errors?.length || 0, 0);

    const tag = conversion.html.match(/<p\b(?=[^>]*data-probe="target")[^>]*>/i)?.[0];
    assert.ok(tag, 'target paragraph must survive conversion');

    return tag.match(/\bstyle="([^"]*)"/i)?.[1] ?? '';
  };

  for (const [name, encoded, baseline] of differentialCases) {
    assert.equal(
      convertedStyle(encoded, true),
      convertedStyle(baseline, false),
      `${name} CSS equivalence`
    );
    console.log(`${name}_DIFFERENTIAL_GATE=PASS`);
  }

  console.log('DIFFERENTIAL_CSS_GATE=PASS');

  console.log('NORMALIZATION_CONTRACT_GATE=PASS');
  console.log('MJML_CONVERSION_GATE=PASS');
}

main().catch((error) => {
  console.error(`${error.name}: ${error.message}`);
  process.exitCode = 1;
});
