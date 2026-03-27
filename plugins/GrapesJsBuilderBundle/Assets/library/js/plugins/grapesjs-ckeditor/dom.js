/**
 * Creates an HTML element with properties.
 *
 * @param {string} type
 * @param {HTMLElement} container
 * @param {Object} properties
 * @return {HTMLElement}
 */
export function createHtmlElem(type, container, properties) {
  const elem = document.createElement(type);
  setElementProperty(elem, properties);
  if (container) {
    container.appendChild(elem);
  }
  return elem;
}

/**
 * Sets properties on an element recursively.
 *
 * @param {Object} elem
 * @param {Object} properties
 */
export function setElementProperty(elem, properties) {
  if (!properties) {
    return;
  }

  for (const key in properties) {
    if (typeof properties[key] === 'object') {
      setElementProperty(elem[key], properties[key]);
    } else {
      elem[key] = properties[key];
    }
  }
}
