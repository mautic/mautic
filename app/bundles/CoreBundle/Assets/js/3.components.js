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
 * Position the sliding indicator of segmented controls under the checked option
 *
 * @param container Selector or element wrapping the controls
 */
Mautic.initSegmentedControl = function (container) {
    mQuery(container).find('.segmented-control').addBack('.segmented-control').each(function () {
        const control = this;
        const update = () => {
            const checked = control.querySelector('.btn-check:checked + .btn');
            if (!checked || !checked.offsetWidth) {
                return;
            }
            control.style.setProperty('--segmented-control-x', checked.offsetLeft + 'px');
            control.style.setProperty('--segmented-control-width', checked.offsetWidth + 'px');
            control.classList.add('is-initialized');
        };

        if (control.dataset.segmentedControlReady) {
            update();
            return;
        }
        control.dataset.segmentedControlReady = 'true';

        update();
        control.addEventListener('change', update);

        if (control.classList.contains('segmented-control--draggable')) {
            Mautic.initSegmentedControlDrag(control);
        }
        if (window.ResizeObserver) {
            new ResizeObserver(update).observe(control);
        }
    });
};

/**
 * Let the user press the selected option and drag across the others
 *
 * @param control Segmented control element
 */
Mautic.initSegmentedControlDrag = function (control) {
    let pointerId = null;
    let drag = null;

    const move = (clientX) => {
        const x = clientX - drag.origin;
        let target = drag.target;
        let distance = Infinity;
        for (const segment of drag.segments) {
            if (segment.input.disabled) {
                continue;
            }
            const gap = Math.max(segment.left - x, x - segment.left - segment.width, 0);
            if (gap < distance) {
                target = segment;
                distance = gap;
            }
        }
        if (target === drag.target) {
            return;
        }
        delete drag.target.label.dataset.segmentedControlPreview;
        target.label.dataset.segmentedControlPreview = 'true';
        drag.target = target;
        control.style.setProperty('--segmented-control-drag-x', `${target.left}px`);
        control.style.setProperty('--segmented-control-drag-width', `${target.width}px`);
    };

    control.addEventListener('pointerdown', (event) => {
        const checked = control.querySelector('.btn-check:checked + .btn');
        if (pointerId !== null || event.button !== 0 || !checked || !checked.contains(event.target)) {
            return;
        }
        const rect = checked.getBoundingClientRect();
        const segments = Array.from(control.querySelectorAll('.btn-check + .btn'), (label) => ({
            label,
            input: label.previousElementSibling,
            left: label.offsetLeft,
            width: label.offsetWidth,
        }));
        control.setPointerCapture(event.pointerId);
        pointerId = event.pointerId;
        drag = {
            segments,
            target: segments.find((segment) => segment.input.checked),
            origin: rect.left - checked.offsetLeft,
        };
        checked.dataset.segmentedControlPreview = 'true';
        control.dataset.segmentedControlDragging = 'true';
    });

    control.addEventListener('pointermove', (event) => {
        if (event.pointerId === pointerId) {
            move(event.clientX);
        }
    });

    const stop = (event) => {
        if (event.pointerId === pointerId) {
            const {input, label} = drag.target;
            delete label.dataset.segmentedControlPreview;
            pointerId = null;
            drag = null;
            if (event.type === 'pointerup' && !input.checked && !input.disabled) {
                input.checked = true;
                input.dispatchEvent(new Event('change', {bubbles: true}));
            }
            delete control.dataset.segmentedControlDragging;
            control.style.removeProperty('--segmented-control-drag-x');
            control.style.removeProperty('--segmented-control-drag-width');
        }
    };
    control.addEventListener('pointerup', stop);
    control.addEventListener('pointercancel', stop);
    control.addEventListener('lostpointercapture', stop);
};
