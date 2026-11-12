# Quantity stepper

Port of the Acervo `QuantityStepper` as a native Twig component: no React, no motion runtime.

```twig
{% include '@MauticCore/Components/quantity-stepper.html.twig' with {
    name: 'quantity',
    labelText: 'mautic.some.label',
    value: 1,
    min: 0,
    max: 99,
    step: 1,
    size: 'md',
    digits: null,
    disabled: false
} only %}
```

- `size`: `sm`, `md` (default) or `lg`.
- The value is a native `<input type="number">`: it submits with the form, exposes spinbutton semantics and keeps ArrowUp/ArrowDown. The +/- buttons are pointer helpers (`tabindex="-1"`).
- The width is reserved for the longest of `min`/`max` (or `digits`, to raise it), computed in Twig and set inline as `--quantity-stepper-digits`, so the first paint already has the final width, so the +/- buttons never move while the number changes.
- Hold a button to repeat (400 ms delay, 80 ms interval, 40 ms after 10 steps), clamped to `min`/`max`.
- The displayed number rolls (new value slides in from below when increasing, from above when decreasing, 200 ms ease-out with a 120 ms fade-out of the old one; 90 ms while holding). The native input is revealed while focused so values can be typed. ArrowUp/Down, PageUp/Down (x10), Home and End are supported.
- As a form field, set `'block_prefix' => 'quantity_stepper'` on a Symfony type (see `PointType` `delta`): the label, tooltip and errors stay in the form row; pass `min`, `max`, `step`, `size` through `attr`.
- The pill uses the field layer background (`--field`, hover `--field-hover`), like the input fields.
- Reaching a bound shakes and tints the value (disabled under `prefers-reduced-motion`) and announces through an `aria-live` region.
- Fires `input` and `change` on the input after every change.

Files: `Components/quantity-stepper.html.twig`, `Assets/js/3.quantity-stepper.js`, `Assets/css/app/scss/components/_quantity-stepper.scss`.
