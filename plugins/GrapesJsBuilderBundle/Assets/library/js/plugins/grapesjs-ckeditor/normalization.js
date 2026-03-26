/**
 * Content normalization helpers for GrapesJS CKEditor.
 */
export const normalizationMixin = {
  /**
   * Gets the inline text color from an element.
   *
   * @param {HTMLElement} element
   * @returns {Object|null}
   */
  getInlineTextColor(element) {
    if (!element || !element.style || typeof element.style.getPropertyValue !== 'function') {
      return null;
    }

    const value = element.style.getPropertyValue('color');
    if (!value) {
      return null;
    }

    const normalized = value.trim();
    if (!normalized) {
      return null;
    }

    const keyword = normalized.toLowerCase();
    if (['inherit', 'initial', 'unset', 'revert'].includes(keyword)) {
      return null;
    }

    return {
      value: normalized,
      priority: element.style.getPropertyPriority('color') || ''
    };
  },

  /**
   * Determines the effective link color for an anchor.
   * Checks direct style or child elements.
   *
   * @param {HTMLElement} anchor
   * @returns {Object|null}
   */
  determineLinkColor(anchor) {
    if (!anchor) {
      return null;
    }

    const direct = this.getInlineTextColor(anchor);
    if (direct && direct.value) {
      return direct;
    }

    let descriptor = null;
    let value = null;
    let multiple = false;

    anchor.querySelectorAll('[style]').forEach(element => {
      if (multiple) {
        return;
      }

      const candidate = this.getInlineTextColor(element);
      if (!candidate) {
        return;
      }

      if (value === null) {
        value = candidate.value;
        descriptor = candidate;
        return;
      }

      if (value !== candidate.value) {
        multiple = true;
        descriptor = null;
      }
    });

    return multiple ? null : descriptor;
  },

  /**
   * Normalizes link underline colors in HTML content.
   * Ensures underlines match the text color.
   *
   * @param {string} html
   * @returns {string}
   */
  normalizeLinkUnderlineColors(html) {
    if (typeof html !== 'string' || html.indexOf('<a') === -1) {
      return html;
    }

    const implementation = (this.frameDoc && this.frameDoc.implementation) || (typeof document !== 'undefined' ? document.implementation : null);
    if (!implementation || typeof implementation.createHTMLDocument !== 'function') {
      return html;
    }

    const workingDocument = implementation.createHTMLDocument('');
    workingDocument.body.innerHTML = html;

    const anchors = workingDocument.body.querySelectorAll('a');
    anchors.forEach(anchor => {
      const descriptor = this.determineLinkColor(anchor);
      if (!descriptor || !descriptor.value) {
        return;
      }

      anchor.style.setProperty('color', descriptor.value, descriptor.priority);
      anchor.style.setProperty('text-decoration-color', descriptor.value, descriptor.priority);
      anchor.style.setProperty('border-bottom-color', descriptor.value, descriptor.priority);
    });

    return workingDocument.body.innerHTML;
  },

  /**
   * Normalizes indentation styles in HTML content.
   *
   * @param {string} html
   * @returns {string}
   */
  normalizeIndentationStyles(html) {
    if (
      typeof html !== 'string'
      || (
        html.indexOf('margin-left') === -1
        && html.indexOf('padding-left') === -1
        && html.indexOf('margin-right') === -1
        && html.indexOf('padding-right') === -1
      )
    ) {
      return html;
    }

    const implementation = (this.frameDoc && this.frameDoc.implementation) || (typeof document !== 'undefined' ? document.implementation : null);
    if (!implementation || typeof implementation.createHTMLDocument !== 'function') {
      return html;
    }

    const workingDocument = implementation.createHTMLDocument('');
    workingDocument.body.innerHTML = html;

    this.applyIndentationNormalization(workingDocument.body);

    return workingDocument.body.innerHTML;
  },

  /**
   * Removes inline styles commonly added by Word from text-level blocks.
   *
   * @param {string} html
   * @returns {string}
   */
  normalizeWordInlineStyles(html) {
    if (typeof html !== 'string' || html.indexOf('style=') === -1) {
      return html;
    }

    const implementation = (this.frameDoc && this.frameDoc.implementation) || (typeof document !== 'undefined' ? document.implementation : null);
    if (!implementation || typeof implementation.createHTMLDocument !== 'function') {
      return html;
    }

    const workingDocument = implementation.createHTMLDocument('');
    workingDocument.body.innerHTML = html;
    this.applyWordInlineStyleNormalization(workingDocument.body);

    return workingDocument.body.innerHTML;
  },

  /**
   * Normalizes list marker styles in HTML content.
   *
   * @param {string} html
   * @returns {string}
   */
  normalizeListMarkerStyles(html) {
    if (typeof html !== 'string' || html.indexOf('<li') === -1) {
      return html;
    }

    const implementation = (this.frameDoc && this.frameDoc.implementation) || (typeof document !== 'undefined' ? document.implementation : null);
    if (!implementation || typeof implementation.createHTMLDocument !== 'function') {
      return html;
    }

    const workingDocument = implementation.createHTMLDocument('');
    workingDocument.body.innerHTML = html;

    this.applyListMarkerNormalization(workingDocument.body);

    return workingDocument.body.innerHTML;
  },

  /**
   * Getter for managed set of link color elements.
   *
   * @returns {WeakSet}
   */
  get managedLinkColorElements() {
    if (!this._managedLinkColorElements) {
      this._managedLinkColorElements = new WeakSet();
    }

    return this._managedLinkColorElements;
  },

  /**
   * Applies an inline style to an element if it's not already set.
   *
   * @param {HTMLElement} element
   * @param {string} property
   * @param {string} value
   * @param {string} priority
   */
  applyInlineStyle(element, property, value, priority) {
    const currentValue = element.style.getPropertyValue(property);
    const currentPriority = element.style.getPropertyPriority(property) || '';
    const normalizedPriority = priority || '';
    if (currentValue === value && currentPriority === normalizedPriority) {
      return;
    }

    element.style.setProperty(property, value, priority);
  },

  /**
   * Removes an inline style property from an element.
   *
   * @param {HTMLElement} element
   * @param {string} property
   */
  clearInlineStyle(element, property) {
    if (!element.style) {
      return;
    }

    element.style.removeProperty(property);
  },

  /**
   * Removes the style attribute if it's empty.
   *
   * @param {HTMLElement} element
   */
  tidyStyleAttribute(element) {
    if (!element || !element.getAttribute) {
      return;
    }

    const styleAttr = element.getAttribute('style');
    if (styleAttr && styleAttr.trim()) {
      return;
    }

    element.removeAttribute('style');
  },

  /**
   * Removes color-related styles from descendant nodes of an anchor.
   *
   * @param {HTMLElement} anchor
   */
  stripDescendantLinkColors(anchor) {
    anchor.querySelectorAll('[style]').forEach(node => {
      this.clearInlineStyle(node, 'color');
      this.clearInlineStyle(node, 'text-decoration-color');
      this.clearInlineStyle(node, 'border-bottom-color');
      this.tidyStyleAttribute(node);
    });
  },

  /**
   * Applies normalization for link underlines to a DOM root.
   *
   * @param {HTMLElement} root
   */
  applyLinkUnderlineNormalization(root) {
    if (!root || typeof root.querySelectorAll !== 'function') {
      return;
    }

    const managed = this.managedLinkColorElements;
    if (!managed || typeof managed.add !== 'function' || typeof managed.has !== 'function') {
      return;
    }
    const anchors = root.querySelectorAll('a');
    anchors.forEach(anchor => {
      const descriptor = this.determineLinkColor(anchor);
      if (descriptor && descriptor.value) {
        const { value, priority } = descriptor;
        this.applyInlineStyle(anchor, 'color', value, priority);
        this.applyInlineStyle(anchor, 'text-decoration-color', value, priority);
        this.applyInlineStyle(anchor, 'border-bottom-color', value, priority);
        managed.add(anchor);
        return;
      }

      if (!managed.has(anchor)) {
        return;
      }

      this.clearInlineStyle(anchor, 'color');
      this.clearInlineStyle(anchor, 'text-decoration-color');
      this.clearInlineStyle(anchor, 'border-bottom-color');
      this.tidyStyleAttribute(anchor);
      managed.delete(anchor);
    });
  },

  /**
   * Applies normalization for indentation to a DOM root.
   * Moves margins from paragraphs to list items.
   *
   * @param {HTMLElement} root
   */
  applyIndentationNormalization(root) {
    if (!root || typeof root.querySelectorAll !== 'function') {
      return;
    }

    const contentPolicy = this.contentPolicy || {};
    const mapRightIndentToHanging = contentPolicy.mapRightIndentToHanging !== false;

    const properties = ['margin-left', 'padding-left', 'margin-right', 'padding-right', 'margin-inline-start', 'margin-inline-end', 'padding-inline-start', 'padding-inline-end'];

    // Move margins from blocks inside list items to the list item itself
    // This prevents the "gap" between bullet and text while preserving indentation
    root.querySelectorAll('li p, li div').forEach(element => {
      const li = element.closest('li');
      if (!li) return;

      properties.forEach(property => {
        const value = element.style.getPropertyValue(property);
        if (value) {
          const priority = element.style.getPropertyPriority(property);
          // Only move if the li doesn't already have this property set
          if (!li.style.getPropertyValue(property)) {
            this.applyInlineStyle(li, property, value, priority);
          }
          element.style.removeProperty(property);
        }
      });
      this.tidyStyleAttribute(element);
    });

    root.querySelectorAll('[style]').forEach(element => {
      if (mapRightIndentToHanging) {
        this.convertRightIndentToHanging(element);
      }

      properties.forEach(property => {
        const value = element.style.getPropertyValue(property);
        if (value && element.style.getPropertyPriority(property) !== 'important') {
          this.applyInlineStyle(element, property, value, 'important');
        }
      });
    });
  },

  /**
   * Converts right indentation styles into hanging indentation.
   *
   * @param {HTMLElement} element
   */
  convertRightIndentToHanging(element) {
    if (!element || !element.style) {
      return;
    }

    const hasHanging = !!element.style.getPropertyValue('text-indent');
    if (hasHanging) {
      return;
    }

    const marginRight = element.style.getPropertyValue('margin-right');
    const paddingRight = element.style.getPropertyValue('padding-right');
    const rightIndent = marginRight || paddingRight;
    if (!rightIndent) {
      return;
    }

    const marginRightPriority = element.style.getPropertyPriority('margin-right');
    const paddingRightPriority = element.style.getPropertyPriority('padding-right');
    const priority = marginRightPriority || paddingRightPriority || 'important';

    if (!element.style.getPropertyValue('margin-left')) {
      this.applyInlineStyle(element, 'margin-left', rightIndent, priority);
    }

    this.applyInlineStyle(element, 'text-indent', `-${rightIndent}`, priority);
    element.style.removeProperty('margin-right');
    element.style.removeProperty('padding-right');
  },

  /**
   * Applies Word inline-style cleanup to configured text tags.
   *
   * @param {HTMLElement} root
   */
  applyWordInlineStyleNormalization(root) {
    if (!root || typeof root.querySelectorAll !== 'function') {
      return;
    }

    const contentPolicy = this.contentPolicy || {};
    if (contentPolicy.stripWordInlineStyles === false) {
      return;
    }

    root.querySelectorAll('p[style], span[style], h1[style], h2[style], h3[style], h4[style], h5[style], h6[style]').forEach(element => {
      element.removeAttribute('style');
    });
  },

  /**
   * Applies normalization for list markers to a DOM root.
   * Ensures markers inherit styles from content.
   *
   * @param {HTMLElement} root
   */
  applyListMarkerNormalization(root) {
    if (!root || typeof root.querySelectorAll !== 'function') {
      return;
    }

    const listItems = root.querySelectorAll('li');
    listItems.forEach(li => {
      // Find the first span with styles that belongs directly to this li (not to a nested li)
      const span = Array.from(li.querySelectorAll('span[style]')).find(s => s.closest('li') === li);
      const properties = ['color', 'font-size', 'font-family', 'font-weight'];

      if (span) {
        properties.forEach(property => {
          const value = span.style.getPropertyValue(property);
          const priority = span.style.getPropertyPriority(property);
          if (value) {
            this.applyInlineStyle(li, property, value, priority);
          } else {
            this.clearInlineStyle(li, property);
          }
        });
      } else {
        properties.forEach(property => this.clearInlineStyle(li, property));
      }

      this.tidyStyleAttribute(li);
    });
  }
};
