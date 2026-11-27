/**
 * Code block: keyboard access to horizontal scroll and animated expand/collapse.
 */
const scrollObserver = 'ResizeObserver' in window ? new ResizeObserver((entries) => entries.forEach(({target}) => {
    if (target.scrollWidth > target.clientWidth) target.setAttribute('tabindex', '0');
    else target.removeAttribute('tabindex');
})) : null;

const observedBodies = new Set();

const observeCodeBlocks = (root) => {
    if (!scrollObserver) return;

    observedBodies.forEach((body) => {
        if (body.isConnected) return;
        scrollObserver.unobserve(body);
        observedBodies.delete(body);
    });
    root.querySelectorAll('.code-block__body').forEach((body) => {
        observedBodies.add(body);
        scrollObserver.observe(body);
    });
};

observeCodeBlocks(document);
mQuery(document).on('mautic:onPageLoad:before', (event, container) => observeCodeBlocks(document.querySelector(container) || document));

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-code-block-toggle]');
    if (!toggle) return;

    const block = toggle.closest('[data-code-block]');
    const body = block.querySelector('.code-block__body');
    const expanded = toggle.getAttribute('aria-expanded') !== 'true';
    const from = body.getBoundingClientRect().height;

    body.getAnimations().forEach((animation) => animation.cancel());
    block.querySelectorAll('[data-code-block-extra]').forEach((line) => {
        line.hidden = !expanded;
    });
    toggle.setAttribute('aria-expanded', String(expanded));
    toggle.querySelector('[data-code-block-label]').textContent = expanded ? toggle.dataset.less : toggle.dataset.more;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const to = body.getBoundingClientRect().height;
    if (from === to) return;

    const duration = parseFloat(getComputedStyle(body).getPropertyValue('--duration-productive')) || 0;
    body.style.overflow = 'hidden';
    const animation = body.animate({height: [`${from}px`, `${to}px`]}, {duration: duration * 2, easing: 'cubic-bezier(0.23, 1, 0.32, 1)'});
    const release = () => {
        if (!body.getAnimations().length) body.style.removeProperty('overflow');
    };
    animation.onfinish = animation.oncancel = release;
});

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
