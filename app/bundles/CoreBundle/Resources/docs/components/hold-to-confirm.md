# HoldToConfirmButton

Twig and native JavaScript equivalent of Acervo's Spectrum UI hold-to-confirm button. React is not shipped. The motion engine and the MIT BorderBeam CSS generator preserve the original spring parameters, beam palettes and theme geometry.

```twig
{% include '@MauticCore/Components/hold-to-confirm.html.twig' with {
    label: 'mautic.example.delete', confirmedLabel: 'mautic.example.deleted',
    duration: 1200, resetDelay: 1500, size: 'md', disabled: false
} only %}
```

Options: `duration` (1200 ms), `resetDelay` (1500 ms; zero stays confirmed), `label`, `confirmedLabel`, `icon`, `size` (`sm`, `md`, `lg`), `disabled`, `className`, `variant` (`destructive`, `neutral`, `beam`), `colorVariant` (`sunset`, `ocean`, `colorful`, `mono`), `beamSize` (`md`), `theme` (`auto`, `light`, `dark`), `strength` (0.65), `beamClassName`, `active` (true), and `id`. Both labels are translated and escaped. For rich icons use the `icon` block of Twig `embed`; client-side updates accept a DOM node as `icon`.

```javascript
const element = document.querySelector('[data-component="hold-to-confirm"]');
const button = window.MauticUI.HoldToConfirmButton.get(element);
button.update({ onConfirm: () => performAuthorizedAction() });
```

The bubbling `mautic:hold-to-confirm:confirm` event is the alternative to `onConfirm`; choose one to avoid executing the action twice. The client-side confirmation is only a gesture: enforce authorization and CSRF protection on the action endpoint.

Hold Space, Enter or the pointer for the specified duration. A second input joins without restarting progress; releasing one input does not cancel another. Release, pointer cancellation or blur unwind incomplete holds. Completion consumes outstanding holds and fires once. `resetDelay` resets for a later hold, or call `reset()` explicitly. Reduced motion preserves confirmation timing. Removed nodes cancel animation, callbacks, reset timers, beam observers and styles. AJAX insertions initialize automatically.

The original light/dark treatment follows `.dark`. Beam `theme: auto` follows the OS color-scheme media query, as in the source package. The Manrope font is bundled with its OFL license. The default trash SVG follows Lucide's ISC-licensed geometry.

## Verification

Run the component tooling commands documented in `animated-checkbox.md`, or from `Assets/components`: `npm ci`, `npm run build`, `npx playwright install --with-deps chromium`, `npm test` (through DDEV locally). The suite type-checks, renders real Twig, checks Mautic's minifier and exercises Chromium. CI discovers it in `core-components.yml`.

Sixteen browser tests cover state, timing, pointer/keyboard cooperation, cancellation, reset, disabled updates, removal, beam activation, reduced motion, sizes, escaping and explicit zero values. Twelve stable screenshots of the original React button matched dimensions with zero Pixelmatch differences at threshold 0.05: all three sizes, idle/confirmed, light/dark. Beam animations and arbitrary host CSS overrides are not covered by that screenshot claim.
