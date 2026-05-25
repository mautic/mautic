import { createHtmlElem } from './dom';
import { editorLifecycleMixin } from './editorLifecycle';
import { normalizationMixin } from './normalization';
import { themeConfigMixin } from './themeConfig';

/**
 * Ck5ForGrapesJs Plugin Authorization
 * Developed by: DevFuture.pro
 * @param {Object} editor - GrapesJS Editor instance
 * @param {Object} options - Plugin options
 */
export default (editor, options) => {
  return new Ck5ForGrapesJs(editor, options);
}

/**
 * Main Class for CkEditor 5 integration with GrapesJS
 */
class Ck5ForGrapesJs {

  static counter = 0;

  /**
   * Constructor
   *
   * @param {Object} editor - GrapesJS Editor instance
   * @param {Object} options - Plugin options
   * @param {string} options.ckeditor_module - URL or path to the CKEditor script
   * @param {Array<string>} options.inline - Array of tags to use inline editor for
   * @param {Object} options.inline_options - Options for inline editor
   * @param {Object} options.options - General CKEditor options
   * @param {string} options.licenseKey - CKEditor License Key
   * @param {string} options.toolbar_max_width - Max width for toolbar
   * @param {string} options.inline_toolbar_max_width - Max width for inline toolbar
   * @param {boolean} options.parse_content - Whether to parse content
   * @param {string} options.theme_alias - Theme alias for configuration
   */
  constructor(
    editor,
    opts = {}
  ) {
    const {
      ckeditor_module = '',
      inline = [],
      inline_options,
      options,
      licenseKey,
      toolbar_max_width,
      inline_toolbar_max_width,
      parse_content = false,
      theme_alias,
      reuse_editor,
      content_policy = {}
    } = opts;
    const resolvedInlineOptions = inline_options !== undefined ? inline_options : options;

    const initialThemeAlias = typeof theme_alias === 'string' && theme_alias.trim() ? theme_alias.trim() : null;
    this._managedLinkColorElements = new WeakSet();

    this._Ck5ForGrapesJsData = {
      editor: editor,
      frame: null,
      licenseKey: licenseKey,
      inline: Array.isArray(inline) ? inline.map(item => item.toLowerCase()) : [],
      inline_options: resolvedInlineOptions,
      options: options,
      el: null,
      toolBarMObserver: new MutationObserver(this.onResize.bind(this)),
      elementObserver: new MutationObserver(
        () => {
          this.applyLinkUnderlineNormalization(this.el);
          this.applyIndentationNormalization(this.el);
          this.applyListMarkerNormalization(this.el);
          this.onResize();
          this.editor.refresh();
        }
      ),
      gjsToolBarMObserver: new MutationObserver(this.onResize.bind(this)),
      inlineStyles: null,
      menuMaxWidth: toolbar_max_width,
      inlineMenuMaxWidth: resolvedInlineOptions === options && inline_toolbar_max_width === undefined ? toolbar_max_width : inline_toolbar_max_width,
      inlineMode: true,
      editorContainer: null,
      latestContent: null,
      display: undefined,
      latestClickEvent: null,
      badgableInfo: null,
      toolbarVisibilityInfo: null,
      parseContent: !!parse_content,
      contentPolicy: typeof content_policy === 'object' && content_policy !== null ? content_policy : {},
      reuseEditor: reuse_editor !== undefined ? !!reuse_editor : false,
      fontConfigPromise: null,
      fontFamilyOptions: [],
      fontSizeOptions: [],
      fontStylesheets: [],
      loadedFontStylesheets: [],
      headingOptions: [],
      styleDefinitions: [],
      frameBodyEl: null,
      frameBodyMouseDownHandler: null,
      bodyWrapperEl: null,
      bodyWrapperMouseDownHandler: null,
      bodyWrapperClickHandler: null,
      frameScrollHandler: null,
      frameResizeHandler: null,
      themeAlias: initialThemeAlias,
      themeAliasSource: initialThemeAlias ? 'options' : null,
      themeConfigUrl: null,
      themeConfigUrlAlias: null,
      baseUrl: null
    };
    if (!initialThemeAlias) {
      this.resolveThemeAlias();
    }
    // Create array copy before clear, forEach will not properly work otherwise
    editor.RichTextEditor.getAll().map(item => item.name).forEach(
      item => editor.RichTextEditor.remove(item)
    );
    // Append editor
    editor.setCustomRte(
      {
        enable: this.enable.bind(this),
        disable: this.disable.bind(this),
        focus: this.focus.bind(this),
        getContent: this.getContentForInterface.bind(this),
        parseContent: this.parseContent
      }
    );
    editor.on('frame:load:before', ({ el }) => {
      const doc = el.contentDocument;
      if (!doc.doctype || doc.doctype.nodeName.toLowerCase() !== "html") {
        doc.open();
        doc.write("<!DOCTYPE html>");
        doc.close();
      }
    });
    editor.on('frame:load', ({ el, model, view }) => {
      this.frame = el;
      this.injectEditorModule(ckeditor_module);
      this.ensureFontConfig().then(() => this.injectFontStyles());
      const frameBody = this.frameBody;
      if (frameBody) {
        if (!this._Ck5ForGrapesJsData.frameBodyMouseDownHandler) {
          this._Ck5ForGrapesJsData.frameBodyMouseDownHandler = e => this.latestClickEvent = e;
        }
        frameBody.addEventListener(
          'mousedown',
          this._Ck5ForGrapesJsData.frameBodyMouseDownHandler
        );
        this._Ck5ForGrapesJsData.frameBodyEl = frameBody;
      }

      createHtmlElem(
        'style',
        this.frameDoc.querySelector('head'),
        {
          innerHTML: `p{margin-top:0px !important; margin-bottom: 0px !important;} .ck.ck-sticky-panel__content{ border-bottom-width: 1px !important; } ` +
            `.ck-button.token-tip-active { background-color: #fff9c4 !important; border: 1px solid #ffd54f !important; margin: 5px !important; padding: 5px 10px !important; cursor: default !important; pointer-events: none !important; width: calc(100% - 10px) !important; min-height: auto !important; display: block !important; box-shadow: none !important; } ` +
            `.ck-button.token-tip-active .ck-button__label { color: #333 !important; font-weight: normal !important; white-space: normal !important; text-align: left !important; font-size: 13px !important; } ` +
            `.ck-button.token-tip-active.ck-on, .ck-button.token-tip-active.ck-on:not(.ck-disabled):hover { background: #fff9c4 !important; border: 1px solid #ffd54f !important; box-shadow: none !important; } ` +
            `a:visited { color: #551A8B !important; text-decoration-color: #551A8B !important; border-bottom-color: #551A8B !important; } ` +
            `.ck-content table { table-layout: fixed; } ` +
            `.ck-content td, .ck-content th { resize: both; overflow: auto; min-width: 24px; min-height: 20px; } ` +
            `li::marker { color: inherit; font-size: inherit; font-family: inherit; font-weight: inherit; } ` +
            `` +
            `ol { list-style-type: decimal !important; } ol ol { list-style-type: lower-alpha !important; } ol ol ol { list-style-type: lower-roman !important; }`
        }
      )
    }
    );
  }


  get latestClickEvent() {
    return this._Ck5ForGrapesJsData.latestClickEvent;
  }

  set latestClickEvent(value) {
    this._Ck5ForGrapesJsData.latestClickEvent = value;
  }

  get badgableInfo() {
    return this._Ck5ForGrapesJsData.badgableInfo;
  }

  set badgableInfo(value) {
    this._Ck5ForGrapesJsData.badgableInfo = value;
  }

  get toolbarVisibilityInfo() {
    return this._Ck5ForGrapesJsData.toolbarVisibilityInfo;
  }

  set toolbarVisibilityInfo(value) {
    this._Ck5ForGrapesJsData.toolbarVisibilityInfo = value;
  }

  get display() {
    return this._Ck5ForGrapesJsData.display;
  }

  set display(value) {
    this._Ck5ForGrapesJsData.display = value;
  }

  get latestContent() {
    return this._Ck5ForGrapesJsData.latestContent;
  }

  set latestContent(value) {
    this._Ck5ForGrapesJsData.latestContent = value;
  }

  get editorContainer() {
    return this._Ck5ForGrapesJsData.editorContainer;
  }

  set editorContainer(value) {
    this._Ck5ForGrapesJsData.editorContainer = value;
  }

  get fontFamilyOptions() {
    return this._Ck5ForGrapesJsData.fontFamilyOptions;
  }

  get fontSizeOptions() {
    return this._Ck5ForGrapesJsData.fontSizeOptions;
  }

  get themeAlias() {
    return this.resolveThemeAlias();
  }

  get themeConfigUrl() {
    const alias = this.resolveThemeAlias();
    if (!alias || alias === 'mautic_code_mode') {
      return null;
    }

    if (this._Ck5ForGrapesJsData.themeConfigUrl?.length && this._Ck5ForGrapesJsData.themeConfigUrlAlias === alias) {
      return this._Ck5ForGrapesJsData.themeConfigUrl;
    }

    const url = this.buildThemeConfigUrl(alias);
    this._Ck5ForGrapesJsData.themeConfigUrl = url;
    this._Ck5ForGrapesJsData.themeConfigUrlAlias = alias;

    return url;
  }

  get fontStylesheets() {
    return this._Ck5ForGrapesJsData.fontStylesheets;
  }

  get loadedFontStylesheets() {
    return this._Ck5ForGrapesJsData.loadedFontStylesheets;
  }

  get headingOptions() {
    return this._Ck5ForGrapesJsData.headingOptions;
  }

  get styleDefinitions() {
    return this._Ck5ForGrapesJsData.styleDefinitions;
  }

  get inlineMenuMaxWidth() {
    return this._Ck5ForGrapesJsData.inlineMenuMaxWidth;
  }

  get menuMaxWidth() {
    return this._Ck5ForGrapesJsData.menuMaxWidth;
  }

  get parseContent() {
    return this._Ck5ForGrapesJsData.parseContent;
  }

  get contentPolicy() {
    return this._Ck5ForGrapesJsData.contentPolicy || {};
  }

  get inlineMode() {
    return this._Ck5ForGrapesJsData.inlineMode;
  }

  set inlineMode(value) {
    this._Ck5ForGrapesJsData.inlineMode = value;
  }

  get inlineStyles() {
    return this._Ck5ForGrapesJsData.inlineStyles;
  }

  set inlineStyles(value) {
    this._Ck5ForGrapesJsData.inlineStyles = value;
  }

  get toolBarMObserver() {
    return this._Ck5ForGrapesJsData.toolBarMObserver;
  }

  get gjsToolBarMObserver() {
    return this._Ck5ForGrapesJsData.gjsToolBarMObserver;
  }

  get elementObserver() {
    return this._Ck5ForGrapesJsData.elementObserver;
  }

  get el() {
    return this._Ck5ForGrapesJsData.el;
  }

  set el(value) {
    this._Ck5ForGrapesJsData.el = value;
  }

  get toolbarContainer() {
    return this.inFrameData?.toolbarContainer;
  }

  get licenseKey() {
    return this._Ck5ForGrapesJsData.licenseKey;
  }

  get frame() {
    return this._Ck5ForGrapesJsData.frame;
  }

  set frame(value) {
    this._Ck5ForGrapesJsData.frame = value;
  }

  get frameContentWindow() {
    return this.frame?.contentWindow;
  }

  get inFrameData() {
    return this.frameContentWindow?.grapesjsCkeditorData;
  }

  get frameDoc() {
    return this.frame?.contentDocument;
  }

  get frameBody() {
    return this.frameDoc?.body;
  }

  get editor() {
    return this._Ck5ForGrapesJsData.editor
  }

  get uniqId() {
    return Ck5ForGrapesJs.counter++;
  }

  get ckeditor() {
    return this.inFrameData?.editor;
  }

  get isActive() {
    return this.el !== null;
  }

  get frameScrollY() {
    return this.frameContentWindow ? this.frameContentWindow.scrollY : 0;
  }

  get frameScrollX() {
    return this.frameContentWindow ? this.frameContentWindow.scrollX : 0;
  }

  get inline() {
    return this._Ck5ForGrapesJsData.inline;
  }

  get inlineOptions() {
    return this._Ck5ForGrapesJsData.inline_options;
  }

  get options() {
    return this._Ck5ForGrapesJsData.options;
  }

  get gjsToolbar() {
    const toolBarEl = this.editor.RichTextEditor.getToolbarEl();
    return toolBarEl.parentElement.querySelector('.gjs-toolbar');
  }

  
}

Object.assign(
  Ck5ForGrapesJs.prototype,
  themeConfigMixin,
  normalizationMixin,
  editorLifecycleMixin
);
