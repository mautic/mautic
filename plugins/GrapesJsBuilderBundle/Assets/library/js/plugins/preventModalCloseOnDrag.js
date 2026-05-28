/**
 * Prevents a GrapesJS modal from closing when the user drags a text selection
 * from inside the dialog and releases the mouse on the backdrop.
 *
 * Root cause: the browser fires a `click` event on the backdrop element at
 * the point of `mouseup`, which GrapesJS interprets as "click outside → close".
 * By tracking where `mousedown` originated we can distinguish a real backdrop
 * click (close) from a drag-release (keep open).
 *
 * @param {Element} dialog    The `.gjs-mdl-dialog` element (inner modal box)
 * @param {Element} container The `.gjs-mdl-container` element (full-screen backdrop)
 * @returns {Function} Cleanup function — call it when the modal closes
 */
export function preventModalCloseOnDrag(dialog, container) {
    if (!dialog || !container) return () => {};

    let mousedownInsideDialog = false;

    const onMouseDown = (e) => {
        mousedownInsideDialog = dialog.contains(e.target);
    };

    const onContainerClick = (e) => {
        const isDragRelease = mousedownInsideDialog && !dialog.contains(e.target);
        mousedownInsideDialog = false;

        if (isDragRelease) {
            e.stopImmediatePropagation();
            e.preventDefault();
        }
    };

    document.addEventListener('mousedown', onMouseDown, true);
    container.addEventListener('click', onContainerClick, true);

    return () => {
        document.removeEventListener('mousedown', onMouseDown, true);
        container.removeEventListener('click', onContainerClick, true);
    };
}
