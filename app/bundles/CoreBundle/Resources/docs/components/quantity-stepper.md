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
    disabled: false
} only %}
```

- `size`: `sm`, `md` (default) or `lg`.
- The value is a native `<input type="number">`: it submits with the form, exposes spinbutton semantics and keeps ArrowUp/ArrowDown. The +/- buttons are pointer helpers (`tabindex="-1"`).
- Hold a button to repeat (400 ms delay, 80 ms interval, 40 ms after 10 steps), clamped to `min`/`max`.
- Reaching a bound shakes and tints the value (disabled under `prefers-reduced-motion`) and announces through an `aria-live` region.
- Fires `input` and `change` on the input after every change.

Files: `Components/quantity-stepper.html.twig`, `Assets/js/3.quantity-stepper.js`, `Assets/css/app/scss/components/_quantity-stepper.scss`.
