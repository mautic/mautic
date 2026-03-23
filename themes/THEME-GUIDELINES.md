# Draft guidelines for theme creators

## 1) Use MJML-native theming first (`<mj-attributes>`)
Prefer defining defaults via:

- `<mj-all ...>` for global font + base color
- `<mj-text ...>` for base text size/line-height/padding
- `<mj-button ...>` for base button shape/weight/padding
- `<mj-section ...>`, `<mj-column ...>` for layout padding defaults

Then layer semantic tokens via `<mj-class ...>`.

## 2) Keep token responsibility clear
Recommended split:

- **Global defaults**: base typography and safe defaults  
  (`<mj-all>`, `<mj-text>`, `<mj-button>`, etc.)
- **Tokens** (`mj-class`): semantic variations  
  e.g. `t-h1`, `t-surface-2`, `t-btn-secondary`

## 3) Avoid relying on `<mj-style>` for critical appearance
`<mj-style>` can be stripped or inconsistently applied in some clients.

- You may include small safe resets (eg `p { margin: 0; }`) using `inline="inline"`.
- Do **not** use `<mj-style>` as the only definition of core typography, button colors, or layout spacing.

## 4) Token naming rules (recommended)
- Prefix all tokens with `t-` (theme token).
- Use kebab-case (`t-btn-primary`, not `tBtnPrimary`).
- Tokens should be **portable** between themes (semantic, not brand-specific).

## 5) Ensure token completeness for builder defaults
If your theme omits any required token, the builder may:
- fall back to GrapesJS/MJML defaults (eg gray button),
- or not apply expected `mj-class` on drop.

At minimum define: `t-body`, `t-btn`, `t-btn-primary`, `t-section`, `t-surface-1`.

## 6) Keep tokens “non-destructive”
Tokens should generally set:
- `font-size`, `line-height`, `font-weight`
- `background-color`, `color`
- `padding` / `inner-padding`
- `border-radius`

Avoid putting layout-breaking properties into typography tokens (eg massive padding inside `t-h1`).

---

## Theme skeleton snippet (copy/paste starter)

```mjml
<mjml>
  <mj-head>
    <mj-attributes>
      <!-- Global defaults (required) -->
      <mj-all font-family="Arial, sans-serif" color="#111827"></mj-all>
      <mj-text font-size="16px" line-height="24px" padding="0"></mj-text>
      <mj-button font-weight="600" border-radius="6px" padding="0"></mj-button>
      <mj-section padding="24px 0"></mj-section>
      <mj-column padding="0"></mj-column>

      <!-- REQUIRED TOKENS -->
      <mj-class name="t-body" font-size="16px" line-height="24px"></mj-class>

      <mj-class name="t-btn" inner-padding="12px 18px"></mj-class>
      <mj-class name="t-btn-primary" background-color="#2563EB" color="#FFFFFF"></mj-class>

      <mj-class name="t-section" padding="32px 24px"></mj-class>
      <mj-class name="t-surface-1" background-color="#FFFFFF"></mj-class>

      <!-- RECOMMENDED OPTIONAL TOKENS -->
      <mj-class name="t-lead" font-size="18px" line-height="28px"></mj-class>
      <mj-class name="t-h1" font-size="32px" line-height="38px" font-weight="700"></mj-class>
      <mj-class name="t-h2" font-size="24px" line-height="30px" font-weight="700"></mj-class>
      <mj-class name="t-h3" font-size="18px" line-height="24px" font-weight="700"></mj-class>
      <mj-class name="t-h4" font-size="16px" line-height="22px" font-weight="700"></mj-class>

      <mj-class name="t-btn-secondary" background-color="#E5E7EB" color="#111827"></mj-class>
      <mj-class name="t-surface-2" background-color="#F3F4F6"></mj-class>
    </mj-attributes>

    <!-- Optional non-critical CSS (keep minimal) -->
    <mj-style inline="inline">
      p, li { margin: 0; padding: 0; }
    </mj-style>
  </mj-head>

  <mj-body background-color="#F3F4F6">
    <!-- Your template content -->
  </mj-body>
</mjml>
```