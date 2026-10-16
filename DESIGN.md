
## Components

Every page must be built using components.

### Rules
- Reuse existing components. Ask permission before creating a new one when none fits the flow.
- Import and configure components only through their public API (content, state, callbacks, config). Never copy a component to change it, and never create wrappers, aliases, or redesigns of existing ones.
- Custom styling and visual or structural changes to shared components need permission first. Make the change in the imported component so it applies to all instances.
- No custom visual CSS without permission. Plain HTML and hardcoded classes are allowed without permission only for page layout (rows, columns, and similar). Otherwise, plain HTML is limited to semantic structure and text, with no component behavior.

### Existing
- **card**: Use for a single item with image, title and call to action (types: default, feature, feature--large, link, logo, pictogram, static).
- **card-group**: Use to lay out several cards in a responsive grid, optionally with a featured card-in-card first.
- **card-in-card**: Use to highlight one main item at the top of a card group.
- **checkbox**: Use when the user picks one or more options from a list inside a form.
- **code-snippet**: Use to show code, commands or config, inline or as a single or multi-block copyable snippet.
- **contained-list**: Use for a list of rows that need actions or selection (variants: default, actions, interactive, interactive-actions).
- **content-block**: Use to start a titled section of a page with an optional subheading.
- **content-group**: Use to group a heading with its paragraphs inside a content block.
- **content-item**: Use to show one unit of content such as an image, logo, pictogram, statistic or text.
- **content-item-row**: Use to place several content items side by side (variants: media, featured, default).
- **content-section**: Use on settings-style pages where a heading sits on the left and its content on the right.
- **list--styled**: Use for numbered steps or ordered lists.
- **list-unordered**: Use for bulleted lists, including nested ones.
- **modal**: Use to confirm an action or show focused content without leaving the page. Variants: `productive` for functionality (actions, forms, confirmations); `expressive` for content (text, media, reading).
- **pictogram**: Use for decorative illustrations, such as empty states, by name and size.
- **tile**: Use for a compact block of content with an optional button or click target (types: base, clickable, expandable, expandable-interactive, mini).
- **toggletip**: Use to reveal a short explanation from an icon or label next to a control.
- **uishell**: Use for the global app header with skip link, menu toggle, brand and actions.

### Creating new
- Assign any colors to tokens from theme.scss.
