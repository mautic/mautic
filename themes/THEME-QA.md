# Theme QA (MJML Token Contract)

## Purpose
Validate that a theme correctly implements the standard token contract used by the builder:
- Existing template renders correctly in the editor canvas
- New blocks dropped from the block panel inherit theme defaults
- Theme tokens persist on save/export

## Required tokens checklist
Verify these tokens exist in `<mj-head><mj-attributes>` as `<mj-class name="...">`:

- [ ] `t-body`
- [ ] `t-btn`
- [ ] `t-btn-primary`
- [ ] `t-section`
- [ ] `t-surface-1`

Recommended:
- [ ] `t-btn-secondary`
- [ ] `t-surface-2`
- [ ] `t-h1`, `t-h2`, `t-h3`, `t-h4`
- [ ] `t-lead`

## Manual QA steps (builder)

### 1) Load template
- [ ] Open the builder with the theme template
- [ ] Confirm the canvas matches expected styling (fonts, sizes, button colors, section backgrounds)

### 2) Ensure mj-attributes are not shown as editable blocks
- [ ] In Layers / canvas, confirm there are no visible components corresponding to `<mj-attributes>` children (eg `mj-class`, `mj-all`, etc.)

### 3) Drag-and-drop defaults (critical)
Drag these blocks from the block panel and verify they match theme defaults:

#### Text
- [ ] Drop an `mj-text` block
- Expected:
    - `mj-class` includes `t-body` (or theme-equivalent configured default)
    - Text uses theme font-family, size, line-height, color

#### Button
- [ ] Drop an `mj-button` block
- Expected:
    - `mj-class` includes `t-btn` and `t-btn-primary`
    - Button background-color and text color match theme
    - Padding/shape match theme

#### Section
- [ ] Drop a section/columns block (eg 1-column, 2-columns, or a preset “text section”)
- Expected:
    - `<mj-section>` has `mj-class` including `t-section t-surface-1`
    - Padding matches theme section rhythm
    - Background matches surface token

### 4) Save & export persistence
- [ ] Save the email/template
- [ ] Re-open it
- [ ] Confirm tokens (`mj-class` values) are still present in MJML output
- [ ] Export/Preview HTML and confirm it matches the canvas appearance

## AI-assisted QA prompt (copy/paste)
Paste the theme `<mj-head>` and:
1) the MJML for a section with text + button
2) the rendered HTML snippet from preview/export

Ask:
- Do tokens exist and match the required list?
- Are any GrapesJS/MJML defaults overriding tokens (eg background-color="#414141", font-size="13px")?
- Does rendered HTML reflect token values (font sizes, colors, background colors)?