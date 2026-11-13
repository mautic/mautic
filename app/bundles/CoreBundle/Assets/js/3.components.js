/**
 * Code block: expand/collapse of long code-block lines.
 */
document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-code-block-toggle]');
    if (!toggle) return;

    const block = toggle.closest('[data-code-block]');
    const expanded = toggle.getAttribute('aria-expanded') !== 'true';
    block.querySelectorAll('[data-code-block-extra]').forEach((line) => {
        line.hidden = !expanded;
    });
    toggle.setAttribute('aria-expanded', String(expanded));
    toggle.querySelector('[data-code-block-label]').textContent = expanded ? toggle.dataset.less : toggle.dataset.more;
});
