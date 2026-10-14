# AnimatedCheckbox

Native Twig/JavaScript equivalent of the Acervo Animate UI Checkbox. The animation runs on Motion (`motion` package), loaded once globally for every page through the shared component runtime. Templates ship no scripts.

```twig
{% include '@MauticCore/Components/animated-checkbox.html.twig' with {
    id: 'accepted', name: 'accepted', label: 'mautic.example.accept',
    defaultChecked: false, required: true
} only %}
```

Options preserve `checked`, `defaultChecked`, `disabled`, `required`, `name`, `value` (default `on`), `className`, `children`, `size` (`default`, `sm`, `lg`) and `variant` (`default`, `accent`). `checked`/`defaultChecked` accept booleans or `indeterminate`. `label`, `id`, `form` and `attributes` provide native DOM integration. Text labels are translated and escaped. For rich children use the `children` block in Twig `embed`; do not pass untrusted HTML.

Omitting `checked` selects uncontrolled state. Supplying it selects controlled state: the component requests the next value without changing the displayed value until the caller updates it.

```javascript
const element = document.querySelector('[data-component="animated-checkbox"]');
const checkbox = window.MauticUI.AnimatedCheckbox.get(element);
checkbox.update({ checked: false, onCheckedChange: (checked) => {
  checkbox.update({ checked });
} });
```

The bubbling `mautic:animated-checkbox:change` event contains `{ checked }`. Native input/change events and form submission work in uncontrolled mode. Enter is suppressed like Radix. Form reset restores the initial state. Disabled fields are omitted from form data. AJAX insertions initialize automatically; removed nodes release listeners and animation handles.

Styles are scoped; `.dark` selects the original dark colors. Override `--acervo-primary`, `--acervo-primary-foreground`, `--acervo-background`, `--acervo-input`, `--acervo-foreground` and `--acervo-ring` to supply another theme.

## Shared component runtime

Components do not bundle Motion themselves. The runtime lives in `Assets/components/src` and is compiled to one global file:

- `src/runtime.ts` exports `animate`, `hover`, `press`, `visualElementStore`, `emit`, `mountComponent` (registers a selector with a `MutationObserver` for AJAX insertions), `springScale` (the original hover/press spring) and `drawStroke` (the check/indeterminate draw).
- `src/runtime-global.ts` publishes that module as `window.MauticComponents`.
- `build.mjs` builds `Assets/js/98.component-runtime.js` once, and each component to `Assets/js/99.<name>.js` with `@mautic/components` resolved to the global runtime. Mautic loads every `Assets/js` file from every bundle, sorted, so `98` loads before `99` on every page without a per-page include.

To add a component in another branch, write `src/<name>.ts` that imports from `@mautic/components`, add its name to `build.mjs`, and use `mountComponent`, `springScale` and `drawStroke` instead of copying the motion code.

## Implementation

- `Resources/views/Components/animated-checkbox.html.twig`: markup. A single `path` (check) or `line` (indeterminate) is rendered; the script swaps it on state change.
- `Assets/components/src/animated-checkbox.ts`: state, events, form integration, gestures.
- `Assets/css/99.animated-checkbox.css`: static visuals and focus ring.

The hover and press scale uses `stiffness: 550`, `damping: 30`, `restSpeed: 10`. The check draws over 200 ms with ease-out after a 200 ms delay when checked; the indeterminate line draws immediately. Interruptions continue from the current animated values. Hover is ignored for touch pointers; press applies to the primary button and Enter.

## Verification

From `app/bundles/CoreBundle/Assets/components`, run `npm ci`, `npm run build`, `npx playwright install --with-deps chromium`, `npm test` through DDEV. The Playwright suite renders the Twig through PHP and checks keyboard, pointer, form and controlled-state behavior (12 tests, all passing). Generated `Assets/js/98.component-runtime.js` and `Assets/js/99.*.js` must be committed; CI fails if they drift from the build.

Dependencies: `motion` (bundled into the runtime), `esbuild` and `typescript` (build), `@playwright/test` (tests). Nothing else.
