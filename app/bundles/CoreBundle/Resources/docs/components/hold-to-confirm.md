# Hold to confirm

Native Twig and JavaScript adaptation of Acervo's Spectrum UI control. Production has no React, Motion, TypeScript build or custom font. The existing Mautic button classes provide typography, size, outer padding, border radius, danger colors, hover, disabled state and theme tokens. Component SCSS supplies the progress/feedback layout and an 8 px (`--spacing-03`) gap between its ring and label; generic button-span widths, heights and margins are reset only on the component's named parts. The shared `btn-nospin` opt-out prevents the global click handler from replacing the action icon with a loading spinner; runtime class updates preserve it. Native Web Animations samples the source spring parameters for press/release, icon pop and label roll; requestAnimationFrame drives the functional progress ring. The MIT BorderBeam CSS generator retains the beam palettes and geometry separately from the interaction controller.

```twig
{% include '@MauticCore/Components/hold-to-confirm.html.twig' with {
    variant: 'danger',
    label: 'mautic.core.hold_to_confirm.label',
    confirmedLabel: 'mautic.core.hold_to_confirm.confirmed',
    duration: 1200,
    resetDelay: 0,
    size: 'md',
    disabled: false
} only %}
```

## API

- `variant`: `danger` (default), `neutral`, `beam`. The old `destructive` name remains an alias for `danger`.
- `size`: `sm`, `md`, `lg`, retaining 32/40/48 px heights and 20/24/30 px rings.
- `duration`: 1200 ms. `resetDelay`: 1500 ms; zero stays confirmed until `reset()`.
- `label`, `confirmedLabel`: translated and escaped. The default confirmation says **Confirmed**, not **Deleted**: completing a gesture does not prove the server deleted anything.
- `icon`: a Remix icon class, default `ri-delete-bin-line`; Twig `embed` can override the `icon` block. Runtime `update({icon})` accepts a DOM node or null to restore the default.
- `disabled`, `className`, `id`; beam-only `beamClassName`, `colorVariant` (`sunset`, `ocean`, `colorful`, `mono`), `theme` (`auto`, `light`, `dark`), `strength` (0.65) and `active` (true). The beam effect uses the original `md` preset.

```javascript
const element = document.querySelector('[data-component="hold-to-confirm"]');
const controller = window.MauticUI.HoldToConfirmButton.get(element);
controller.update({onConfirm: () => performAuthorizedAction()});
```

Alternatively listen to the bubbling `mautic:hold-to-confirm:confirm` event. Use either the callback or the event to execute the action, never both. `update()` changes options and `reset()` restores idle. AJAX-inserted controls initialize once; removal releases listeners, animations, timers and beam styles.

Hold the primary pointer, Space or Enter. Short clicks and right clicks do not confirm. Combined pointer/keyboard input does not restart progress or cancel the other input. Releasing the last input unwinds progress; blur, visibility loss and pointer cancellation cancel an incomplete hold. Completion fires once and consumes the physical holds. Reduced motion freezes beam effects and suppresses decorative movement without shortening the confirmation duration.

## Layout and feedback

Use the native button outline, including its inset focus treatment. On completion, text, icon, border, outline and inset focus ring share `--hold-to-confirm-success`, derived from the existing success and primary-text tokens. Mixing those tokens preserves the success hue while improving small-text contrast on light palettes. Reset restores the normal focus color; disabled buttons retain the native disabled treatment. No global theme token is changed.

Keep the live status as an `sr-only` sibling of the button, outside the global `.btn span` cascade. Apply component layout resets to named parts rather than every descendant span, including any custom icon markup.

## Adoption rules

The account page (`/s/account`) uses the danger variant for **Restart all dismissed tips**, with the refresh icon and small size. Its UserBundle handler runs the existing `Mautic.resetDismissedElements()` only after the confirmation event, preserving the local-storage reset, restored tips and flash feedback. Short clicks and cancelled holds do not reset tips; the control returns to idle for reuse.

This branch also supplies an [adoption audit](hold-to-confirm-danger-audit.md); it does not change existing delete handlers. Keep a clearly explained confirmation surface, affected item/count and a normal Cancel action. For users unable to sustain a press, retain an accessible confirmation alternative in the consuming flow; do not make this gesture the sole way to manage data.

Integrations must preserve authorization, CSRF, POST semantics, batch selection prechecks, lock/dependency checks, loading/error feedback and focus return. Pass the existing action through its established helper after confirmation; do not turn a destructive endpoint into a GET link. Use `resetDelay: 0` and disable while a request is pending. Only a successful server response may claim deletion. Do not stack a second confirmation dialog after the hold.

## Verification

From the registered main DDEV checkout, run the suite against the checked-out branch source:

```sh
ddev exec --dir /var/www/html/app/bundles/CoreBundle/Assets/components env MAUTIC_VENDOR_AUTOLOAD=/var/www/html/vendor/autoload.php npm test
```

Test setup in `Assets/components`: `npm ci`, then `npx playwright install --with-deps chromium`. CI uses the same Twig/Chromium tests and a small Composer install for Twig and the production PHP minifier. There is no production component build step.

Assets are generated from the branch in the registered main DDEV checkout, including its Bootstrap Sass dependency.

The 30-test suite covers input cooperation, cancellation, reset, explicit zero/false, escaped labels, disabled/removal lifecycle, sizes, alias/dynamic variant changes, accessible names, reduced motion, beam activation and execution after PHP minification. Regression tests reproduce inherited button-span widths, heights and margins, measure the 8 px gap and label/check alignment, verify the status remains outside the button, and cover success focus/hover/active/disabled states for all three variants.

Visual verification uses the authenticated local Account route (`/s/account`) with the installed Twig integration, real Mautic styles and generated assets, on desktop and a 390 px viewport. Short clicks and cancelled holds leave a dismissed test tip hidden; a full hold clears `dismissedElements`, restores the tip, shows the existing flash and confirms exactly once. The control then resets for reuse. Keyboard holds are also exercised. Browser storage and temporary handler wrappers are restored afterward; no deletion endpoint is involved.

A separate runtime matrix on that route checks all 45 combinations of five UI themes, three variants and three sizes with reduced motion enabled. Those checks measure the gap, label alignment, shared success/focus/border color and small-text contrast against the rendered success background; the lowest measured contrast is 4.84:1. These are runtime API checks on the Account control, not 45 separately installed integrations or a visual frame review of every combination.
