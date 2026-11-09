import { preventModalCloseOnDrag } from '../preventModalCloseOnDrag';

/**
 * Helpers to simulate the browser event sequence that causes the bug:
 *   mousedown (inside dialog) → drag → mouseup (outside) → click (on container)
 */
function fireMousedown(target) {
    target.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true }));
}

function fireClick(target) {
    const event = new MouseEvent('click', { bubbles: true, cancelable: true });
    target.dispatchEvent(event);
    return event;
}

/**
 * Build a minimal DOM that mirrors the GrapesJS modal structure:
 *   .gjs-mdl-container
 *     └── .gjs-mdl-dialog
 *           └── (editor content)
 */
function buildModalDOM() {
    const container = document.createElement('div');
    container.className = 'gjs-mdl-container';

    const dialog = document.createElement('div');
    dialog.className = 'gjs-mdl-dialog';

    const content = document.createElement('div');
    content.className = 'gjs-mdl-content';
    content.textContent = 'Email body text';

    dialog.appendChild(content);
    container.appendChild(dialog);
    document.body.appendChild(container);

    return { container, dialog, content };
}

describe('preventModalCloseOnDrag', () => {
    let container, dialog, content, cleanup;

    beforeEach(() => {
        ({ container, dialog, content } = buildModalDOM());
        cleanup = preventModalCloseOnDrag(dialog, container);
    });

    afterEach(() => {
        cleanup();
        document.body.textContent = '';
    });

    describe('drag from inside dialog to outside (text-selection scenario)', () => {
        it('stops click propagation so the modal does not close', () => {
            const clickHandler = jest.fn();
            container.addEventListener('click', clickHandler);

            // Simulate: mousedown inside the dialog content, then click fires on the container
            fireMousedown(content);
            const clickEvent = fireClick(container);

            // The click on the backdrop should have been stopped
            expect(clickHandler).not.toHaveBeenCalled();
            expect(clickEvent.defaultPrevented).toBe(true);
        });
    });

    describe('genuine backdrop click (mousedown AND click both on container)', () => {
        it('does not stop propagation so the modal closes normally', () => {
            const clickHandler = jest.fn();
            container.addEventListener('click', clickHandler);

            // Simulate: user clicks directly on the backdrop (no drag from inside)
            fireMousedown(container);
            fireClick(container);

            expect(clickHandler).toHaveBeenCalledTimes(1);
        });
    });

    describe('click inside the dialog itself', () => {
        it('does not interfere with normal in-dialog interactions', () => {
            const clickHandler = jest.fn();
            dialog.addEventListener('click', clickHandler);

            fireMousedown(content);
            fireClick(content);

            expect(clickHandler).toHaveBeenCalledTimes(1);
        });
    });

    describe('consecutive interactions', () => {
        it('allows backdrop click to close after a previous drag-release was blocked', () => {
            const clickHandler = jest.fn();
            container.addEventListener('click', clickHandler);

            // First: drag from inside to outside (should be blocked)
            fireMousedown(content);
            fireClick(container);
            expect(clickHandler).not.toHaveBeenCalled();

            // Second: genuine backdrop click (should be allowed)
            fireMousedown(container);
            fireClick(container);
            expect(clickHandler).toHaveBeenCalledTimes(1);
        });

        it('resets state correctly for multiple drag operations', () => {
            const clickHandler = jest.fn();
            container.addEventListener('click', clickHandler);

            // Two consecutive drag-releases should both be blocked
            fireMousedown(content);
            fireClick(container);
            expect(clickHandler).not.toHaveBeenCalled();

            fireMousedown(content);
            fireClick(container);
            expect(clickHandler).not.toHaveBeenCalled();
        });
    });

    describe('cleanup', () => {
        it('removes event listeners so backdrop clicks pass through after cleanup', () => {
            const clickHandler = jest.fn();
            container.addEventListener('click', clickHandler);

            cleanup(); // manually trigger cleanup

            // After cleanup, a drag-release should no longer be blocked
            fireMousedown(content);
            fireClick(container);

            expect(clickHandler).toHaveBeenCalledTimes(1);
        });
    });

    describe('missing DOM elements', () => {
        it('returns a no-op cleanup function when dialog is null', () => {
            const noop = preventModalCloseOnDrag(null, container);
            expect(() => noop()).not.toThrow();
        });

        it('returns a no-op cleanup function when container is null', () => {
            const noop = preventModalCloseOnDrag(dialog, null);
            expect(() => noop()).not.toThrow();
        });
    });
});
