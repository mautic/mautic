/**
 * Theme configuration and font helpers for GrapesJS CKEditor.
 */
export const themeConfigMixin = {
  /**
   * Resolves the theme alias from various sources (options, window, DOM).
   *
   * @returns {string|null} The resolved theme alias or null.
   */
  resolveThemeAlias() {
    const currentAlias = this._Ck5ForGrapesJsData.themeAlias;
    if (currentAlias) {
      return currentAlias;
    }

    const windowAlias = this.lookupThemeAliasFromWindow();
    if (windowAlias) {
      return this.setThemeAlias(windowAlias, 'window');
    }

    const formAlias = this.lookupThemeAliasFromForm();
    if (formAlias) {
      return this.setThemeAlias(formAlias, 'form');
    }

    return this._Ck5ForGrapesJsData.themeAlias;
  },

  /**
   * Normalizes the theme alias string.
   *
   * @param {string} value - The raw alias value
   * @returns {string|null} Normalized alias or null
   */
  normalizeThemeAlias(value) {
    if (typeof value !== 'string') {
      return null;
    }

    const trimmed = value.trim();
    return trimmed ? trimmed : null;
  },

  /**
   * Sets the theme alias and updates the internal state.
   *
   * @param {string} value - The new alias value
   * @param {string} source - The source of the alias ('option', 'window', 'form')
   * @returns {string|null} The accepted alias
   */
  setThemeAlias(value, source) {
    const normalized = this.normalizeThemeAlias(value);
    const current = this._Ck5ForGrapesJsData.themeAlias;

    if (normalized === current) {
      if (normalized && source && this._Ck5ForGrapesJsData.themeAliasSource !== source) {
        this._Ck5ForGrapesJsData.themeAliasSource = source;
      }
      return current;
    }

    this._Ck5ForGrapesJsData.themeAlias = normalized;
    this._Ck5ForGrapesJsData.themeAliasSource = normalized ? (source || null) : null;
    this._Ck5ForGrapesJsData.themeConfigUrl = null;
    this._Ck5ForGrapesJsData.themeConfigUrlAlias = null;
    this._Ck5ForGrapesJsData.fontConfigPromise = null;

    return normalized;
  },

  /**
   * Looks up the theme alias from the global window object.
   * Checks Mautic global and mauticThemeAlias.
   *
   * @returns {string|null}
   */
  lookupThemeAliasFromWindow() {
    if (typeof window === 'undefined') {
      return null;
    }

    const { Mautic: mauticGlobal } = window;
    if (mauticGlobal && typeof mauticGlobal.builderTheme === 'string') {
      const alias = mauticGlobal.builderTheme.trim();
      if (alias) {
        return alias;
      }
    }

    if (typeof window.mauticThemeAlias === 'string') {
      const alias = window.mauticThemeAlias.trim();
      if (alias) {
        return alias;
      }
    }

    return null;
  },

  /**
   * Looks up the theme alias from the document DOM (hidden fields or selected theme).
   *
   * @returns {string|null}
   */
  lookupThemeAliasFromForm() {
    if (typeof document === 'undefined') {
      return null;
    }

    const templateField = document.querySelector('[name$="[template]"]');
    if (templateField && typeof templateField.value === 'string') {
      const alias = templateField.value.trim();
      if (alias) {
        return alias;
      }
    }

    const selectedTheme = document.querySelector('.theme-selected [data-theme]');
    if (selectedTheme) {
      const alias = selectedTheme.dataset ? selectedTheme.dataset.theme : null;
      if (typeof alias === 'string' && alias.trim()) {
        return alias.trim();
      }
    }

    return null;
  },

  /**
   * Resolves the base URL for constructing theme config paths.
   *
   * @returns {string} The base URL
   */
  resolveBaseUrl() {
    if (this._Ck5ForGrapesJsData.baseUrl !== null) {
      return this._Ck5ForGrapesJsData.baseUrl;
    }

    let base = null;

    if (typeof mauticBaseUrl !== 'undefined' && typeof mauticBaseUrl === 'string' && mauticBaseUrl.trim()) {
      base = mauticBaseUrl.trim();
    } else if (typeof window !== 'undefined' && typeof window.mauticBaseUrl === 'string' && window.mauticBaseUrl.trim()) {
      base = window.mauticBaseUrl.trim();
    }

    if (!base) {
      base = '/';
    }

    this._Ck5ForGrapesJsData.baseUrl = base;
    return base;
  },

  /**
   * Builds the full URL for the theme configuration JSON file.
   *
   * @param {string} alias - The theme alias
   * @returns {string} The full URL to the config
   */
  buildThemeConfigUrl(alias) {
    const normalizedAlias = encodeURIComponent(alias);
    const baseUrl = this.resolveBaseUrl();
    const normalizedBase = baseUrl.endsWith('/') ? baseUrl : `${baseUrl}/`;
    const relativePath = `themes/${normalizedAlias}/config.json`;

    if (/^https?:\/\//i.test(normalizedBase)) {
      return `${normalizedBase}${relativePath}`;
    }

    if (normalizedBase.startsWith('/')) {
      return `${normalizedBase}${relativePath}`;
    }

    if (typeof window !== 'undefined' && window.location && window.location.origin) {
      try {
        const url = new URL(`${normalizedBase}${relativePath}`, window.location.origin);
        return url.toString();
      } catch (error) {
        console.warn('GrapesJS CKEditor: unable to resolve theme config URL via origin', error);
      }
    }

    return `/${relativePath}`;
  },

  /**
   * Resets the internal font configuration state.
   */
  resetFontConfigState() {
    this._Ck5ForGrapesJsData.fontFamilyOptions = [];
    this._Ck5ForGrapesJsData.fontSizeOptions = [];
    this._Ck5ForGrapesJsData.fontStylesheets = [];
    this._Ck5ForGrapesJsData.headingOptions = [];
    this._Ck5ForGrapesJsData.styleDefinitions = [];
  },

  /**
   * Merges custom font family options with the base configuration.
   *
   * @param {Object} baseConfig - The base font configuration
   * @returns {Object|null} The merged configuration or null
   */
  mergeFontFamilyOptions(baseConfig) {
    const remoteOptions = Array.isArray(this.fontFamilyOptions) ? this.fontFamilyOptions : [];
    if (!remoteOptions.length) {
      return baseConfig || null;
    }

    const config = baseConfig ? { ...baseConfig } : {};
    const normalizedOptions = remoteOptions.reduce((accumulator, option) => {
      if (!this.containsFontOption(accumulator, option)) {
        accumulator.push({ ...option });
      }
      return accumulator;
    }, []);

    if (!normalizedOptions.some(option => this.isDefaultFontOption(option))) {
      normalizedOptions.unshift('default');
    }

    config.options = normalizedOptions;
    config.supportAllValues = false;

    return config;
  },

  /**
   * Merges custom font size options with the base configuration.
   * Keeps supportAllValues enabled so users can type custom numeric values.
   *
   * @param {Object} baseConfig - The base font size configuration
   * @returns {Object}
   */
  mergeFontSizeOptions(baseConfig) {
    const config = baseConfig ? { ...baseConfig } : {};
    const remoteOptions = Array.isArray(this.fontSizeOptions) ? this.fontSizeOptions : [];
    const baseOptions = Array.isArray(config.options) ? config.options : [];
    const mergedOptions = [];

    const registerOption = option => {
      const normalized = this.normalizeFontSizeOption(option);
      if (!normalized) {
        return;
      }

      if (!this.containsFontSizeOption(mergedOptions, normalized)) {
        mergedOptions.push(normalized);
      }
    };

    baseOptions.forEach(registerOption);
    remoteOptions.forEach(registerOption);

    if (mergedOptions.length) {
      config.options = mergedOptions;
    }

    config.supportAllValues = true;

    return config;
  },

  /**
   * Normalizes a font size option into a CKEditor-compatible value.
   *
   * @param {string|number|Object} option
   * @returns {string|number|Object|null}
   */
  normalizeFontSizeOption(option) {
    if (typeof option === 'number' && Number.isFinite(option)) {
      return option;
    }

    if (typeof option === 'string') {
      return this.normalizeOptionalString(option);
    }

    if (!option || typeof option !== 'object') {
      return null;
    }

    return this.normalizeFontSizeObjectOption(option);
  },

  /**
   * Normalizes optional string values by trimming and discarding empties.
   *
   * @param {string} value
   * @returns {string|null}
   */
  normalizeOptionalString(value) {
    if (typeof value !== 'string') {
      return null;
    }

    const normalized = value.trim();
    return normalized || null;
  },

  /**
   * Normalizes object-based font size options.
   *
   * @param {Object} option
   * @returns {Object|null}
   */
  normalizeFontSizeObjectOption(option) {
    if (!option || typeof option !== 'object') {
      return null;
    }

    const normalized = { ...option };

    normalized.model = typeof normalized.model === 'string' ? this.normalizeOptionalString(normalized.model) : normalized.model;
    normalized.title = typeof normalized.title === 'string' ? this.normalizeOptionalString(normalized.title) : normalized.title;

    if (normalized.model === '' || normalized.model === null) {
      delete normalized.model;
    }

    if (normalized.title === '' || normalized.title === null) {
      delete normalized.title;
    }

    if (normalized.model === undefined) {
      if (typeof normalized.title === 'string' && normalized.title) {
        normalized.model = normalized.title;
      } else {
        return null;
      }
    }

    if (normalized.title === undefined) {
      normalized.title = typeof normalized.model === 'string' ? normalized.model : `${normalized.model}`;
    }

    return normalized;
  },

  /**
   * Checks if a font size option collection contains a candidate.
   *
   * @param {Array} collection
   * @param {string|number|Object} candidate
   * @returns {boolean}
   */
  containsFontSizeOption(collection, candidate) {
    return collection.some(item => this.fontSizeOptionEquals(item, candidate));
  },

  /**
   * Compares two font size options.
   *
   * @param {string|number|Object} optionA
   * @param {string|number|Object} optionB
   * @returns {boolean}
   */
  fontSizeOptionEquals(optionA, optionB) {
    const normalize = option => {
      if (typeof option === 'number' && Number.isFinite(option)) {
        return `${option}`;
      }

      if (typeof option === 'string') {
        return option.trim();
      }

      if (option && typeof option === 'object') {
        if (typeof option.model === 'number' && Number.isFinite(option.model)) {
          return `${option.model}`;
        }

        if (typeof option.model === 'string') {
          return option.model.trim();
        }

        if (typeof option.title === 'string') {
          return option.title.trim();
        }
      }

      return null;
    };

    const normalizedA = normalize(optionA);
    const normalizedB = normalize(optionB);

    return normalizedA !== null && normalizedB !== null && normalizedA === normalizedB;
  },

  /**
   * Checks if a font option collection contains a specific candidate.
   *
   * @param {Array} collection - Existing options
   * @param {Object|string} candidate - The option to check
   * @returns {boolean}
   */
  containsFontOption(collection, candidate) {
    return collection.some(item => this.fontOptionEquals(item, candidate));
  },

  /**
   * Compares two font options for equality.
   *
   * @param {Object|string} optionA
   * @param {Object|string} optionB
   * @returns {boolean}
   */
  fontOptionEquals(optionA, optionB) {
    const normalize = option => {
      if (typeof option === 'string') {
        return option.trim();
      }

      if (option && typeof option === 'object') {
        if (typeof option.model === 'string') {
          return option.model.trim();
        }

        if (typeof option.title === 'string' && option.model === undefined) {
          return option.title.trim();
        }
      }

      return null;
    };

    const normalizedA = normalize(optionA);
    const normalizedB = normalize(optionB);

    return normalizedA !== null && normalizedB !== null && normalizedA === normalizedB;
  },

  /**
   * Determines if the option represents the default font.
   *
   * @param {Object|string} option
   * @returns {boolean}
   */
  isDefaultFontOption(option) {
    if (typeof option === 'string') {
      return option.trim().toLowerCase() === 'default';
    }

    if (option && typeof option === 'object' && typeof option.model === 'string') {
      return option.model.trim().toLowerCase() === 'default';
    }

    return false;
  },

  /**
   * Merges custom heading options with the base configuration.
   *
   * @param {Object} baseConfig - The base heading configuration
   * @returns {Object|null}
   */
  mergeHeadingOptions(baseConfig) {
    const remoteOptions = Array.isArray(this.headingOptions) ? this.headingOptions : [];
    const config = baseConfig ? { ...baseConfig } : {};
    const baseOptions = Array.isArray(config.options) ? config.options.map(option => this.cloneHeadingOption(option)) : [];
    const merged = baseOptions.slice();

    remoteOptions.forEach(option => {
      if (!option || typeof option !== 'object') {
        return;
      }

      const normalizedModel = this.normalizeHeadingModel(option.model);
      const index = merged.findIndex(candidate => this.normalizeHeadingModel(candidate && candidate.model) === normalizedModel);
      const cloned = this.cloneHeadingOption(option);

      if (index >= 0) {
        merged[index] = this.mergeHeadingOption(merged[index], cloned);
      } else {
        merged.push(cloned);
      }
    });

    if (!merged.length) {
      merged.push(
        {
          model: 'paragraph',
          title: 'Paragraph',
          class: 'ck-heading_paragraph'
        },
        {
          model: 'heading1',
          title: 'Heading 1',
          class: 'ck-heading_heading1',
          view: 'h1'
        },
        {
          model: 'heading2',
          title: 'Heading 2',
          class: 'ck-heading_heading2',
          view: 'h2'
        },
        {
          model: 'heading3',
          title: 'Heading 3',
          class: 'ck-heading_heading3',
          view: 'h3'
        }
      );
    }

    this.normalizeHeadingOptionViews(merged);

    if (!merged.some(item => this.normalizeHeadingModel(item && item.model) === 'paragraph')) {
      merged.unshift({
        model: 'paragraph',
        title: 'Paragraph',
        class: 'ck-heading_paragraph'
      });
    }

    config.options = merged;
    return config;
  },

  /**
   * Normalizes heading option views to semantic tags (heading1 -> h1, etc).
   *
   * @param {Array} options
   */
  normalizeHeadingOptionViews(options) {
    if (!Array.isArray(options)) {
      return;
    }

    options.forEach(option => {
      if (!option || typeof option !== 'object') {
        return;
      }

      const model = this.normalizeHeadingModel(option.model);
      const match = model.match(/^heading([1-6])$/);
      if (!match) {
        return;
      }

      const expectedTag = `h${match[1]}`;

      if (typeof option.view === 'string') {
        option.view = expectedTag;
        return;
      }

      if (option.view && typeof option.view === 'object') {
        option.view = {
          ...option.view,
          name: expectedTag
        };
        return;
      }

      option.view = expectedTag;
    });
  },

  /**
   * Creates a deep copy of a heading option.
   *
   * @param {Object} option
   * @returns {Object}
   */
  cloneHeadingOption(option) {
    if (!option || typeof option !== 'object') {
      return {
        model: '',
        title: '',
        class: ''
      };
    }

    const cloned = {
      ...option
    };

    if (option.view && typeof option.view === 'object') {
      cloned.view = {
        ...option.view
      };
    }

    return cloned;
  },

  /**
   * Merges two heading options, prioritizing the target but combining views.
   *
   * @param {Object} target
   * @param {Object} source
   * @returns {Object}
   */
  mergeHeadingOption(target, source) {
    if (!target || typeof target !== 'object') {
      return this.cloneHeadingOption(source);
    }

    const merged = {
      ...target,
      ...source
    };

    if (target.view || source.view) {
      merged.view = {
        ...(target && target.view ? target.view : {}),
        ...(source && source.view ? source.view : {})
      };
    }

    return merged;
  },

  /**
   * Normalizes the heading model string.
   *
   * @param {string} model
   * @returns {string}
   */
  normalizeHeadingModel(model) {
    return typeof model === 'string' ? model.trim().toLowerCase() : '';
  },

  /**
   * Merges custom style definitions with the base configuration.
   *
   * @param {Object} baseConfig
   * @returns {Object|null}
   */
  mergeStyleDefinitions(baseConfig) {
    const remoteDefinitions = Array.isArray(this.styleDefinitions) ? this.styleDefinitions : [];
    if (!remoteDefinitions.length) {
      return baseConfig || null;
    }

    const config = baseConfig ? { ...baseConfig } : {};
    const baseDefinitions = Array.isArray(config.definitions) ? config.definitions.map(definition => this.cloneStyleDefinition(definition)) : [];
    const merged = baseDefinitions.slice();

    remoteDefinitions.forEach(definition => {
      if (!definition || typeof definition !== 'object') {
        return;
      }

      const index = merged.findIndex(candidate => this.styleDefinitionEquals(candidate, definition));
      const cloned = this.cloneStyleDefinition(definition);

      if (index >= 0) {
        merged[index] = this.mergeStyleDefinition(merged[index], cloned);
      } else {
        merged.push(cloned);
      }
    });

    config.definitions = merged;
    return config;
  },

  /**
   * Creates a deep copy of a style definition.
   *
   * @param {Object} definition
   * @returns {Object|null}
   */
  cloneStyleDefinition(definition) {
    if (!definition || typeof definition !== 'object') {
      return null;
    }

    const cloned = {
      ...definition
    };

    const classes = this.normalizeClassList(definition.classes);
    if (classes.length) {
      cloned.classes = classes.slice();
    } else {
      delete cloned.classes;
    }

    return cloned;
  },

  /**
   * Merges two style definitions.
   *
   * @param {Object} target
   * @param {Object} source
   * @returns {Object}
   */
  mergeStyleDefinition(target, source) {
    if (!target || typeof target !== 'object') {
      return this.cloneStyleDefinition(source);
    }

    const merged = {
      ...target,
      ...source
    };

    const combinedClasses = this.mergeClassLists(target.classes, source.classes);
    if (combinedClasses.length) {
      merged.classes = combinedClasses;
    } else {
      delete merged.classes;
    }

    return merged;
  },

  /**
   * Checks if two style definitions are equal.
   *
   * @param {Object} definitionA
   * @param {Object} definitionB
   * @returns {boolean}
   */
  styleDefinitionEquals(definitionA, definitionB) {
    if (!definitionA || !definitionB) {
      return false;
    }

    const nameA = this.normalizeDefinitionName(definitionA.name);
    const nameB = this.normalizeDefinitionName(definitionB.name);
    if (nameA && nameB) {
      return nameA === nameB;
    }

    const elementA = typeof definitionA.element === 'string' ? definitionA.element.trim().toLowerCase() : '';
    const elementB = typeof definitionB.element === 'string' ? definitionB.element.trim().toLowerCase() : '';

    if (!elementA || !elementB || elementA !== elementB) {
      return false;
    }

    return this.classListEquals(definitionA.classes, definitionB.classes);
  },

  /**
   * Normalizes a style definition name.
   *
   * @param {string} name
   * @returns {string}
   */
  normalizeDefinitionName(name) {
    return typeof name === 'string' ? name.trim().toLowerCase() : '';
  },

  /**
   * Merges two lists of classes, ensuring uniqueness.
   *
   * @param {Array|string} listA
   * @param {Array|string} listB
   * @returns {Array}
   */
  mergeClassLists(listA, listB) {
    const normalizedA = this.normalizeClassList(listA);
    const normalizedB = this.normalizeClassList(listB);

    const combined = [];
    normalizedA.concat(normalizedB).forEach(item => {
      if (!combined.includes(item)) {
        combined.push(item);
      }
    });

    return combined;
  },

  /**
   * Checks if two class lists are equal.
   *
   * @param {Array|string} listA
   * @param {Array|string} listB
   * @returns {boolean}
   */
  classListEquals(listA, listB) {
    const compareClassNames = (firstClassName, secondClassName) => firstClassName.localeCompare(secondClassName);
    const normalizedA = this.normalizeClassList(listA).sort(compareClassNames);
    const normalizedB = this.normalizeClassList(listB).sort(compareClassNames);

    if (normalizedA.length !== normalizedB.length) {
      return false;
    }

    return normalizedA.every((item, index) => item === normalizedB[index]);
  },

  /**
   * Normalizes a class list into an array of strings.
   *
   * @param {Array|string} value
   * @returns {Array}
   */
  normalizeClassList(value) {
    const result = [];

    const addClass = candidate => {
      if (typeof candidate !== 'string') {
        return;
      }

      const normalized = candidate.trim();
      if (!normalized) {
        return;
      }

      if (!result.includes(normalized)) {
        result.push(normalized);
      }
    };

    if (Array.isArray(value)) {
      value.forEach(addClass);
    } else if (typeof value === 'string') {
      value.split(/\s+/).forEach(addClass);
    }

    return result;
  },

  /**
   * Builds heading options from editor styles.
   *
   * @param {Object} data - Configuration data
   * @returns {Array}
   */
  buildHeadingOptions(data) {
    const styles = data && data.editor && Array.isArray(data.editor.styles) ? data.editor.styles : [];
    if (!styles.length) {
      return [];
    }

    const usedModels = new Set();
    const options = [];

    styles.forEach((style, index) => {
      const option = this.createHeadingOption(style, index, usedModels);
      if (option) {
        usedModels.add(this.normalizeHeadingModel(option.model));
        options.push(option);
      }
    });

    return options;
  },

  /**
   * Creates a heading option from a style definition.
   *
   * @param {Object} style
   * @param {number} index
   * @param {Set} usedModels
   * @returns {Object|null}
   */
  createHeadingOption(style, index, usedModels) {
    if (!style || typeof style !== 'object') {
      return null;
    }

    const element = this.normalizeHeadingElement(style.element || style.tag || style.block);
    if (!element) {
      return null;
    }

    const classes = this.normalizeClassList(style.classes || style.class || style.className);
    const title = this.normalizeStyleName(style.name, element);
    const baseModel = this.buildHeadingModelBase(element);
    if (!baseModel) {
      return null;
    }

    const model = this.ensureUniqueHeadingModel(baseModel, title, classes, usedModels, index);
    const view = this.buildHeadingView(element, classes);
    const dropdownClass = this.buildHeadingDropdownClass(model, element);

    const option = {
      model,
      title,
      class: dropdownClass,
      view
    };

    // Custom heading variants with classes need high priority to be converted before standard headings
    if (classes.length > 0) {
      option.converterPriority = 'high';
    }

    return option;
  },

  /**
   * Normalizes the heading element tag.
   *
   * @param {string} value
   * @returns {string|null}
   */
  normalizeHeadingElement(value) {
    if (typeof value !== 'string') {
      return null;
    }

    const normalized = value.trim().toLowerCase();
    if (!normalized) {
      return null;
    }

    const allowed = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
    return allowed.includes(normalized) ? normalized : null;
  },

  /**
   * Builds the base model name for a heading element.
   *
   * @param {string} element
   * @returns {string|null}
   */
  buildHeadingModelBase(element) {
    if (!element) {
      return null;
    }

    if (element === 'p') {
      return 'paragraph';
    }

    const headingMatch = element.match(/^h([1-6])$/);
    if (headingMatch) {
      return `heading${headingMatch[1]}`;
    }

    return `heading_${element}`;
  },

  /**
   * Ensures the heading model name is unique.
   *
   * @param {string} baseModel
   * @param {string} title
   * @param {Array} classes
   * @param {Set} usedModels
   * @param {number} index
   * @returns {string}
   */
  ensureUniqueHeadingModel(baseModel, title, classes, usedModels, index) {
    if (!usedModels.has(this.normalizeHeadingModel(baseModel))) {
      return baseModel;
    }

    const parts = [];
    const titleSlug = this.slugify(title || '');
    if (titleSlug) {
      parts.push(titleSlug);
    }

    const classSlug = this.slugify(classes.join('-'));
    if (classSlug) {
      parts.push(classSlug);
    }

    if (!parts.length) {
      parts.push(`variant${index}`);
    }

    let candidate = `${baseModel}_${parts.join('_')}`;
    let attempt = 1;
    let normalizedCandidate = this.normalizeHeadingModel(candidate);
    while (usedModels.has(normalizedCandidate) && attempt < 100) {
      candidate = `${baseModel}_${parts.join('_')}_${attempt}`;
      normalizedCandidate = this.normalizeHeadingModel(candidate);
      attempt += 1;
    }

    return candidate;
  },

  /**
   * Builds the view configuration for a heading.
   *
   * @param {string} element
   * @param {Array} classes
   * @returns {Object}
   */
  buildHeadingView(element, classes) {
    const view = {
      name: element
    };

    if (classes.length) {
      view.classes = classes.length === 1 ? classes[0] : classes.slice();
    }

    return view;
  },

  /**
   * Generates the CSS class for the heading dropdown item.
   *
   * @param {string} model
   * @param {string} element
   * @returns {string}
   */
  buildHeadingDropdownClass(model, element) {
    const normalizedModel = this.slugify(model || element || '');
    if (!normalizedModel) {
      return 'ck-heading_custom-option';
    }

    if (this.normalizeHeadingModel(model) === 'paragraph') {
      return 'ck-heading_paragraph';
    }

    return `ck-heading_${normalizedModel}`;
  },

  /**
   * Converts a string into a slug.
   *
   * @param {string} value
   * @returns {string}
   */
  slugify(value) {
    if (typeof value !== 'string') {
      return '';
    }

    const slug = value.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-');

    let start = 0;
    let end = slug.length;

    while (start < end && slug[start] === '-') {
      start += 1;
    }

    while (end > start && slug[end - 1] === '-') {
      end -= 1;
    }

    return slug.slice(start, end);
  },

  /**
   * Normalizes a style name.
   *
   * @param {string} name
   * @param {string} element
   * @returns {string}
   */
  normalizeStyleName(name, element) {
    if (typeof name === 'string' && name.trim()) {
      return name.trim();
    }

    if (typeof element === 'string' && element.trim()) {
      return element.trim().toUpperCase();
    }

    return 'Style';
  },

  /**
   * Builds style definitions from editor configuration.
   *
   * @param {Object} data
   * @returns {Array}
   */
  buildStyleDefinitions(data) {
    const styles = data && data.editor && Array.isArray(data.editor.styles) ? data.editor.styles : [];
    if (!styles.length) {
      return [];
    }

    const definitions = [];

    styles.forEach((style, index) => {
      const definition = this.createStyleDefinition(style, index);
      if (!definition) {
        return;
      }

      const existingIndex = definitions.findIndex(candidate => this.styleDefinitionEquals(candidate, definition));
      if (existingIndex >= 0) {
        definitions[existingIndex] = this.mergeStyleDefinition(definitions[existingIndex], definition);
      } else {
        definitions.push(definition);
      }
    });

    return definitions;
  },

  /**
   * Builds font size options from editor configuration.
   *
   * Supports:
   * - editor.fontSizes: []
   * - editor.font_sizes: []
   * - editor.fontSize: { options: [] } or []
   *
   * @param {Object} data
   * @returns {Array}
   */
  buildFontSizeOptions(data) {
    const editorConfig = data && data.editor && typeof data.editor === 'object' ? data.editor : {};
    const rawFontSizeConfig = editorConfig.fontSizes !== undefined
      ? editorConfig.fontSizes
      : (editorConfig.font_sizes !== undefined ? editorConfig.font_sizes : editorConfig.fontSize);

    if (!rawFontSizeConfig) {
      return [];
    }

    const rawOptions = Array.isArray(rawFontSizeConfig)
      ? rawFontSizeConfig
      : (rawFontSizeConfig && Array.isArray(rawFontSizeConfig.options) ? rawFontSizeConfig.options : []);

    if (!rawOptions.length) {
      return [];
    }

    const normalizedOptions = [];
    rawOptions.forEach(option => {
      const normalized = this.normalizeFontSizeOption(option);
      if (!normalized) {
        return;
      }

      if (!this.containsFontSizeOption(normalizedOptions, normalized)) {
        normalizedOptions.push(normalized);
      }
    });

    return normalizedOptions;
  },

  /**
   * Creates a style definition from a raw style object.
   *
   * @param {Object} style
   * @param {number} index
   * @returns {Object|null}
   */
  createStyleDefinition(style, index) {
    if (!style || typeof style !== 'object') {
      return null;
    }

    const element = this.normalizeStyleElement(style.element || style.tag || style.block);
    if (!element) {
      return null;
    }

    const classes = this.normalizeClassList(style.classes || style.class || style.className);
    const name = this.normalizeStyleName(style.name, element);
    const type = this.isBlockElement(element) ? 'block' : 'inline';

    const definition = {
      name,
      element,
      type
    };

    if (classes.length) {
      definition.classes = classes.slice();
    }

    if (!definition.name) {
      definition.name = `Style ${index + 1}`;
    }

    return definition;
  },

  /**
   * Normalizes the element name for a style.
   *
   * @param {string} value
   * @returns {string|null}
   */
  normalizeStyleElement(value) {
    if (typeof value !== 'string') {
      return null;
    }

    const normalized = value.trim().toLowerCase();
    return normalized || null;
  },

  /**
   * Checks if an element is a block element.
   *
   * @param {string} element
   * @returns {boolean}
   */
  isBlockElement(element) {
    const blockElements = ['address', 'article', 'aside', 'blockquote', 'div', 'footer', 'header', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'nav', 'p', 'section'];
    return blockElements.includes(element);
  },

  /**
   * Ensures the font configuration is loaded.
   * Fetches the theme config if available.
   *
   * @returns {Promise<Array>}
   */
  ensureFontConfig() {
    if (this._Ck5ForGrapesJsData.fontConfigPromise) {
      return this._Ck5ForGrapesJsData.fontConfigPromise;
    }

    const fetchFn = typeof window !== 'undefined' && window.fetch ? window.fetch.bind(window) : null;
    if (!fetchFn) {
      this.resetFontConfigState();
      this._Ck5ForGrapesJsData.fontConfigPromise = Promise.resolve([]);
      return this._Ck5ForGrapesJsData.fontConfigPromise;
    }

    const configUrl = this.themeConfigUrl;
    if (!configUrl) {
      this.resetFontConfigState();
      this._Ck5ForGrapesJsData.fontConfigPromise = Promise.resolve([]);
      return this._Ck5ForGrapesJsData.fontConfigPromise;
    }

    const request = fetchFn(configUrl, { cache: 'no-store', credentials: 'same-origin' })
      .then(response => {
        if (response.status === 404) {
          return null;
        }

        if (!response.ok) {
          throw new Error(`Failed to load theme editor config: ${response.status}`);
        }

        return response.json();
      })
      .then(data => {
        if (!data) {
          console.info('GrapesJS CKEditor: theme config not found, skipping editor font overrides', configUrl);
          this.resetFontConfigState();
          this.injectFontStyles();
          return [];
        }

        const parsed = this.extractEditorConfig(data);
        this._Ck5ForGrapesJsData.fontFamilyOptions = parsed.fontFamilyOptions;
        this._Ck5ForGrapesJsData.fontSizeOptions = parsed.fontSizeOptions;
        this._Ck5ForGrapesJsData.fontStylesheets = parsed.fontStylesheets;
        this._Ck5ForGrapesJsData.headingOptions = parsed.headingOptions;
        this._Ck5ForGrapesJsData.styleDefinitions = parsed.styleDefinitions;
        this.injectFontStyles();
        return parsed.fontFamilyOptions;
      })
      .catch(error => {
        console.warn('GrapesJS CKEditor: unable to load theme editor config', error);
        this.resetFontConfigState();
        this.injectFontStyles();
        return [];
      });

    this._Ck5ForGrapesJsData.fontConfigPromise = request;
    return request;
  },

  /**
   * Extracts editor configuration from the theme config data.
   *
   * @param {Object} data
   * @returns {Object}
   */
  extractEditorConfig(data) {
    const fonts = data && data.editor && Array.isArray(data.editor.fonts) ? data.editor.fonts : [];
    const fontFamilyOptions = [];
    const fontStylesheets = [];

    const registerFontOption = (title, model) => {
      if (typeof model !== 'string' || !model.trim()) {
        return;
      }

      const normalizedModel = model.trim();
      const normalizedTitle = typeof title === 'string' && title.trim() ? title.trim() : normalizedModel;

      const option = {
        title: normalizedTitle,
        model: normalizedModel,
        view: {
          name: 'span',
          styles: {
            'font-family': normalizedModel
          }
        }
      };

      if (!this.containsFontOption(fontFamilyOptions, option)) {
        fontFamilyOptions.push(option);
      }
    };

    fonts.forEach(font => {
      if (!font) {
        return;
      }

      if (typeof font === 'string') {
        registerFontOption(font, font);
        return;
      }

      if (typeof font === 'object') {
        const model = font.font || font['font-family'] || font.family || font.name;
        registerFontOption(font.name, model);

        const sheet = font.url || font.href || font.src;
        if (typeof sheet === 'string' && sheet.trim()) {
          fontStylesheets.push(sheet.trim());
        }
      }
    });

    const uniqueStylesheets = [];
    fontStylesheets.forEach(href => {
      if (!uniqueStylesheets.includes(href)) {
        uniqueStylesheets.push(href);
      }
    });

    const headingOptions = this.buildHeadingOptions(data);
    const styleDefinitions = this.buildStyleDefinitions(data);
    const fontSizeOptions = this.buildFontSizeOptions(data);

    return {
      fontFamilyOptions,
      fontSizeOptions,
      fontStylesheets: uniqueStylesheets,
      headingOptions,
      styleDefinitions
    };
  },

  /**
   * Injects font stylesheets into the editor frame.
   */
  injectFontStyles() {
    const doc = this.frameDoc;
    if (!doc || !doc.head) {
      return;
    }

    const stylesheets = Array.isArray(this.fontStylesheets) ? this.fontStylesheets : [];
    if (!stylesheets.length) {
      return;
    }

    let loaded = Array.isArray(this.loadedFontStylesheets) ? this.loadedFontStylesheets : [];

    stylesheets.forEach(href => {
      if (typeof href !== 'string') {
        return;
      }

      const trimmedHref = href.trim();
      if (!trimmedHref) {
        return;
      }

      if (loaded.includes(trimmedHref)) {
        return;
      }

      const alreadyPresent = Array.from(doc.querySelectorAll('link[rel="stylesheet"]')).some(link => {
        return link.getAttribute('href') === trimmedHref || link.href === trimmedHref;
      });

      if (alreadyPresent) {
        loaded.push(trimmedHref);
        return;
      }

      const link = doc.createElement('link');
      link.rel = 'stylesheet';
      link.href = trimmedHref;
      doc.head.appendChild(link);
      loaded.push(trimmedHref);
    });

    this._Ck5ForGrapesJsData.loadedFontStylesheets = loaded;
  }
};
