# AnimatedCheckbox

Native Twig/JavaScript equivalent of the Acervo Animate UI Checkbox and its Radix primitive. No React runtime is shipped.

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

The bubbling `mautic:animated-checkbox:change` event contains `{ checked }`. Native input/change events and form submission work in uncontrolled mode. Space toggles; Enter is suppressed like Radix. Form reset restores the initial state. Disabled fields are omitted from form data. AJAX insertions initialize automatically; removed nodes release listeners and animation handles.

Styles are scoped; `.dark` selects the original dark colors. Override `--acervo-primary`, `--acervo-primary-foreground`, `--acervo-background`, `--acervo-input`, `--acervo-foreground` and `--acervo-ring` to supply another theme.

## Verification

From `app/bundles/CoreBundle/Assets/components`, run `npm ci`, `npm run build`, `npx playwright install --with-deps chromium`, `npm test`. Run these through DDEV locally. Tests render the actual Twig using the installed Twig package, type-check strict TypeScript, exercise Chromium and run Mautic's PHP minifier. CI discovers the suite in `core-components.yml`.

The original React component was rendered separately with its source theme tokens. Eighteen stable-state screenshots (three sizes, unchecked/checked/indeterminate, light/dark) matched dimensions and had zero differing pixels under Pixelmatch threshold 0.05.

The committed React reference also verifies the PHP-minified assets in Chromium and compares animation trajectories: 24 configurations (all three sizes, both variants, both themes and both system motion preferences), 21 interaction sequences and 135 sampled frames per configuration. Scale, stroke length, opacity and dash offset are compared to four decimal places; SVG bounds use the same numeric tolerance and computed colors match exactly. Screenshots use Pixelmatch threshold 0.05. Small rasterization differences below that perceptual threshold are not byte-identical PNGs.

The native implementation uses Motion's DOM renderer without React. Hover/press use the original default scale spring (`stiffness: 550`, `damping: 30`, `restSpeed: 10`). Gesture helpers preserve hover/tap priority, Enter, cancellation and pointer filtering. The check draws with 200ms ease-out after a 200ms delay; the indeterminate line draws immediately over 200ms. Changing shape replaces the old geometry immediately instead of fading two shapes together. Interruptions continue from the existing animated values. Initial SVG motion values are explicitly seeded to prevent accelerated opacity from leaving invalid presentation attributes.

The reference ignores the system reduced-motion preference by default, so these DOM animations explicitly preserve that behavior instead of silently using the imperative Motion default. Color transitions retain Tailwind's 500ms cubic-bezier(.4,0,.2,1). Focus uses the original white ring offset; invalid ring colors retain the light/dark opacity differences. `--acervo-ring-offset-color` can override the offset color.

The comparison harness synchronizes RAF and browser/compositor timelines without changing the source's keyframes or easing. It checks intermediate opacity and stroke progress, not just final states. See `Assets/components/tests/reference/README.md` for reference provenance and timing details. Run `npx playwright test tests/checkbox-motion.spec.mjs` for the focused animation suite. These cases do not establish equivalence for arbitrary custom Motion props or application overrides.
