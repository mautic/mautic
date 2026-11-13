/**
 * Code block: keyboard access to horizontal scroll and animated expand/collapse.
 */
const scrollObserver = new ResizeObserver((entries) => entries.forEach(({target}) => {
    if (target.scrollWidth > target.clientWidth) target.setAttribute('tabindex', '0');
    else target.removeAttribute('tabindex');
}));

const observeCodeBlocks = (root) => root.querySelectorAll('.code-block__body').forEach((body) => scrollObserver.observe(body));

new MutationObserver((mutations) => mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
    if (node.nodeType === Node.ELEMENT_NODE) observeCodeBlocks(node);
}))).observe(document.documentElement, {childList: true, subtree: true});
observeCodeBlocks(document);

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
    body.animate({height: [`${from}px`, `${to}px`]}, {duration: duration * 2, easing: 'cubic-bezier(0.23, 1, 0.32, 1)'});
});
