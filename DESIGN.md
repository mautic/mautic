---
version: alpha
name: Mautic
---

## Overview

Mautic is an open-source marketing automation product. The UI looks serious: it is built on Bootstrap and uses a restrained palette and layout. The copy is playful: text can be more relaxed while the interface stays formal.

## Colors

Color variables are defined in `app/bundles/CoreBundle/Assets/css/app/scss/_variables.scss`. Use them instead of hardcoded values.

Colors are grouped by the role each variable has in the theme. Each value is written as `var(--name, default)`, where the default is the light theme value.

### Design Tokens

```yaml
colors:
  brand:
    primary: "var(--primary-60, #4e5e9e)"
  text:
    primary: "var(--text-primary, #161616)"
    secondary: "var(--text-secondary, #525252)"
    helper: "var(--text-helper, #6f6f6f)"
    placeholder: "var(--text-placeholder, #a8a8a8)"
    inverse: "var(--text-inverse, #ffffff)"
    error: "var(--text-error, #da1e28)"
  layer:
    layer-01: "var(--layer-01, #f4f4f4)"
    layer-02: "var(--layer-02, #ffffff)"
    layer-hover-01: "var(--layer-hover-01, #e8e8e8)"
    layer-selected-01: "var(--layer-selected-01, #e0e0e0)"
    layer-accent-01: "var(--layer-accent-01, #e0e0e0)"
  support:
    error: "var(--support-error, #da1e28)"
    success: "var(--support-success, #24a148)"
    warning: "var(--support-warning, #f1c21b)"
    info: "var(--support-info, #0043ce)"
  border:
    subtle-01: "var(--border-subtle-01, #e0e0e0)"
    strong-01: "var(--border-strong-01, #8d8d8d)"
    interactive: "var(--border-interactive, #4e5e9e)"
  interactive:
    interactive: "var(--interactive, #4e5e9e)"
    focus: "var(--focus, #4e5e9e)"
    button-primary: "var(--button-primary, #4e5e9e)"
```

## Typography

Type classes are defined in `app/bundles/CoreBundle/Assets/css/app/scss/components/_type.scss`. Use them instead of writing the styles again.

The UI uses **Source Sans 3**, with Helvetica Neue, Helvetica, Arial and sans-serif as fallbacks. Monospace text uses Menlo, Monaco, Consolas, Courier New and monospace. Apply the tokens below through the matching `.type-*` classes (for example `.type-heading-06`); plain `h1`–`h6` tags do not use them by default.

### Design Tokens

```yaml
typography:
  heading-06:
    fontSize: "var(--heading-06-font-size, 42px)"
    lineHeight: "var(--heading-06-line-height, 50px)"
    fontWeight: "var(--heading-06-font-weight, 300)"
    letterSpacing: "var(--heading-06-letter-spacing, 0px)"
  heading-05:
    fontSize: "var(--heading-05-font-size, 32px)"
    lineHeight: "var(--heading-05-line-height, 40px)"
    fontWeight: "var(--heading-05-font-weight, 400)"
    letterSpacing: "var(--heading-05-letter-spacing, 0px)"
  heading-04:
    fontSize: "var(--heading-04-font-size, 28px)"
    lineHeight: "var(--heading-04-line-height, 36px)"
    fontWeight: "var(--heading-04-font-weight, 400)"
    letterSpacing: "var(--heading-04-letter-spacing, 0px)"
  heading-03:
    fontSize: "var(--heading-03-font-size, 20px)"
    lineHeight: "var(--heading-03-line-height, 28px)"
    fontWeight: "var(--heading-03-font-weight, 400)"
    letterSpacing: "var(--heading-03-letter-spacing, 0px)"
  heading-02:
    fontSize: "var(--heading-02-font-size, 16px)"
    lineHeight: "var(--heading-02-line-height, 24px)"
    fontWeight: "var(--heading-02-font-weight, 600)"
    letterSpacing: "var(--heading-02-letter-spacing, 0px)"
  body-01:
    fontSize: "var(--body-01-font-size, 14px)"
    lineHeight: "var(--body-01-line-height, 20px)"
    fontWeight: "var(--body-01-font-weight, 400)"
    letterSpacing: "var(--body-01-letter-spacing, 0.16px)"
  helper-text-01:
    fontSize: "var(--helper-text-01-font-size, 12px)"
    lineHeight: "var(--helper-text-01-line-height, 16px)"
    fontWeight: "var(--helper-text-01-font-weight, 400)"
    letterSpacing: "var(--helper-text-01-letter-spacing, 0.32px)"
```

## Layout

Layout follows the official Bootstrap documentation: https://getbootstrap.com/docs/5.3/layout/grid/

The layout uses the Bootstrap grid. Page layout (rows, columns, and similar) can use plain HTML and hardcoded classes without permission, as stated in Do's and Don'ts.

## Elevation & Depth

Depth is shown with layered background colors, not shadows. Each layer sits on the layer below it and uses its own color. Carbon works the same way with its layer sets.

Two classes apply the layer automatically: `.layer-one` (the default) and `.layer-two`. Each one remaps the context variables (`--layer`, `--field`, `--border-strong`, etc.) to its layer set. Components use these context names, so placing a container with `.layer-two` switches every child to the second layer without changing the component. The Mautic implementation uses only two layers and alternates between them, instead of stacking the four layers Carbon defines.

## Shapes

The shape language is defined by square corners. The theme base radius is `0px`, which the small, medium and large radius variables derive from. Interactive elements, containers and inputs use these variables, so they stay square until the base radius changes.

Carbon also keeps corners at 0 by default for components.

### Design Tokens

```yaml
rounded:
  sm: "var(--border-radius-sm, 0px)"
  md: "var(--border-radius-md, 0px)"
  lg: "var(--border-radius-lg, 0px)"
```

## Components

### card

Use for a single item with image, title and call to action.

```yaml
card:
  default: "Use for a standard item with image, title and copy."
  feature: "Use to highlight one item with a larger image and title."
  feature--large: "Use to highlight one item at the top of a page, with the largest title."
  link: "Use when the whole card is a link to another page."
  logo: "Use to represent a brand, company or similar organization."
  pictogram: "Use to show an item with a pictogram instead of an image."
  static: "Use to show information only, with no action."
```

### card-group

```yaml
card-group: "Use to lay out several cards in a responsive grid, optionally with a featured card-in-card first."
```

### card-in-card

```yaml
card-in-card: "Use to highlight one main item at the top of a card group."
```

### code-snippet

```yaml
code-snippet: "Use to show code, commands or config, inline or as a single or multi-block copyable snippet."
```

### contained-list

Use for a list of rows that need actions or selection.

```yaml
contained-list:
  default: "Use for a list of rows that only displays content."
  actions: "Use for a list of rows where each row has its own actions."
  interactive: "Use for a list of rows the user can select."
  interactive-actions: "Use for a list of selectable rows where each row also has actions."
```

### content-block

Container. Holds a heading, subheading, copy, optional CTA, and nested content through the child container.

```yaml
content-block: "Use to start a titled section of a page. Nest content-group or content-item inside it through the child container."
```

### content-group

Container. Holds a smaller heading, copy, optional CTA, and nested content through the child container. It has no subheading.

```yaml
content-group: "Use to group a smaller heading with its copy and nested content inside a content block."
```

### content-item

Leaf. Holds a heading, copy and optional CTA. It has no child container, so it cannot contain other components.

```yaml
content-item:
  image: "Use to show an image with a heading, copy and optional CTA."
  logo: "Use to show a brand, company or similar logo with a heading and copy."
  pictogram: "Use to show a pictogram with a heading and copy."
  statistic: "Use to highlight a single number or statistic with a heading and copy."
  text: "Use to show a heading and copy with no media."
```

### content-item-row

Use to place several content items side by side.

```yaml
content-item-row:
  media: "Use to place content items side by side with media, such as images."
  featured: "Use to place one featured item next to other items."
  default: "Use to place content items side by side in a standard row."
```

### content-section

```yaml
content-section: "Use on settings-style pages where a heading sits on the left and its content on the right."
```

### list-unordered

```yaml
list-unordered: "Use for bulleted lists, including nested ones."
```

### modal

Use to confirm an action or show focused content without leaving the page.

```yaml
modal:
  productive: "Use for functionality: actions, forms and confirmations."
  expressive: "Use for content: text, media and reading."
```

### pictogram

```yaml
pictogram: "Use for decorative illustrations, such as empty states, by name and size."
```

### tile

Use for a compact block of content with an optional button or click target. Tile is the main component to create a layer: place content inside a tile to add a new layer on top of the background.

```yaml
tile:
  base: "Use for a compact block of content with an optional button."
  clickable: "Use for a block of content that is a single click target."
  expandable: "Use for content that the user can expand to read more."
  expandable-interactive: "Use for expandable content that has interactive elements inside."
  mini: "Use for a small clickable block with an icon."
```

### toggletip

Use to reveal a short explanation from an icon or label next to a control. Toggletip requires user interaction to open: it is triggered by a click, not by hover like a tooltip.

```yaml
toggletip: "Use to reveal a short explanation that the user opens by clicking the trigger."
```

### uishell

```yaml
uishell: "Use for the global app header with skip link, menu toggle, brand and actions."
```

## Do's and Don'ts

- Do build every page using components.
- Do reuse an existing component. Ask permission before creating a new one when none fits the flow.
- Do import and configure components only through their public API (content, state, callbacks, config).
- Don't copy a component to change it, and don't create wrappers, aliases, or redesigns of existing ones.
- Do ask permission before any custom styling or visual or structural change to a shared component. Make the change in the imported component so it applies to all instances.
- Don't write custom visual CSS without permission. Plain HTML and hardcoded classes are allowed without permission only for page layout (rows, columns, and similar). Otherwise, plain HTML is limited to semantic structure and text, with no component behavior.
- Do use `var(--name)` tokens for every value. Don't write a hex, rgb, or pixel value directly.
- Do use the primary color for one main action per view.
- Do keep WCAG AA contrast for text: 4.5:1 for normal text.
