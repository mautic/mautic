(function () {
    const HOLD_DELAY = 400;
    const HOLD_INTERVAL = 80;
    const HOLD_FAST_INTERVAL = 40;
    const HOLD_FAST_AFTER = 10;
    const FLASH_DURATION = 400;
    const ROLL_DISTANCE = 10;
    const ROLL_IN = {duration: 200, easing: 'cubic-bezier(0.22, 1, 0.36, 1)'};
    const ROLL_OUT = {duration: 120, easing: 'cubic-bezier(0.4, 0, 1, 1)'};
    const ROLL_REPEAT = {duration: 90, easing: 'ease-out'};
    const SHAKE = [0, -4, 4, -2, 2, 0];
    const SHAKE_DURATION = 300;
    const state = new WeakMap();
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    function parts(root) {
        return {
            input: root.querySelector('.quantity-stepper__value'),
            field: root.querySelector('.quantity-stepper__field'),
            display: root.querySelector('.quantity-stepper__display'),
        };
    }

    function info(root) {
        if (!state.has(root)) {
            state.set(root, {timer: null, repeating: false, flash: null});
        }

        return state.get(root);
    }

    function bounds(input) {
        return {
            min: input.min === '' ? -Infinity : Number(input.min),
            max: input.max === '' ? Infinity : Number(input.max),
        };
    }

    function clamp(input, number) {
        const {min, max} = bounds(input);

        return Math.min(max, Math.max(min, number));
    }

    function stepBase(input) {
        const {min} = bounds(input);
        const initial = input.getAttribute('value');

        if (Number.isFinite(min)) {
            return min;
        }

        return initial !== null && initial.trim() !== '' && Number.isFinite(Number(initial)) ? Number(initial) : 0;
    }

    function snap(input, number, direction) {
        const {max} = bounds(input);
        const step = Number(input.step) || 1;
        const base = stepBase(input);
        const ratio = (clamp(input, number) - base) / step;
        const next = base + (direction < 0 ? Math.floor(ratio) : Math.ceil(ratio)) * step;

        return next > max ? next - step : next;
    }

    function format(template, value) {
        return template.replace('%value%', value);
    }

    function fit(input) {
        const field = input.closest('.quantity-stepper__field');
        const current = Number(field.style.getPropertyValue('--quantity-stepper-digits')) || 2;

        field.style.setProperty('--quantity-stepper-digits', Math.max(current, input.value.length));
    }

    function sync(root) {
        const {input} = parts(root);
        const {min, max} = bounds(input);
        const current = Number(input.value);

        fit(input);
        root.querySelectorAll('[data-quantity-step]').forEach((button) => {
            const blocked = Number(button.dataset.quantityStep) < 0 ? current <= min : current >= max;
            button.setAttribute('aria-disabled', blocked ? 'true' : 'false');
        });
    }

    function roll(root, direction, text) {
        const {display} = parts(root);
        const previous = display.querySelector('.quantity-stepper__digit:not(.quantity-stepper__digit--exit)');
        const entering = document.createElement('span');
        entering.className = 'quantity-stepper__digit';
        entering.textContent = text;

        display.querySelectorAll('.quantity-stepper__digit--exit').forEach((node) => node.remove());
        if (previous) {
            previous.classList.add('quantity-stepper__digit--exit');
        }
        display.appendChild(entering);

        const instant = reducedMotion.matches || root.classList.contains('is-limit') || !entering.animate;
        if (instant || direction === 0) {
            if (previous) {
                previous.remove();
            }

            return;
        }

        const repeating = info(root).repeating;
        const offset = direction * ROLL_DISTANCE;
        const enterTiming = Object.assign({fill: 'both'}, repeating ? ROLL_REPEAT : ROLL_IN);
        const exitTiming = Object.assign({fill: 'both'}, repeating ? ROLL_REPEAT : ROLL_OUT);

        entering.animate([{transform: `translateY(${offset}px)`, opacity: 0}, {transform: 'translateY(0)', opacity: 1}], enterTiming);
        if (previous) {
            previous.animate([{transform: 'translateY(0)', opacity: 1}, {transform: `translateY(${-offset}px)`, opacity: 0}], exitTiming).onfinish = () => previous.remove();
        }
    }

    function flashLimit(root) {
        const data = info(root);
        const {field} = parts(root);

        root.classList.add('is-limit');
        if (!reducedMotion.matches && field.animate) {
            field.animate(SHAKE.map((x) => ({transform: `translateX(${x}px)`})), {duration: SHAKE_DURATION, easing: 'ease-in-out'});
        }
        clearTimeout(data.flash);
        data.flash = setTimeout(() => root.classList.remove('is-limit'), FLASH_DURATION);
    }

    function announce(root, text) {
        root.querySelector('[data-quantity-announcement]').textContent = text;
    }

    function setValue(root, next, silentAnnouncement) {
        const {input} = parts(root);
        const current = Number(input.value) || 0;
        const direction = Math.sign(next - current);

        input.value = next;
        roll(root, direction, String(next));
        sync(root);
        input.dispatchEvent(new Event('input', {bubbles: true}));
        input.dispatchEvent(new Event('change', {bubbles: true}));
        if (!silentAnnouncement) {
            announce(root, String(next));
        }
    }

    function change(root, delta, announceLimit = true) {
        const {input} = parts(root);
        if (input.disabled || input.readOnly) {
            return false;
        }

        const current = Number(input.value) || 0;
        const next = snap(input, current + delta, delta);

        if (next === current) {
            flashLimit(root);
            if (announceLimit) {
                const {min, max} = bounds(input);
                announce(root, format(delta < 0 ? root.dataset.minMessage : root.dataset.maxMessage, delta < 0 ? min : max));
            }

            return false;
        }

        setValue(root, next);

        return true;
    }

    function stop(root) {
        const data = info(root);
        clearTimeout(data.timer);
        data.timer = null;
        data.repeating = false;
    }

    function start(root, direction) {
        const data = info(root);
        const step = Number(parts(root).input.step) || 1;
        let count = 0;
        const tick = () => {
            data.repeating = true;
            if (!change(root, direction * step)) {
                stop(root);

                return;
            }
            count++;
            data.timer = setTimeout(tick, count >= HOLD_FAST_AFTER ? HOLD_FAST_INTERVAL : HOLD_INTERVAL);
        };

        stop(root);
        change(root, direction * step);
        data.timer = setTimeout(tick, HOLD_DELAY);
    }

    function rootOf(target) {
        return target.closest && target.closest('[data-quantity-stepper]');
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

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-quantity-step]');
        const root = button && rootOf(button);
        if (root && event.detail === 0 && !button.disabled) {
            change(root, Number(button.dataset.quantityStep) * (Number(parts(root).input.step) || 1));
        }
    });

    ['pointerup', 'pointercancel', 'pointerleave'].forEach((type) => {
        document.addEventListener(type, (event) => {
            const root = rootOf(event.target);
            if (root) {
                stop(root);
            }
        }, true);
    });

    document.addEventListener('keydown', (event) => {
        const root = rootOf(event.target);
        if (!root) {
            return;
        }

        const button = event.target.closest('[data-quantity-step]');
        if (button) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                change(root, Number(button.dataset.quantityStep) * (Number(parts(root).input.step) || 1));
            }

            return;
        }

        const {input} = parts(root);
        if (input.disabled || input.readOnly) {
            return;
        }

        const step = Number(input.step) || 1;
        const {min, max} = bounds(input);
        const deltas = {ArrowUp: step, ArrowDown: -step, PageUp: step * 10, PageDown: -step * 10};

        if (event.key in deltas) {
            event.preventDefault();
            change(root, deltas[event.key]);
        } else if ((event.key === 'Home' && Number.isFinite(min)) || (event.key === 'End' && Number.isFinite(max))) {
            event.preventDefault();
            const target = event.key === 'Home' ? min : snap(input, max, -1);
            if (input.value === '' || target !== Number(input.value)) {
                setValue(root, target);
            }
        }
    });

    document.addEventListener('change', (event) => {
        const root = rootOf(event.target);
        const {input} = root ? parts(root) : {};
        if (!root || event.target !== input) {
            return;
        }

        if (input.value === '') {
            roll(root, 0, '');
            sync(root);

            return;
        }

        const typed = clamp(input, Math.round(Number(input.value) || 0));
        const shown = parts(root).display.querySelector('.quantity-stepper__digit:not(.quantity-stepper__digit--exit)');
        if (String(typed) !== input.value || !shown || input.value !== shown.textContent) {
            input.value = typed;
            roll(root, 0, String(typed));
            sync(root);
        }
    });

    document.addEventListener('input', (event) => {
        const root = rootOf(event.target);
        if (root && event.target === parts(root).input) {
            fit(event.target);
        }
    });

    function init(node) {
        if (node.nodeType !== 1) {
            return;
        }
        if (node.matches('[data-quantity-stepper]')) {
            sync(node);
        }
        node.querySelectorAll('[data-quantity-stepper]').forEach(sync);
    }

    document.addEventListener('DOMContentLoaded', () => {
        init(document.body);
        new MutationObserver((records) => records.forEach((record) => record.addedNodes.forEach(init)))
            .observe(document.body, {childList: true, subtree: true});
    });
})();
