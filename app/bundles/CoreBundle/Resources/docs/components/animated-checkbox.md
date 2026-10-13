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

The original React component was rendered separately with its source theme tokens. Eighteen stable-state screenshots (three sizes, unchecked/checked/indeterminate, light/dark) matched dimensions and had zero differing pixels under Pixelmatch threshold 0.05. This establishes those stable states, not every possible custom motion prop or application override.
