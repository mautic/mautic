(function () {
    const HOLD_DELAY = 400;
    const HOLD_INTERVAL = 80;
    const HOLD_FAST_INTERVAL = 40;
    const HOLD_FAST_AFTER = 10;
    const FLASH_DURATION = 400;
    const state = new WeakMap();

    function bounds(input) {
        const min = input.min === '' ? -Infinity : Number(input.min);
        const max = input.max === '' ? Infinity : Number(input.max);

        return {min, max};
    }

    function clamp(input, number) {
        const {min, max} = bounds(input);

        return Math.min(max, Math.max(min, number));
    }

    function sync(root) {
        const input = root.querySelector('.quantity-stepper__value');
        const {min, max} = bounds(input);
        const current = Number(input.value);

        root.querySelectorAll('[data-quantity-step]').forEach((button) => {
            const blocked = Number(button.dataset.quantityStep) < 0 ? current <= min : current >= max;
            button.setAttribute('aria-disabled', blocked ? 'true' : 'false');
        });
    }

    function flashLimit(root) {
        const info = state.get(root) || {};
        root.classList.remove('is-limit');
        void root.offsetWidth;
        root.classList.add('is-limit');
        clearTimeout(info.flash);
        info.flash = setTimeout(() => root.classList.remove('is-limit'), FLASH_DURATION);
        state.set(root, info);
    }

    function announce(root, input, blocked) {
        const message = root.querySelector('[data-quantity-announcement]');
        message.textContent = blocked ? `${input.value} (${input.min}–${input.max})` : input.value;
    }

    function change(root, direction) {
        const input = root.querySelector('.quantity-stepper__value');
        const step = Number(input.step) || 1;
        const current = Number(input.value) || 0;
        const next = clamp(input, current + direction * step);
        const blocked = next === current;

        if (blocked) {
            flashLimit(root);
        } else {
            input.value = next;
            input.dispatchEvent(new Event('input', {bubbles: true}));
            input.dispatchEvent(new Event('change', {bubbles: true}));
        }

        announce(root, input, blocked);
        sync(root);

        return !blocked;
    }

    function stop(root) {
        const info = state.get(root);
        if (info) {
            clearTimeout(info.timer);
            info.timer = null;
        }
    }

    function start(root, direction) {
        const info = state.get(root) || {};
        let count = 0;
        const tick = () => {
            if (!change(root, direction)) {
                return;
            }
            count++;
            info.timer = setTimeout(tick, count >= HOLD_FAST_AFTER ? HOLD_FAST_INTERVAL : HOLD_INTERVAL);
        };

        state.set(root, info);
        change(root, direction);
        info.timer = setTimeout(tick, HOLD_DELAY);
    }

    function rootOf(target) {
        return target.closest('[data-quantity-stepper]');
    }

    document.addEventListener('pointerdown', (event) => {
        const button = event.target.closest('[data-quantity-step]');
        const root = button && rootOf(button);
        if (!root || button.disabled || event.button > 0) {
            return;
        }
        event.preventDefault();
        start(root, Number(button.dataset.quantityStep));
    });

    ['pointerup', 'pointercancel', 'pointerleave'].forEach((type) => {
        document.addEventListener(type, (event) => {
            const root = event.target.closest && rootOf(event.target);
            if (root) {
                stop(root);
            }
        }, true);
    });

    document.addEventListener('keydown', (event) => {
        const button = event.target.closest('[data-quantity-step]');
        if (button && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            change(rootOf(button), Number(button.dataset.quantityStep));
        }
    });

    document.addEventListener('change', (event) => {
        const root = event.target.closest && rootOf(event.target);
        if (root && event.target.matches('.quantity-stepper__value')) {
            event.target.value = clamp(event.target, Number(event.target.value) || 0);
            sync(root);
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-quantity-stepper]').forEach(sync);
    });
})();
