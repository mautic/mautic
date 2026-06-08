function isOpen(element) {
  return [...element.classList].find(className => className.match(/panel-visible/g));
}

/**
 * Checks if an open panel overlaps with the GrapesJS toolbar.
 *
 * @param {HTMLElement} element
 * @param {HTMLElement} gjsToolbar
 * @param {HTMLElement} frame
 * @returns {boolean}
 */
export function isOpenPanelOverlapGjsToolbar(element, gjsToolbar, frame) {
  if (isOpen(element) && overlap(element, gjsToolbar, frame)) {
    return true;
  }

  return !![...element.children].find(child => isOpenPanelOverlapGjsToolbar(child, gjsToolbar, frame));
}

/**
 * Checks for overlap between two elements/rects.
 *
 * @param {HTMLElement} element
 * @param {HTMLElement} gjsToolbar
 * @param {HTMLElement} frame
 * @returns {boolean}
 */
export function overlap(element, gjsToolbar, frame) {
  const elementBoundaryRect = element.getBoundingClientRect();
  const frameBoundaryRect = frame.getBoundingClientRect();
  const gjsToolbarBoundaryRect = gjsToolbar.getBoundingClientRect();
  const elementBoundaryRectScreenViewPort = {
    top: elementBoundaryRect.top + frameBoundaryRect.top,
    bottom: elementBoundaryRect.bottom + frameBoundaryRect.top,
    left: elementBoundaryRect.left + frameBoundaryRect.left,
    right: elementBoundaryRect.right + frameBoundaryRect.left,
  };

  return !(
    gjsToolbarBoundaryRect.left > elementBoundaryRectScreenViewPort.right ||
    gjsToolbarBoundaryRect.right < elementBoundaryRectScreenViewPort.left ||
    gjsToolbarBoundaryRect.top > elementBoundaryRectScreenViewPort.bottom ||
    gjsToolbarBoundaryRect.bottom < elementBoundaryRectScreenViewPort.top
  );
}
