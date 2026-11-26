/**
 * Update the value, width and helper text of a progress bar
 *
 * @param container Selector or element wrapping the component
 * @param value     Current value
 * @param percent   Current percentage (0-100)
 * @param max       Maximum value, used for the helper text
 */
Mautic.updateProgressBar = function (container, value, percent, max) {
    const component = mQuery(container).find('.progress-component').addBack('.progress-component');
    const track = component.find('[role="progressbar"]');

    if (component.hasClass('progress-component--indeterminate')) {
        component.removeClass('progress-component--indeterminate');
    }

    track.attr({'aria-valuemin': 0, 'aria-valuemax': max, 'aria-valuenow': value});
    track.find('.progress-bar').css('width', percent + '%');
    component.find('.progress-component__helper-text').text(
        Mautic.translate('mautic.core.progress_bar.count', {current: value, total: max})
    );
};

/**
 * Update the arc and value text of a gauge
 *
 * @param container Selector or element wrapping the component
 * @param value     Percentage (0-100), or the amount when total is given
 * @param total     Optional total; the arc shows value/total
 */
Mautic.updateGauge = function (container, value, total) {
    const gauge = mQuery(container).find('.gauge').addBack('.gauge');
    const hasTotal = total > 0;
    const raw = hasTotal ? (parseFloat(value) || 0) / total * 100 : parseFloat(value) || 0;
    const percent = Math.round(Math.min(Math.max(raw, 0), 100) * 10) / 10;
    const chart = gauge.find('[role="meter"], [role="progressbar"]');
    const unit = gauge.attr('data-unit') || '';
    const number = hasTotal ? value : percent;
    const spoken = hasTotal ? Mautic.translate('mautic.core.gauge.of', {value: value, total: total}) : percent + unit;
    const text = mQuery('<span class="gauge__number"></span>').text(number);

    gauge.removeClass('gauge--loading');
    chart.removeAttr('aria-busy').attr({role: 'meter', 'aria-valuenow': percent, 'aria-valuetext': spoken});
    gauge.find('.gauge__arc').attr('stroke-dasharray', percent + ' 100');
    gauge.find('.gauge__value').empty().append(text)
        .append(hasTotal ? mQuery('<span class="gauge__total"></span>').text('/' + total) : (unit ? mQuery('<span class="gauge__unit"></span>').text(unit) : ''));
};

/**
 * Draw and keep in sync the tick-based field sliders
 */
Mautic.initFieldSliders = function () {
    const padding = 12;
    const baseLength = 20;
    const peakLength = 32;
    const thumbLength = 40;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const state = new WeakMap();

    function numeric(value, fallback) {
        const number = parseFloat(value);

        return Number.isFinite(number) ? number : fallback;
    }

    function target(slider) {
        const {input, ticks} = slider;
        const min = numeric(input.min, 0);
        const range = numeric(input.max, 100) - min;

        return range > 0 ? Math.min(Math.max((numeric(input.value, min) - min) / range, 0), 1) * (ticks - 1) : 0;
    }

    function draw(slider) {
        const {canvas, ticks, lean, reach} = slider;
        const ratio = window.devicePixelRatio || 1;
        const width = canvas.clientWidth;
        const height = canvas.clientHeight;
        if (!width || !height) {
            return;
        }

        if (canvas.width !== Math.round(width * ratio) || canvas.height !== Math.round(height * ratio)) {
            canvas.width = Math.round(width * ratio);
            canvas.height = Math.round(height * ratio);
        }

        const style = getComputedStyle(canvas);
        const ctx = canvas.getContext('2d');
        const gap = (width - padding * 2) / (ticks - 1);
        const middle = height / 2;
        const rtl = style.direction === 'rtl';
        const position = rtl ? ticks - 1 - slider.shown : slider.shown;

        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.clearRect(0, 0, width, height);
        ctx.lineCap = 'round';
        ctx.lineWidth = 1.5;

        for (let i = 0; i < ticks; i++) {
            const distance = i - position;
            const closeness = Math.max(0, 1 - Math.abs(distance) / reach);
            const eased = closeness * closeness * (3 - 2 * closeness);
            const length = baseLength + (peakLength - baseLength) * eased;
            const angle = -Math.sign(distance) * lean * eased * Math.PI / 180;
            const active = rtl ? i >= position : i <= position;
            const activeDistance = rtl ? ticks - 1 - i : i;
            const activeRange = rtl ? ticks - 1 - position : position;
            const activeProgress = activeRange ? activeDistance / activeRange : 1;

            ctx.save();
            ctx.translate(padding + i * gap, middle);
            ctx.rotate(angle);
            ctx.globalAlpha = active ? 0.3 + 0.7 * activeProgress : 1;
            ctx.strokeStyle = active ? style.accentColor : style.color;
            ctx.beginPath();
            ctx.moveTo(0, -length / 2);
            ctx.lineTo(0, length / 2);
            ctx.stroke();
            ctx.restore();
        }

        ctx.strokeStyle = style.accentColor;
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(padding + position * gap, middle - thumbLength / 2);
        ctx.lineTo(padding + position * gap, middle + thumbLength / 2);
        ctx.stroke();
    }

    function animate(slider) {
        const distance = slider.goal - slider.shown;

        if (reducedMotion.matches || Math.abs(distance) < 0.005) {
            slider.shown = slider.goal;
            slider.frame = null;
        } else {
            slider.shown += distance * 0.3;
            slider.frame = requestAnimationFrame(() => animate(slider));
        }

        draw(slider);
    }

    function update(slider) {
        const {input} = slider;
        slider.goal = target(slider);
        slider.number.textContent = input.value;
        input.setAttribute('aria-valuetext', input.value + (input.dataset.fieldSliderSuffix || ''));

        if (!slider.frame) {
            animate(slider);
        }
    }

    function setup(root) {
        if (state.has(root)) {
            return;
        }

        const slider = {
            root,
            input: root.querySelector('.field-slider__input'),
            canvas: root.querySelector('.field-slider__canvas'),
            number: root.querySelector('[data-field-slider-number]'),
            ticks: Math.max(2, Math.round(numeric(root.dataset.ticks, 41))),
            lean: numeric(root.dataset.lean, 30),
            reach: Math.max(1, numeric(root.dataset.reach, 4)),
            frame: null,
        };

        if (!slider.input || !slider.canvas || !slider.number) {
            return;
        }

        state.set(root, slider);
        slider.goal = slider.shown = target(slider);
        slider.input.addEventListener('input', () => update(slider));
        slider.input.addEventListener('change', () => update(slider));
        new ResizeObserver(() => draw(slider)).observe(slider.canvas);
        update(slider);
    }

    function init(node) {
        if (node.nodeType !== 1) {
            return;
        }

        if (node.matches('[data-field-slider]')) {
            setup(node);
        }

        node.querySelectorAll('[data-field-slider]').forEach(setup);
    }

    document.addEventListener('DOMContentLoaded', () => {
        init(document.body);
        new MutationObserver((records) => records.forEach((record) => record.addedNodes.forEach(init)))
            .observe(document.body, {childList: true, subtree: true});
    });
};

Mautic.initFieldSliders();
