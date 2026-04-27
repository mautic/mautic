/**
 * Injects data storage object into window.
*/

export function injectDataStorage() {
  window.grapesjsCkeditorData = {
    optionsRegistry: {}
  };
}

export function setElementProperty(elem, properties) {
  if (properties) {
    for (const key in properties) {
      if (_typeof(properties[key]) === 'object') {
        setElementProperty(elem[key], properties[key]);
      } else {
        elem[key] = properties[key];
      }
    }
  }
}

/**
 * Instantiates the editor instantly using options from registry.
 *
 * @param {string} selector
 * @param {string} optionsKey
 * @param {boolean} forceBr
 */
export function injectEditorInstant(selector, optionsKey, forceBr, reuseEditor) {
  const createHtmlElem = (type, container, properties) => {
    const elem = document.createElement(type);
    setElementProperty(elem, properties);
    if (container) {
      container.appendChild(elem);
    }
    return elem;
  };

  const normalizeFeedItems = (items) => Array.from(items || []);
  const toMentionFeedPromise = (feed, queryText) => Promise.resolve(feed(queryText))
    .then(normalizeFeedItems)
    .catch(err => {
      console.error('Mention feed error:', err);
      return [];
    });

  const wrapMentionFeed = (feed) => (queryText) => new Promise(resolve => {
    toMentionFeedPromise(feed, queryText).then(resolve);
  });

  const normalizeMentionFeeds = (mentionConfig) => {
    if (!mentionConfig || !Array.isArray(mentionConfig.feeds)) {
      return;
    }

    mentionConfig.feeds.forEach(feedConfig => {
      if (typeof feedConfig.feed === 'function') {
        feedConfig.feed = wrapMentionFeed(feedConfig.feed);
      }
    });
  };

  const registry = window.grapesjsCkeditorData?.optionsRegistry;
  const options = registry && optionsKey ? registry[optionsKey] : {};
  if (registry && optionsKey) {
    delete registry[optionsKey];
  }
  const attachToolbarContainer = () => {
    if (window.grapesjsCkeditorData.customFontSizeObserver) {
      try {
        window.grapesjsCkeditorData.customFontSizeObserver.disconnect();
      } catch (err) {
      }
      window.grapesjsCkeditorData.customFontSizeObserver = null;
    }

    if (window.grapesjsCkeditorData.toolbarContainer) {
      try {
        window.grapesjsCkeditorData.toolbarContainer.remove();
      } catch (err) {
      }
      window.grapesjsCkeditorData.toolbarContainer = null;
    }

    window.grapesjsCkeditorData.toolbarContainer = createHtmlElem(
      'div',
      document.body,
      {
        style: {
          position: 'absolute',
          top: '0px',
          bottom: '0px',
          height: 'min-content'
        }
      }
    );
    createHtmlElem(
      'div',
      window.grapesjsCkeditorData.toolbarContainer,
      {}
    );

    const stopToolbarEventPropagation = e => {
      e.stopPropagation();
      if (typeof e.stopImmediatePropagation === 'function') {
        e.stopImmediatePropagation();
      }
    };

    ['pointerdown', 'mousedown', 'mouseup', 'click', 'touchstart', 'touchend'].forEach(eventName => {
      window.grapesjsCkeditorData.toolbarContainer.addEventListener(eventName, stopToolbarEventPropagation);
    });
  };

  const ensureTipObserver = () => {
    const applyTipClass = () => {
      const buttons = document.querySelectorAll('.ck-button');
      buttons.forEach(btn => {
        if (!btn.classList.contains('token-tip-active') && btn.textContent.includes("Tip: Type '{'")) {
          btn.classList.add('token-tip-active');
        }
      });
    };

    if (window.grapesjsCkeditorData.tipObserver) {
      return;
    }

    const observer = new MutationObserver(applyTipClass);
    window.grapesjsCkeditorData.tipObserver = observer;
    observer.observe(document.body, { childList: true, subtree: true });
    applyTipClass();
  };

  const ensurePoweredByHidden = () => {
    if (document.getElementById('gjs-cke-hide-powered-by-style')) {
      return;
    }

    const style = document.createElement('style');
    style.id = 'gjs-cke-hide-powered-by-style';
    style.textContent = `
      .ck.ck-powered-by,
      .ck.ck-powered-by-balloon {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
      }
    `;

    document.head.appendChild(style);
  };

  const setupFontSizeCustomInput = (editorInstance) => {
      const toolbarRoot = editorInstance.ui?.view?.element
        ? editorInstance.ui.view.element
        : null;

      if (!toolbarRoot) {
        return;
      }

      const dropdown = toolbarRoot.querySelector('.ck-font-size-dropdown');
      if (!dropdown) {
        return;
      }

      const panel = dropdown.querySelector('.ck-dropdown__panel');
      if (!panel) {
        return;
      }

      if (panel.querySelector('.ck-font-size-custom-input-wrap')) {
        return;
      }

      const list = panel.querySelector('.ck-list');
      if (!list || !list.parentNode) {
        return;
      }

      const wrap = document.createElement('div');
      wrap.className = 'ck-font-size-custom-input-wrap';
      wrap.style.padding = '6px';
      wrap.style.borderBottom = '1px solid var(--ck-color-toolbar-border, #d9d9d9)';
      wrap.style.background = 'var(--ck-color-toolbar-background, #fff)';

      const controls = document.createElement('div');
      controls.className = 'ck-font-size-custom-controls';
      controls.style.display = 'block';

      const input = document.createElement('input');
      input.type = 'number';
      input.min = '1';
      input.step = '1';
      input.placeholder = 'Custom size';
      input.className = 'ck ck-input ck-font-size-custom-input';
      input.style.width = '100%';

      let savedSelectionRanges = [];

      const captureSelectionRanges = () => {
        try {
          const modelSelection = editorInstance.model?.document
            ? editorInstance.model.document.selection
            : null;

          if (!modelSelection) {
            savedSelectionRanges = [];
            return;
          }

          savedSelectionRanges = Array.from(modelSelection.getRanges()).map(range => range.clone());
        } catch (err) {
          savedSelectionRanges = [];
        }
      };

      const restoreSelectionRanges = () => {
        if (!savedSelectionRanges.length) {
          return;
        }

        try {
          editorInstance.model.change(writer => {
            writer.setSelection(savedSelectionRanges);
          });
        } catch (err) {
        }
      };

      const applyFontSizeValue = () => {
        const raw = typeof input.value === 'string' ? input.value.trim() : '';
        if (!raw) {
          return;
        }

        const parsed = Number(raw);
        if (!Number.isFinite(parsed) || parsed <= 0) {
          return;
        }

        const normalized = Math.round(parsed * 100) / 100;
        const normalizedString = `${normalized}`;
        const normalizedWithUnit = `${normalizedString}px`;
        const candidates = [
          normalizedWithUnit,
          normalizedString,
          normalized
        ];

        restoreSelectionRanges();

        for (let index = 0; index < candidates.length; index += 1) {
          const candidate = candidates[index];

          try {
            editorInstance.execute('fontSize', { value: candidate });
            return;
          } catch (err) {
          }
        }

        try {
          const fallbackValue = normalizedWithUnit;
          editorInstance.model.change(writer => {
            writer.removeSelectionAttribute('fontSize');
            writer.setSelectionAttribute('fontSize', fallbackValue);
          });
        } catch (err) {
          console.warn('GrapesJS CKEditor: unable to apply custom font size', err);
          return;
        }

        try {
          const editableRoot = editorInstance.ui?.view?.editable
            ? editorInstance.ui.view.editable.element
            : null;

          if (editableRoot) {
            const elementsWithStyle = editableRoot.querySelectorAll('[style]');
            elementsWithStyle.forEach(element => {
              const styleAttr = element.getAttribute('style');
              if (typeof styleAttr !== 'string' || !styleAttr.includes('font-size')) {
                return;
              }

              const normalizedStyle = styleAttr.replace(/font-size\s*:\s*(\d+(?:\.\d+)?)\s*;/gi, 'font-size:$1px;');
              if (normalizedStyle !== styleAttr) {
                element.setAttribute('style', normalizedStyle);
              }
            });
          }
        } catch (err) {
        }
      };

      input.addEventListener('keydown', (event) => {
        event.stopPropagation();

        if (event.key === 'Enter') {
          event.preventDefault();
          applyFontSizeValue();
        }
      });

      input.addEventListener('mousedown', event => event.stopPropagation());
      input.addEventListener('click', event => event.stopPropagation());
      input.addEventListener('focus', captureSelectionRanges);
      input.addEventListener('input', applyFontSizeValue);

      const setCurrentSize = () => {
        try {
          const command = editorInstance.commands?.get
            ? editorInstance.commands.get('fontSize')
            : null;
          const value = command ? command.value : null;

          if (typeof value === 'number' && Number.isFinite(value)) {
            input.value = `${value}`;
            return;
          }

          if (typeof value === 'string') {
            const normalized = value.trim().replace(/px$/i, '');
            if (/^\d+(?:\.\d+)?$/.test(normalized)) {
              input.value = normalized;
              return;
            }
          }

          input.value = '';
        } catch (err) {
          input.value = '';
        }
      };

      const dropdownButton = dropdown.querySelector('.ck-dropdown__button');
      if (dropdownButton) {
        dropdownButton.addEventListener('click', () => {
          setTimeout(() => {
            captureSelectionRanges();
            setCurrentSize();
          }, 0);
        });
      }

      controls.appendChild(input);
      wrap.appendChild(controls);
      list.parentNode.insertBefore(wrap, list);
  };

  const configureEditor = (editorInstance) => {
    const contentPolicy = options && typeof options.mauticContentPolicy === 'object'
      ? options.mauticContentPolicy
      : {};

    ensurePoweredByHidden();

    // Try to find CKEditor's toolbar element via the editor instance
    try {
      const rootEl = editorInstance.ui?.view?.element ? editorInstance.ui.view.element : null;
      const toolbarEl = rootEl ? rootEl.querySelector('.ck-toolbar') : null;
      if (toolbarEl) {
        // Hide toolbar initially
        try { toolbarEl.style.display = 'none'; } catch (err) { }

        // Show toolbar after 2 seconds using the editor object (accessing DOM through the editor)
        setTimeout(() => {
          try {
            // Prefer any API-backed toolbar element if available, fallback to DOM node
            const tb = editorInstance.ui?.view?.element?.querySelector('.ck-toolbar') || toolbarEl;
            if (tb) tb.style.display = '';
          } catch (err) {
            console.warn('GrapesJS CKEditor: unable to reveal toolbar', err);
          }
        }, 100);
      }
    } catch (err) {
      console.warn('GrapesJS CKEditor: toolbar manipulation failed', err);
    }

    setupFontSizeCustomInput(editorInstance);

    const customFontSizeObserver = new MutationObserver(() => {
      setupFontSizeCustomInput(editorInstance);
    });

    customFontSizeObserver.observe(document.body, { childList: true, subtree: true });
    window.grapesjsCkeditorData.customFontSizeObserver = customFontSizeObserver;

    if (forceBr && !window.grapesjsCkeditorData.forceBrApplied) {
      try {
        editorInstance.editing.view.document.on(
          'keydown',
          (event, data) => {
            if (data.keyCode === 13) {
              data.shiftKey = true;
            }
          },
          { priority: 'highest' }
        );
        window.grapesjsCkeditorData.forceBrApplied = true;
      } catch (err) {
        console.warn('GrapesJS CKEditor: unable to apply forceBr handler', err);
      }
    }

    if (contentPolicy.allowImages === false) {
      let isRemovingDisallowedImages = false;

      const removeDisallowedImages = () => {
        if (isRemovingDisallowedImages) {
          return;
        }

        const model = editorInstance.model;
        if (!model || !model.document) {
          return;
        }

        const removableElements = [];
        for (const root of model.document.roots) {
          for (const item of model.createRangeIn(root).getItems()) {
            if (!item || typeof item.is !== 'function' || !item.is('element')) {
              continue;
            }

            if (['image', 'imageBlock', 'imageInline'].includes(item.name)) {
              removableElements.push(item);
            }
          }
        }

        if (!removableElements.length) {
          return;
        }

        isRemovingDisallowedImages = true;
        try {
          model.change(writer => {
            removableElements.forEach(element => {
              if (element.root) {
                writer.remove(element);
              }
            });
          });
        } finally {
          isRemovingDisallowedImages = false;
        }
      };

      editorInstance.model.document.on('change:data', removeDisallowedImages);
      removeDisallowedImages();
    }

    ensureTipObserver();
  };

  const createEditor = () => {
    attachToolbarContainer();

    const editorConstructor =
      window.CKEDITOR?.ClassicEditor
      || window.ClassicEditor
      || (typeof ClassicEditor !== 'undefined' ? ClassicEditor : null);

    if (!editorConstructor || typeof editorConstructor.create !== 'function') {
      console.error('GrapesJS CKEditor: ClassicEditor constructor is not available in iframe context');
      return;
    }

    editorConstructor.create(
      document.querySelector(selector),
      options
    ).then(
      e => {
        window.grapesjsCkeditorData.editor = e;
        configureEditor(e);
      }
    ).catch(
      error => {
        console.error(error);
      }
    );
  };

  const existingEditor = window.grapesjsCkeditorData.editor;
  if (reuseEditor && existingEditor) {
    attachToolbarContainer();
    configureEditor(existingEditor);
  } else if (existingEditor && typeof existingEditor.destroy === 'function') {
    Promise.resolve(existingEditor.destroy())
      .catch(err => {
        console.warn('GrapesJS CKEditor: unable to destroy previous editor', err);
      })
      .then(() => {
        window.grapesjsCkeditorData.editor = null;
        window.grapesjsCkeditorData.forceBrApplied = false;
        createEditor();
      });
  } else {
    createEditor();
  }

  // Cross-frame iterable fix: Ensure mention feeds return a local array (iterable in this window)
  normalizeMentionFeeds(options?.mention || null);
}
