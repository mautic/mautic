/**
 * Editor lifecycle helpers for GrapesJS CKEditor.
 */
import { createHtmlElem } from './dom';
import { injectDataStorage, injectEditorInstant, setElementProperty } from './iframe';
import { isOpenPanelOverlapGjsToolbar } from './overlap';

export const editorLifecycleMixin = {
  /**
   * Checks if the target element should use inline editor.
   *
   * @param {HTMLElement} target
   * @returns {boolean}
   */
  isInline(target) {
    return this.inline.includes(target.tagName.toLowerCase());
  },

  /**
   * Compiles options for the editor instance based on the target element.
   *
   * @param {HTMLElement} target
   * @returns {Object}
   */
  compileEditorOptions(target) {
    let options = this.isInline(target) ? this.inlineOptions : this.options;
    const compiledOptions = {
      ...(options ? options : {}),
      licenseKey: this.licenseKey
    };

    const fontFamilyConfig = this.mergeFontFamilyOptions(compiledOptions.fontFamily);
    if (fontFamilyConfig) {
      compiledOptions.fontFamily = fontFamilyConfig;
    }

    const headingConfig = this.mergeHeadingOptions(compiledOptions.heading);
    const headingConfigWithTargetClasses = this.extendHeadingOptionsFromTarget(headingConfig, target);
    if (headingConfigWithTargetClasses) {
      compiledOptions.heading = headingConfigWithTargetClasses;
    }

    const styleConfig = this.mergeStyleDefinitions(compiledOptions.style);
    if (styleConfig) {
      compiledOptions.style = styleConfig;
    }

    return compiledOptions;
  },

  extendHeadingOptionsFromTarget(headingConfig, target) {
    const variants = this.collectHeadingClassVariants(target);
    if (!variants.length) {
      return headingConfig;
    }

    const resolvedHeadingConfig = headingConfig && typeof headingConfig === 'object' ? { ...headingConfig } : {};
    const options = Array.isArray(resolvedHeadingConfig.options) ? [...resolvedHeadingConfig.options] : [];

    const knownViews = new Set();
    const knownModels = new Set();
    options.forEach(option => {
      const view = option?.view;
      const viewName = typeof view === 'string' ? view.toLowerCase() : (typeof view?.name === 'string' ? view.name.toLowerCase() : '');
      const viewClasses = Array.isArray(view?.classes)
        ? [...view.classes]
        : (typeof view?.classes === 'string' ? view.classes.split(/\s+/) : []);
      const normalizedClasses = viewClasses
        .map(item => `${item || ''}`.trim())
        .filter(Boolean)
        .sort()
        .join(' ');

      if (viewName) {
        knownViews.add(`${viewName}|${normalizedClasses}`);
      }

      if (typeof option?.model === 'string' && option.model.trim()) {
        knownModels.add(option.model.trim().toLowerCase());
      }
    });

    variants.forEach((variant, index) => {
      const viewKey = `${variant.name}|${variant.classes.join(' ')}`;
      if (knownViews.has(viewKey)) {
        return;
      }

      const model = this.generateHeadingVariantModel(variant, knownModels, index);
      knownModels.add(model.toLowerCase());
      knownViews.add(viewKey);

      options.push({
        model,
        title: `${variant.name.toUpperCase()} (${variant.classes.join(' ')})`,
        class: `ck-heading_${variant.name}_${model.replace(/[^a-z0-9_-]/gi, '_').toLowerCase()}`,
        view: {
          name: variant.name,
          classes: variant.classes
        },
        converterPriority: 'high'
      });
    });

    resolvedHeadingConfig.options = options;

    return resolvedHeadingConfig;
  },

  collectHeadingClassVariants(target) {
    const html = typeof target?.innerHTML === 'string' ? target.innerHTML : '';
    if (!html.trim()) {
      return [];
    }

    const implementation = (this.frameDoc && this.frameDoc.implementation)
      || (typeof document !== 'undefined' ? document.implementation : null);
    if (!implementation || typeof implementation.createHTMLDocument !== 'function') {
      return [];
    }

    const workingDocument = implementation.createHTMLDocument('');
    workingDocument.body.innerHTML = html;

    const variantsByKey = new Map();
    const selector = 'h1[class], h2[class], h3[class], h4[class], h5[class], h6[class], span[class]';

    workingDocument.body.querySelectorAll(selector).forEach(element => {
      const name = `${element.tagName || ''}`.toLowerCase();
      if (!name) {
        return;
      }

      const classes = Array.from(element.classList || [])
        .map(item => item.trim())
        .filter(item => item && !item.startsWith('ck-'))
        .sort((a, b) => a.localeCompare(b));

      if (!classes.length) {
        return;
      }

      const key = `${name}|${classes.join(' ')}`;
      if (!variantsByKey.has(key)) {
        variantsByKey.set(key, { name, classes });
      }
    });

    return Array.from(variantsByKey.values());
  },

  generateHeadingVariantModel(variant, knownModels, index) {
    const levelMatch = variant.name.match(/^h([1-6])$/);
    const baseModel = levelMatch ? `heading${levelMatch[1]}` : `heading_${variant.name}`;
    const classSlug = variant.classes
      .join('_')
      .replace(/[^a-z0-9_-]/gi, '_')
      .split('_')
      .filter(s => s.length > 0)
      .join('_')
      .toLowerCase();

    const suffix = classSlug || `variant_${index}`;
    let candidate = `${baseModel}_${suffix}`;
    let attempt = 1;

    while (knownModels.has(candidate.toLowerCase())) {
      candidate = `${baseModel}_${suffix}_${attempt}`;
      attempt += 1;
    }

    return candidate;
  },

  /**
   * Enables the rich text editor on an element.
   *
   * @param {HTMLElement} el - The element to enable editor on
   * @param {Object} rte - The RTE instance (should be this)
   * @returns {Object} - The RTE instance
   */
  enable(el, rte) {
    if (rte && rte !== this) {
      return rte;
    }

    if (rte && rte === this && this.el === el && this.ckeditor) {
      this.focus(el, rte);
      return rte;
    }

    this.originalContent = typeof el?.innerHTML === 'string' ? el.innerHTML : '';
    this.latestContent = this.originalContent;

    const selectedComponent = typeof this.editor?.getSelected === 'function'
      ? this.editor.getSelected()
      : null;
    this.trackBadgableComponent(selectedComponent);
    this.trackToolbarVisibility();
    const computedWidth = this.updateMenuWidthsBySelection(selectedComponent);

    this.prepareEditorActivation(el);
    const initializeEditor = () => this.initializeEditorInstance(el, computedWidth);

    this.ensureFontConfig()
      .catch(() => [])
      .then(initializeEditor);
    return this;
  },

  prepareEditorActivation(el) {
    this.el = el;
    this.display = el.style.display;
    this.inlineMode = this.isInline(el);
    this.editorContainer = createHtmlElem('div', el.parentElement, {});
  },

  initializeEditorInstance(el, computedWidth) {
    if (!this.el || this.el !== el) {
      return;
    }

    this.injectFontStyles();

    const optionsKey = this.registerEditorOptions(this.compileEditorOptions(el));
    const reuseEditor = this._Ck5ForGrapesJsData.reuseEditor ? 'true' : 'false';
    this.executeInFrame(
      `${injectEditorInstant.name}('#${this.getElementId(this.editorContainer)}','${optionsKey}',${this.inlineMode ? 'true' : 'false'},${reuseEditor});`
    );

    const toolbarContainer = this.toolbarContainer;
    const toolbarMaxWidth = computedWidth || (this.inlineMode ? this.inlineMenuMaxWidth : this.menuMaxWidth);
    this.applyToolbarMaxWidth(toolbarContainer, toolbarMaxWidth);
    this.applyInlineModeStyles(el);
    this.observeEditorElements(toolbarContainer);

    setTimeout(() => this.mountEditorUi(el, toolbarMaxWidth));
  },

  applyToolbarMaxWidth(toolbarContainer, toolbarMaxWidth) {
    if (toolbarContainer && toolbarMaxWidth) {
      toolbarContainer.style.maxWidth = toolbarMaxWidth;
    }
  },

  applyInlineModeStyles(el) {
    if (!this.inlineMode) {
      return;
    }

    if (['span', 'a'].includes(el.tagName.toLowerCase())) {
      el.style.display = 'inline-block';
    }

    const head = this.frameDoc ? this.frameDoc.querySelector('head') : null;
    if (!head) {
      return;
    }

    this.inlineStyles = createHtmlElem(
      'style',
      head,
      {
        innerHTML: `.ck-editor__editable>p {display: inline-block; margin-top: 0px !important; margin-bottom: 0px !important;}` +
          `.ck-editor__editable {display: inline-block;}`
      }
    );
  },

  observeEditorElements(toolbarContainer) {
    if (toolbarContainer?.firstChild) {
      this.toolBarMObserver.observe(toolbarContainer.firstChild, {
        subtree: true,
        childList: true,
        attributes: true
      });
    }

    if (this.el) {
      this.elementObserver.observe(this.el, {
        subtree: true,
        childList: true,
        attributes: true
      });
    }
  },

  getEditorEditableElement(ckeditor) {
    if (ckeditor?.ui?.view?.editable) {
      return ckeditor.ui.view.editable.element;
    }

    return null;
  },

  getEditorToolbarElement(ckeditor) {
    if (ckeditor?.ui?.view) {
      return ckeditor.ui.view.element;
    }

    return null;
  },

  detachEditableClassObserver() {
    const observer = this._Ck5ForGrapesJsData?.editableClassObserver;
    if (!observer || typeof observer.disconnect !== 'function') {
      this._Ck5ForGrapesJsData.editableClassObserver = null;
      return;
    }

    observer.disconnect();
    this._Ck5ForGrapesJsData.editableClassObserver = null;
  },

  stripEditableClasses(editableEl) {
    if (!editableEl || typeof editableEl.removeAttribute !== 'function') {
      return;
    }

    const removeClasses = () => {
      if (editableEl.getAttribute('class')) {
        editableEl.removeAttribute('class');
      }
    };

    removeClasses();
    this.detachEditableClassObserver();

    if (typeof MutationObserver !== 'function') {
      return;
    }

    const observer = new MutationObserver(() => removeClasses());
    observer.observe(editableEl, {
      attributes: true,
      attributeFilter: ['class']
    });

    this._Ck5ForGrapesJsData.editableClassObserver = observer;
  },

  ensureBodyWrapperHandlers() {
    if (!this._Ck5ForGrapesJsData.bodyWrapperMouseDownHandler) {
      this._Ck5ForGrapesJsData.bodyWrapperMouseDownHandler = e => {
        e.stopPropagation();
        e.stopImmediatePropagation();
      };
    }

    if (!this._Ck5ForGrapesJsData.bodyWrapperClickHandler) {
      this._Ck5ForGrapesJsData.bodyWrapperClickHandler = e => {
        e.stopPropagation();
        e.stopImmediatePropagation();
      };
    }
  },

  bindBodyWrapperListeners(bodyWrapper) {
    this.ensureBodyWrapperHandlers();

    bodyWrapper.addEventListener('mousedown', this._Ck5ForGrapesJsData.bodyWrapperMouseDownHandler);
    bodyWrapper.addEventListener('click', this._Ck5ForGrapesJsData.bodyWrapperClickHandler);
    this._Ck5ForGrapesJsData.bodyWrapperEl = bodyWrapper;
  },

  mountEditorUi(el, toolbarMaxWidth) {
    if (!this.el || this.el !== el) {
      return;
    }

    const ckeditor = this.ckeditor;
    if (!ckeditor) {
      return;
    }

    // CKEditor 5 silently drops plain <span> elements (no attributes) because its
    // model is attribute-based and has nothing to store for them. Injecting a
    // placeholder attribute gives GHS something to preserve; it is removed in getContent().
    // A non-empty value ("1") is required — CKEditor GHS may discard attributes whose
    // value is an empty string, which would cause the span to be silently dropped.
    const contentForEditor = this.latestContent.replace(/<span\s*>/gi, '<span data-gjs-span="1">');
    ckeditor.data.set(contentForEditor);
    this.latestContent = null;
    el.innerHTML = '';

    const editableEl = this.getEditorEditableElement(ckeditor);
    if (editableEl) {
      this.stripEditableClasses(editableEl);
      el.appendChild(editableEl);
    }

    const toolbarWrapper = this.toolbarContainer?.firstChild;
    const toolbarEl = this.getEditorToolbarElement(ckeditor);
    if (toolbarWrapper && toolbarEl) {
      toolbarWrapper.appendChild(toolbarEl);
    }

    this.applyToolbarMaxWidth(this.toolbarContainer, toolbarMaxWidth);
    this.applyLinkUnderlineNormalization(this.el);
    this.applyIndentationNormalization(this.el);
    this.editor.refresh();
    this.onResize();

    try {
      ckeditor.focus();
    } catch (error) {
      console.warn('GrapesJS CKEditor: unable to focus editor', error);
    }

    const bodyWrapper = this.frameDoc?.querySelector('.ck-body-wrapper');
    if (bodyWrapper) {
      this.bindBodyWrapperListeners(bodyWrapper);
    }

    this.setCaret();
  },

  /**
   * Sets the caret position in the editor based on the last click event.
   */
  setCaret() {
    if (!this.latestClickEvent) return;
    let e = this.latestClickEvent;
    let range = null;
    let textNode;
    let offset;
    if (document.caretRangeFromPoint) {
      range = this.frameDoc.caretRangeFromPoint(e.clientX, e.clientY);
      textNode = range.startContainer;
      offset = range.startOffset;
    } else if (document.caretPositionFromPoint) {
      range = this.frameDoc.caretPositionFromPoint(e.clientX, e.clientY);
      textNode = range.offsetNode;
      offset = range.offset;
    }
    if (range) {
      range = this.frameDoc.createRange();
      let sel = this.frameContentWindow.getSelection();
      range.setStart(textNode, offset)
      range.collapse(true)
      sel.removeAllRanges();
      sel.addRange(range);
    }
    this.latestClickEvent = null;
  },

  /**
   * Focuses the editor.
   *
   * @param {HTMLElement} el
   * @param {Object} rte
   */
  focus(el, rte) {
    if (rte && rte !== this) {
      return;
    }

    if (el && this.el !== el) {
      this.el = el;
    }

    if (el) {
      el.contentEditable = true;
    }

    const ckeditor = this.ckeditor;
    if (ckeditor && typeof ckeditor.focus === 'function') {
      try {
        ckeditor.focus();
      } catch (error) {
        console.warn('GrapesJS CKEditor: unable to focus editor', error);
      }
    }

    this.setCaret();
  },

  /**
   * Tracks the 'badgable' state of a component to temporarily disable it.
   *
   * @param {Object} component
   */
  trackBadgableComponent(component) {
    if (!component || typeof component.get !== 'function' || typeof component.set !== 'function') {
      this.badgableInfo = null;
      return;
    }

    let previousState;
    try {
      previousState = component.get('badgable');
    } catch (error) {
      previousState = undefined;
    }

    const shouldRestore = previousState !== false;
    if (!shouldRestore) {
      this.badgableInfo = null;
      return;
    }

    this.badgableInfo = {
      component,
      previousState
    };

    try {
      component.set('badgable', false);
    } catch (error) {
      console.warn('GrapesJS CKEditor: unable to disable badgable on component', error);
      this.badgableInfo = null;
    }
  },

  /**
   * Restores the 'badgable' state of the tracked component.
   */
  restoreBadgableComponent() {
    if (!this.badgableInfo) {
      return;
    }

    const { component, previousState } = this.badgableInfo;
    this.badgableInfo = null;

    if (!component || typeof component.set !== 'function') {
      return;
    }

    const targetState = typeof previousState === 'boolean' ? previousState : true;

    try {
      component.set('badgable', targetState);
    } catch (error) {
      console.warn('GrapesJS CKEditor: unable to restore badgable on component', error);
    }
  },

  /**
   * Tracks the current toolbar visibility state to hide it during editing.
   */
  trackToolbarVisibility() {
    this.restoreToolbarVisibility();

    const canvas = this.editor?.Canvas;
    const getToolbar = typeof canvas?.getToolbarEl === 'function' ? () => canvas.getToolbarEl() : null;
    if (!getToolbar) {
      this.toolbarVisibilityInfo = null;
      return;
    }

    const toolbarEl = getToolbar();
    if (!toolbarEl) {
      this.toolbarVisibilityInfo = null;
      return;
    }

    const info = {
      toolbarEl,
      previousDisplay: toolbarEl.style.display,
      previousVisibility: toolbarEl.style.visibility,
      previousPointerEvents: toolbarEl.style.pointerEvents,
      listener: null
    };

    const hideToolbar = () => {
      const target = getToolbar ? (getToolbar() || toolbarEl) : toolbarEl;
      if (!target) {
        return;
      }

      info.toolbarEl = target;
      target.style.display = 'none';
      target.style.visibility = 'hidden';
      target.style.pointerEvents = 'none';
    };

    hideToolbar();

    if (typeof this.editor.on === 'function') {
      const listener = () => hideToolbar();
      this.editor.on('canvas:tools:update', listener);
      info.listener = listener;
    }

    this.toolbarVisibilityInfo = info;
  },

  /**
   * Restores the GrapesJS toolbar visibility.
   */
  restoreToolbarVisibility() {
    if (!this.toolbarVisibilityInfo) {
      return;
    }

    const { toolbarEl, previousDisplay, previousVisibility, previousPointerEvents, listener } = this.toolbarVisibilityInfo;

    if (listener && typeof this.editor?.off === 'function') {
      try {
        this.editor.off('canvas:tools:update', listener);
      } catch (error) {
        console.warn('GrapesJS CKEditor: failed to detach toolbar listener', error);
      }
    }

    if (toolbarEl) {
      toolbarEl.style.display = typeof previousDisplay === 'string' ? previousDisplay : '';
      toolbarEl.style.visibility = typeof previousVisibility === 'string' ? previousVisibility : '';
      toolbarEl.style.pointerEvents = typeof previousPointerEvents === 'string' ? previousPointerEvents : '';
    }

    this.toolbarVisibilityInfo = null;
  },

  /**
   * Gets the content from the editor, applying normalizations.
   *
   * @returns {string}
   */
  getContent() {
    const ckeditor = this.ckeditor;
    let ckeditorContent = ckeditor?.data ? ckeditor.data.get() : '';
    if (typeof ckeditorContent !== "string") ckeditorContent = "";
    // Remove the placeholder injected in mountEditorUi() to preserve plain <span> elements.
    ckeditorContent = ckeditorContent.replace(/ data-gjs-span="1"/g, '');
    const baseContent = this.resolveBaseContent(ckeditorContent);

    return this.normalizeWordInlineStyles(
      this.normalizeListMarkerStyles(
        this.normalizeIndentationStyles(
          this.normalizeLinkUnderlineColors(baseContent)
        )
      )
    );
  },

  restoreOriginalElementAttributes(content) {
    if (typeof content !== 'string') {
      return '';
    }

    const originalContent = typeof this.originalContent === 'string' ? this.originalContent : '';
    if (!originalContent.trim()) {
      return content;
    }

    const implementation = (this.frameDoc && this.frameDoc.implementation)
      || (typeof document !== 'undefined' ? document.implementation : null);
    if (!implementation || typeof implementation.createHTMLDocument !== 'function') {
      return content;
    }

    const currentDocument = implementation.createHTMLDocument('');
    const originalDocument = implementation.createHTMLDocument('');

    currentDocument.body.innerHTML = content;
    originalDocument.body.innerHTML = originalContent;

    this.reconcileElementAttributes(originalDocument.body, currentDocument.body);
    this.restoreMissingDataSpanWrappers(originalDocument.body, currentDocument.body);

    return currentDocument.body.innerHTML;
  },

  restoreMissingDataSpanWrappers(originalRoot, currentRoot) {
    if (!originalRoot || !currentRoot) {
      return;
    }

    const originalText = this.normalizeComparableText(originalRoot.textContent);
    const currentText = this.normalizeComparableText(currentRoot.textContent);
    if (!originalText || originalText !== currentText) {
      return;
    }

    const spanDescriptors = this.collectDataSpanDescriptors(originalRoot);
    if (!spanDescriptors.length) {
      return;
    }

    this.applyMissingDataSpansByOffsets(currentRoot, spanDescriptors);
  },

  normalizeComparableText(value) {
    if (typeof value !== 'string') {
      return '';
    }

    return value.replace(/\u00a0/g, ' ').replace(/\s+/g, ' ').trim();
  },

  collectDataSpanDescriptors(root) {
    if (!root || typeof Node === 'undefined') {
      return [];
    }

    const descriptors = [];
    let offset = 0;

    const walk = (node) => {
      if (!node) {
        return;
      }

      if (node.nodeType === Node.TEXT_NODE) {
        offset += (node.nodeValue || '').length;
        return;
      }

      if (node.nodeType !== Node.ELEMENT_NODE) {
        return;
      }

      const tagName = `${node.tagName || ''}`.toLowerCase();
      const isDataSpan = tagName === 'span' && this.elementHasDataAttributes(node);
      const start = offset;

      Array.from(node.childNodes || []).forEach(walk);

      if (!isDataSpan) {
        return;
      }

      const end = offset;
      if (end <= start) {
        return;
      }

      descriptors.push({
        start,
        end,
        attributes: Array.from(node.attributes || []).map(attribute => ({
          name: attribute.name,
          value: attribute.value || ''
        }))
      });
    };

    Array.from(root.childNodes || []).forEach(walk);

    return descriptors;
  },

  elementHasDataAttributes(element) {
    if (!element) {
      return false;
    }

    return Array.from(element.attributes || []).some(attribute => {
      const name = `${attribute?.name || ''}`.toLowerCase();
      return name.startsWith('data-');
    });
  },

  applyMissingDataSpansByOffsets(root, descriptors) {
    if (!root || !Array.isArray(descriptors) || !descriptors.length) {
      return;
    }

    descriptors.forEach(descriptor => {
      const startPoint = this.resolveTextOffset(root, descriptor.start);
      const endPoint = this.resolveTextOffset(root, descriptor.end);
      if (!startPoint || !endPoint) {
        return;
      }

      const range = root.ownerDocument.createRange();
      range.setStart(startPoint.node, startPoint.offset);
      range.setEnd(endPoint.node, endPoint.offset);

      if (range.collapsed || this.rangeContainsDataSpan(range)) {
        return;
      }

      const span = root.ownerDocument.createElement('span');
      (descriptor.attributes || []).forEach(attribute => {
        if (!attribute?.name) {
          return;
        }

        span.setAttribute(attribute.name, attribute.value || '');
      });

      try {
        const fragment = range.extractContents();
        span.appendChild(fragment);
        range.insertNode(span);
      } catch (error) {
        // Ignore restoration errors and preserve edited content.
      }
    });
  },

  resolveTextOffset(root, targetOffset) {
    if (!root || typeof NodeFilter === 'undefined') {
      return null;
    }

    const normalizedTarget = Math.max(0, Number.isFinite(targetOffset) ? targetOffset : 0);
    const walker = root.ownerDocument.createTreeWalker(
      root,
      NodeFilter.SHOW_TEXT,
      null
    );

    let consumed = 0;
    let currentNode = walker.nextNode();

    while (currentNode) {
      const length = (currentNode.nodeValue || '').length;
      const nextConsumed = consumed + length;

      if (normalizedTarget <= nextConsumed) {
        return {
          node: currentNode,
          offset: Math.max(0, Math.min(length, normalizedTarget - consumed))
        };
      }

      consumed = nextConsumed;
      currentNode = walker.nextNode();
    }

    return null;
  },

  rangeContainsDataSpan(range) {
    if (!range) {
      return false;
    }

    const clonedContent = range.cloneContents();
    if (!clonedContent || typeof clonedContent.querySelectorAll !== 'function') {
      return false;
    }

    return Array.from(clonedContent.querySelectorAll('span')).some(element => this.elementHasDataAttributes(element));
  },

  reconcileElementAttributes(originalRoot, currentRoot) {
    if (!originalRoot || !currentRoot) {
      return;
    }

    const originalChildren = Array.from(originalRoot.children || []);
    const currentChildren = Array.from(currentRoot.children || []);
    const length = Math.min(originalChildren.length, currentChildren.length);

    for (let index = 0; index < length; index += 1) {
      const originalElement = originalChildren[index];
      const currentElement = currentChildren[index];
      if (!originalElement || !currentElement) {
        continue;
      }

      const originalTagName = `${originalElement.tagName || ''}`.toLowerCase();
      const currentTagName = `${currentElement.tagName || ''}`.toLowerCase();

      if (originalTagName !== currentTagName) {
        // Handle the case where CKEditor flattened a gjs-heading-wrapper div:
        // original: <div class="gjs-heading-wrapper"><span ...>text</span></div>
        // current:  <span ...>text</span>
        // Reconcile attributes from the inner element to the current element so
        // data-gjs-type, id, draggable etc. are preserved after editing.
        if (originalTagName === 'div' && originalElement.classList.contains('gjs-heading-wrapper')) {
          const originalInner = originalElement.firstElementChild;
          if (originalInner && `${originalInner.tagName || ''}`.toLowerCase() === currentTagName) {
            this.reconcileSingleElementAttributes(originalInner, currentElement);
            this.reconcileElementAttributes(originalInner, currentElement);
          }
        }
        continue;
      }

      this.reconcileSingleElementAttributes(originalElement, currentElement);
      this.reconcileElementAttributes(originalElement, currentElement);
    }
  },

  reconcileSingleElementAttributes(originalElement, currentElement) {
    if (!originalElement || !currentElement) {
      return;
    }

    this.reconcileClassAttribute(originalElement, currentElement);
    this.reconcileStyleAttribute(originalElement, currentElement);

    if (originalElement.id && !currentElement.id) {
      currentElement.id = originalElement.id;
    }

    Array.from(originalElement.attributes || []).forEach(attribute => {
      const name = `${attribute?.name || ''}`.toLowerCase();
      if (!name || name === 'class' || name === 'style' || name === 'id') {
        return;
      }

      if (!name.startsWith('data-') && !name.startsWith('aria-')) {
        return;
      }

      if (!currentElement.hasAttribute(name)) {
        currentElement.setAttribute(name, attribute.value || '');
      }
    });
  },

  reconcileClassAttribute(originalElement, currentElement) {
    const originalClasses = Array.from(originalElement.classList || []).map(item => item.trim()).filter(Boolean);
    if (!originalClasses.length) {
      return;
    }

    const currentClasses = Array.from(currentElement.classList || []).map(item => item.trim()).filter(Boolean);
    const mergedClasses = [...currentClasses];

    originalClasses.forEach(className => {
      if (!mergedClasses.includes(className)) {
        mergedClasses.push(className);
      }
    });

    if (!mergedClasses.length) {
      return;
    }

    currentElement.setAttribute('class', mergedClasses.join(' '));
  },

  reconcileStyleAttribute(originalElement, currentElement) {
    const originalStyle = originalElement.style;
    const currentStyle = currentElement.style;
    if (!originalStyle || !currentStyle) {
      return;
    }

    for (let index = 0; index < originalStyle.length; index += 1) {
      const property = originalStyle[index];
      if (!property) {
        continue;
      }

      const currentValue = currentStyle.getPropertyValue(property);
      if (currentValue) {
        continue;
      }

      const value = originalStyle.getPropertyValue(property);
      if (!value) {
        continue;
      }

      const priority = originalStyle.getPropertyPriority(property) || '';
      currentStyle.setProperty(property, value, priority);
    }
  },

  resolveBaseContent(ckeditorContent) {
    if (this.latestContent !== null) {
      return this.latestContent;
    }

    if (!this.inlineMode) {
      return ckeditorContent;
    }

    return this.unwrapInlineParagraphRoot(ckeditorContent);
  },

  unwrapInlineParagraphRoot(ckeditorContent) {
    if (typeof ckeditorContent !== 'string') {
      return '';
    }

    const content = ckeditorContent.trim();
    if (!content) {
      return '';
    }

    const originalContent = typeof this.originalContent === 'string' ? this.originalContent.trim() : '';
    const originalStartsWithParagraph = /^<p(\s|>)/i.test(originalContent);
    const originalEndsWithParagraph = /<\/p>$/i.test(originalContent);
    if (originalStartsWithParagraph && originalEndsWithParagraph) {
      return ckeditorContent;
    }

    const implementation = (this.frameDoc && this.frameDoc.implementation)
      || (typeof document !== 'undefined' ? document.implementation : null);
    if (!implementation || typeof implementation.createHTMLDocument !== 'function') {
      return ckeditorContent;
    }

    const workingDocument = implementation.createHTMLDocument('');
    workingDocument.body.innerHTML = content;

    const elementChildren = Array.from(workingDocument.body.children || []);
    if (elementChildren.length !== 1) {
      return ckeditorContent;
    }

    const rootElement = elementChildren[0];
    if (`${rootElement.tagName || ''}`.toLowerCase() !== 'p') {
      return ckeditorContent;
    }

    const hasOnlyOneBlock = workingDocument.body.childNodes.length === 1;
    if (!hasOnlyOneBlock) {
      return ckeditorContent;
    }

    return rootElement.innerHTML || '';
  },

  /**
   * Gets content for the interface (GrapesJ).
   *
   * @param {HTMLElement} el
   * @param {Object} rte
   * @returns {string}
   */
  getContentForInterface(el, rte) {
    if (rte && rte !== this) {
      return typeof el?.innerHTML === 'string' ? el.innerHTML : '';
    }

    if (!this.isActive) {
      return typeof el?.innerHTML === 'string' ? el.innerHTML : '';
    }

    return this.getContent();
  },

  /**
   * Disables the editor and cleans up.
   *
   * @param {HTMLElement} el
   * @param {Object} rte
   */
  disable(el, rte) {
    if (rte && rte !== this) {
      return;
    }

    this.restoreBadgableComponent();
    this.restoreToolbarVisibility();

    if (!this.el) {
      return;
    }

    const content = this.getContent();
    const toolbarContainer = this.toolbarContainer;
    const ckeditor = this.ckeditor;
    const reuseEditor = !!this._Ck5ForGrapesJsData.reuseEditor;

    this.toolBarMObserver.disconnect();
    this.elementObserver.disconnect();
    this.gjsToolBarMObserver.disconnect();
    this.detachFrameListeners();
    this.detachFrameBodyListeners();
    this.detachBodyWrapperListeners();
    this.disconnectTipObserver(reuseEditor);

    const finalizeCleanup = () => this.finalizeDisableCleanup(content, reuseEditor, toolbarContainer);
    this.destroyEditorAndCleanup(ckeditor, reuseEditor, finalizeCleanup);
  },

  detachFrameListeners() {
    const frameWindow = this.frameContentWindow;
    if (frameWindow && this._Ck5ForGrapesJsData.frameScrollHandler) {
      frameWindow.removeEventListener('scroll', this._Ck5ForGrapesJsData.frameScrollHandler);
    }
    if (frameWindow && this._Ck5ForGrapesJsData.frameResizeHandler) {
      frameWindow.removeEventListener('resize', this._Ck5ForGrapesJsData.frameResizeHandler);
    }
  },

  detachFrameBodyListeners() {
    if (this._Ck5ForGrapesJsData.frameBodyEl && this._Ck5ForGrapesJsData.frameBodyMouseDownHandler) {
      this._Ck5ForGrapesJsData.frameBodyEl.removeEventListener('mousedown', this._Ck5ForGrapesJsData.frameBodyMouseDownHandler);
    }
  },

  detachBodyWrapperListeners() {
    const bodyWrapperEl = this._Ck5ForGrapesJsData.bodyWrapperEl;
    if (!bodyWrapperEl) {
      return;
    }

    if (this._Ck5ForGrapesJsData.bodyWrapperMouseDownHandler) {
      bodyWrapperEl.removeEventListener('mousedown', this._Ck5ForGrapesJsData.bodyWrapperMouseDownHandler);
    }
    if (this._Ck5ForGrapesJsData.bodyWrapperClickHandler) {
      bodyWrapperEl.removeEventListener('click', this._Ck5ForGrapesJsData.bodyWrapperClickHandler);
    }
  },

  disconnectTipObserver(reuseEditor) {
    if (reuseEditor || !this.inFrameData?.tipObserver) {
      return;
    }

    try {
      this.inFrameData.tipObserver.disconnect();
    } catch (error) {
      console.warn('GrapesJS CKEditor: unable to disconnect tip observer', error);
    }

    this.inFrameData.tipObserver = null;
  },

  finalizeDisableCleanup(content, reuseEditor, toolbarContainer) {
    this.detachEditableClassObserver();

    if (this.inFrameData) {
      if (!reuseEditor) {
        this.inFrameData.editor = null;
      }
      this.inFrameData.toolbarContainer = null;
    }

    toolbarContainer?.remove();
    this.inlineStyles?.remove();
    this.inlineStyles = null;
    this._Ck5ForGrapesJsData.frameBodyEl = null;
    this._Ck5ForGrapesJsData.bodyWrapperEl = null;
    const restoredContent = this.restoreOriginalElementAttributes(content);
    this.el.innerHTML = restoredContent;
    this.el.style.display = this.display;
    this.el.contentEditable = false;
    this.el = null;
    this.editorContainer?.remove();
    this.editorContainer = null;
    this.latestContent = null;
    this.originalContent = null;
    this.display = undefined;
    this.latestClickEvent = null;
  },

  destroyEditorAndCleanup(ckeditor, reuseEditor, finalizeCleanup) {
    if (reuseEditor) {
      finalizeCleanup();
      return;
    }

    const destroyEditorContext = () => {
      if (typeof ckeditor?._context?.destroy === 'function') {
        ckeditor._context.destroy();
      }
    };

    if (typeof ckeditor?.destroy === 'function') {
      Promise.resolve(ckeditor.destroy())
        .catch(error => {
          console.warn('GrapesJS CKEditor: unable to destroy editor', error);
        })
        .then(destroyEditorContext)
        .then(finalizeCleanup);

      return;
    }

    destroyEditorContext();
    finalizeCleanup();
  },

  /**
   * Injects the CKEditor script and data storage into the iframe.
   *
   * @param {string} src
   */
  injectEditorModule(src) {
    const hostHead = document.querySelector('head');
    if (hostHead && !document.getElementById('grapesjs-ckeditor-toolbar-style')) {
      createHtmlElem(
        'style',
        hostHead,
        {
          id: 'grapesjs-ckeditor-toolbar-style',
          innerHTML: `.gjs-rte-toolbar {opacity: 0;}`
        }
      );
    }

    const frameDocument = this.frameDoc;
    const body = this.frameBody;
    if (!frameDocument || !body) {
      return;
    }

    const moduleSource = typeof src === 'string' ? src.trim() : '';
    const injectedModuleScript = frameDocument.getElementById('grapesjs-ckeditor-module-loader');
    const hasInjectedModule = !!(moduleSource && injectedModuleScript?.getAttribute('src') === moduleSource);

    if (!hasInjectedModule && moduleSource) {
      createHtmlElem(
        'script',
        body,
        {
          id: 'grapesjs-ckeditor-module-loader',
          src: moduleSource,
        }
      ).onload =
        () => setTimeout(
          () => {
            const styles = [...frameDocument.querySelectorAll('style')];
            for (let index = 0; index < styles.length; index += 1) {
              const item = styles[index];
              let innerHTML = item.innerHTML;
              let match = innerHTML.match(/.ck.ck-editor__editable_inline ?{[^}]*(overflow:[^;]*;)[^}]*}/);
              if (!match) {
                continue;
              }

              item.innerHTML = innerHTML.replace(match[0], '');
              createHtmlElem(
                'style',
                item.parentNode,
                {
                  innerHTML: `.ck-toolbar {border-bottom-width: 1px !important;}` +
                    `.ck.ck-editor__editable.ck-focused:not(.ck-editor__nested-editable) {border: none !important;box-shadow: none !important;} 
                         .ck.ck-dropdown .ck-dropdown__panel.ck-dropdown__panel-visible { max-height: 200px; overflow-y: auto; } `
                }
              );
              break;
            }
          }
        );
    }

    if (!frameDocument.getElementById('grapesjs-ckeditor-runtime')) {
      createHtmlElem(
        'script',
        body,
        {
          id: 'grapesjs-ckeditor-runtime',
          innerHTML: `${setElementProperty.toString()}; ${injectEditorInstant.toString()}; function _typeof(obj) { return typeof obj; }`
        }
      );
    }

    if (!this.frameContentWindow.grapesjsCkeditorData) {
      this.executeInFrame(
        `(${injectDataStorage.toString()})()`
      );
    }

    if (!this._Ck5ForGrapesJsData.frameScrollHandler) {
      this._Ck5ForGrapesJsData.frameScrollHandler = this.onResize.bind(this);
    }
    if (!this._Ck5ForGrapesJsData.frameResizeHandler) {
      this._Ck5ForGrapesJsData.frameResizeHandler = this.onResize.bind(this);
    }
    this.frameContentWindow.addEventListener(
      'scroll',
      this._Ck5ForGrapesJsData.frameScrollHandler
    );
    this.frameContentWindow.addEventListener(
      'resize',
      this._Ck5ForGrapesJsData.frameResizeHandler
    );
  },

  /**
   * Executes code within the iframe context.
   *
   * @param {string} code
   */
  executeInFrame(code) {
    createHtmlElem(
      'script',
      this.frameBody,
      {
        innerHTML: code
      }
    ).remove();
  },

  /**
   * Gets or generates a unique ID for an element.
   *
   * @param {HTMLElement} el
   * @returns {string}
   */
  getElementId(el) {
    if (el.id === '' || el.id === null || el.id === undefined) {
      el.id = `ckeditor_target_el_${this.uniqId}`;
    }

    return el.id;
  },

  /**
   * Adjusts the GrapesJS toolbar opacity if it overlaps with panels.
   */
  tuneGjsToolbar() {
    const gjsToolbar = this.gjsToolbar;
    if (gjsToolbar) {
      if (this.isActive && isOpenPanelOverlapGjsToolbar(this.toolbarContainer, gjsToolbar, this.frame)) {
        gjsToolbar.style.opacity = 0;
        gjsToolbar.style.pointerEvents = 'none';
      } else {
        gjsToolbar.style.opacity = 'unset';
        gjsToolbar.style.pointerEvents = 'all';
      }
    }
  },

  /**
   * Updates toolbar max widths based on selection.
   *
   * @param {Object} component
   * @returns {string|null}
   */
  updateMenuWidthsBySelection(component) {
    const targetComponent = component || (typeof this.editor?.getSelected === 'function' ? this.editor.getSelected() : null);
    const element = typeof targetComponent?.getEl === 'function' ? targetComponent.getEl() : null;
    const width = typeof element?.getBoundingClientRect === 'function' ? element.getBoundingClientRect().width : null;
    if (!Number.isFinite(width) || width <= 0) {
      return null;
    }

    const minWidth = 445;
    const widthValue = `${Math.max(width, minWidth)}px`;
    this._Ck5ForGrapesJsData.menuMaxWidth = widthValue;
    this._Ck5ForGrapesJsData.inlineMenuMaxWidth = widthValue;

    return widthValue;
  },

  /**
   * Positions the toolbar relative to the edited element.
   */
  positionToolbar() {
    if (!this.hasToolbarContainerContent()) {
      this.toolbarContainer.style.display = 'none';
      setTimeout(this.tuneGjsToolbar.bind(this));
      return;
    }

    this.toolbarContainer.style.display = '';
    this.toolbarContainer.style.top = '0px';
    this.toolbarContainer.style.left = '0px';

    const gjsToolbar = this.gjsToolbar;
    this.observeGjsToolbar(gjsToolbar);
    setTimeout(this.tuneGjsToolbar.bind(this));

    const gjsToolbarBoundingRect = this.getGjsToolbarRect(gjsToolbar);
    let toolBarBoundingRect = this.toolbarContainer.getBoundingClientRect();
    const elBoundingRect = this.el.getBoundingClientRect();
    const center = this.shouldCenterToolbar(toolBarBoundingRect, elBoundingRect, gjsToolbarBoundingRect.width);
    const left = this.calculateToolbarLeft(toolBarBoundingRect, elBoundingRect, center);

    this.toolbarContainer.style.left = `${left}px`;

    toolBarBoundingRect = this.toolbarContainer.getBoundingClientRect();
    const top = this.calculateToolbarTop(toolBarBoundingRect, elBoundingRect, gjsToolbarBoundingRect, center);
    this.toolbarContainer.style.top = `${top}px`;
  },

  hasToolbarContainerContent() {
    return !!this.toolbarContainer?.firstChild?.firstChild;
  },

  observeGjsToolbar(gjsToolbar) {
    if (!gjsToolbar) {
      return;
    }

    this.gjsToolBarMObserver.observe(gjsToolbar, {
      subtree: false,
      childList: false,
      attributes: true
    });
  },

  getGjsToolbarRect(gjsToolbar) {
    return gjsToolbar?.getBoundingClientRect() || { width: 0, height: 0, bottom: 0 };
  },

  shouldCenterToolbar(toolBarBoundingRect, elBoundingRect, gjsToolbarWidth) {
    const gjsToolbarHSpace = 1;
    return toolBarBoundingRect.width > elBoundingRect.width - gjsToolbarWidth - gjsToolbarHSpace;
  },

  calculateToolbarLeft(toolBarBoundingRect, elBoundingRect, center) {
    if (!center) {
      return elBoundingRect.left + this.frameScrollX;
    }

    const gjsToolbarToScreenBorderSpace = 5;
    let left = elBoundingRect.left - (toolBarBoundingRect.width - elBoundingRect.width) / 2 + this.frameScrollX;

    if (left + toolBarBoundingRect.width > this.frameBody.offsetWidth) {
      left -= left + toolBarBoundingRect.width - this.frameBody.offsetWidth + gjsToolbarToScreenBorderSpace;
    }

    if (left < this.frameScrollX) {
      return this.frameScrollX;
    }

    return left;
  },

  calculateToolbarTop(toolBarBoundingRect, elBoundingRect, gjsToolbarBoundingRect, center) {
    const gjsToolbarVSpace = 1;
    let top = (
      elBoundingRect.top + this.frameScrollY - toolBarBoundingRect.height - gjsToolbarVSpace -
      (center ? gjsToolbarBoundingRect.height : 0)
    );

    if (top > this.frameScrollY) {
      return top;
    }

    top = (
      elBoundingRect.bottom + this.frameScrollY + gjsToolbarVSpace +
      (center && gjsToolbarBoundingRect.bottom > elBoundingRect.bottom ? gjsToolbarBoundingRect.height : 0)
    );

    return top;
  },

  /**
   * Registers options in the iframe registry.
   *
   * @param {Object} options
   * @returns {string}
   */
  registerEditorOptions(options) {
    if (!this.frameContentWindow) {
      return '';
    }

    const frameData = this.frameContentWindow.grapesjsCkeditorData || (this.frameContentWindow.grapesjsCkeditorData = {});
    const registry = frameData.optionsRegistry || (frameData.optionsRegistry = {});
    const data = this._Ck5ForGrapesJsData || (this._Ck5ForGrapesJsData = {});

    data.optionsRegistryCounter = (data.optionsRegistryCounter || 0) + 1;
    const key = `options_${Date.now()}_${data.optionsRegistryCounter}`;

    registry[key] = options;

    return key;
  },

  /**
   * Resize handler to reposition toobar.
   */
  onResize() {
    if (this.isActive) {
      this.positionToolbar();
    }
  }
};
