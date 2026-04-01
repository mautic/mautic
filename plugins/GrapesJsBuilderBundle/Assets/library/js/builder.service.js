import grapesjs from 'grapesjs';
import grapesjsmjml from 'grapesjs-mjml';
import grapesjsnewsletter from 'grapesjs-preset-newsletter';
import grapesjswebpage from 'grapesjs-preset-webpage';
import grapesjsblocksbasic from 'grapesjs-blocks-basic';
import grapesjscomponentcountdown from 'grapesjs-component-countdown';
import grapesjsnavbar from 'grapesjs-navbar';
import grapesjscustomcode from 'grapesjs-custom-code';
import grapesjstouch from 'grapesjs-touch';
import grapesjstuiimageeditor from 'grapesjs-tui-image-editor';
import grapesjsstylebg from 'grapesjs-style-bg';
import grapesjspostcss from 'grapesjs-parser-postcss';
import grapesjsckeditor from './plugins/grapesjs-ckeditor';
import contentService from 'grapesjs-preset-mautic/dist/content.service';
import grapesjsmautic from 'grapesjs-preset-mautic';
import editorFontsService from 'grapesjs-preset-mautic/dist/editorFonts/editorFonts.service';
import StorageService from './storage.service';

// for local dev
// import contentService from '../../../../../../grapesjs-preset-mautic/src/content.service';
// import grapesjsmautic from '../../../../../../grapesjs-preset-mautic/src';

import CodeModeButton from './codeMode/codeMode.button';
import CompCopyPaste from './commands/compCopyPaste';
import MjmlService from 'grapesjs-preset-mautic/dist/mjml/mjml.service';
import MjmlStylesService from './mjmlStyles.service';
import EditorStateService from './editorState.service';

export default class BuilderService {
  editor;

  storageService;

  assetService;

  editorStateField;

  pendingEditorState;

  context;

  editorStateLoaded;

  editorStateService;

  typographySector;

  typographySectorInitialized;

  typographySectorTimeout;

  optimisticLockVersion;

  /**
   * @param {AssetService} assetService
   */
  constructor(assetService) {
    this.assetService = assetService;
    this.editorStateField = null;
    this.pendingEditorState = null;
    this.context = null;
    this.editorStateLoaded = false;
    this.editorStateService = new EditorStateService({
      setFieldValue: (value) => this.setEditorStateFieldValue(value),
      setContextReset: (context, resetEditorState) => this.setContextEditorStateReset(context, resetEditorState),
    });
    this.typographySector = null;
    this.typographySectorInitialized = false;
    this.typographySectorTimeout = null;
    this.optimisticLockVersion = null;

    this.patchMjmlService();
  }

  patchMjmlService() {
    if (!MjmlService || typeof MjmlService.getEditorMjmlContent !== 'function') {
      return;
    }

    if (MjmlService.__gjsBuilderListStylesPatched) {
      return;
    }

    const originalGetEditorMjmlContent = MjmlService.getEditorMjmlContent.bind(MjmlService);

    MjmlService.getEditorMjmlContent = (editor) => {
      const mjml = originalGetEditorMjmlContent(editor);
      return MjmlStylesService.normalizeListStyles(mjml);
    };

    MjmlService.__gjsBuilderListStylesPatched = true;
  }

  /**
   * Initialize GrapesJsBuilder
   *
   * @param object
   */
  getContext(object) {
    const isPage = object === 'page';
    const formName = isPage ? 'page' : 'emailform';
    const form = document.querySelector(`form[name="${formName}"]`);
    const builderRouteContext = this.getBuilderRouteContext();
    const sessionFieldId = isPage ? 'page_sessionId' : 'emailform_sessionId';
    const sessionInput = document.getElementById(sessionFieldId);
    const sessionValue = sessionInput ? sessionInput.value : '';
    const fallbackEntityId = sessionValue && !sessionValue.startsWith('new_') ? sessionValue : null;

    const objectType = builderRouteContext
      ? builderRouteContext.objectType
      : this.getDefaultObjectType(isPage);
    const entityId = builderRouteContext
      ? builderRouteContext.entityId
      : fallbackEntityId;
    const sessionId = builderRouteContext
      ? builderRouteContext.objectId
      : this.normalizeSessionId(sessionValue);

    return {
      form,
      formName,
      sessionId,
      entityId,
      objectType,
      editorStateUrl: builderRouteContext ? builderRouteContext.editorStateUrl : null,
      resetEditorState: form?.dataset?.grapesjsbuilderReset === 'true',
    };
  }

  getDefaultObjectType(isPage) {
    return isPage ? 'page' : 'email';
  }

  normalizeSessionId(sessionValue) {
    return sessionValue || null;
  }

  getBuilderRouteContext() {
    const builderUrlInput = document.getElementById('builder_url');
    const builderUrlValue = builderUrlInput && typeof builderUrlInput.value === 'string'
      ? builderUrlInput.value
      : '';

    if (!builderUrlValue) {
      return null;
    }

    let parsedBuilderUrl;

    try {
      parsedBuilderUrl = new URL(builderUrlValue, window.location.origin);
    } catch (error) {
      console.warn('Unable to parse GrapesJS builder URL', error);
      return null;
    }

    const routeMatch = parsedBuilderUrl.pathname.match(/\/grapesjsbuilder\/(page|email)\/([^/]+)\/?$/);
    if (!routeMatch) {
      return null;
    }

    const [, objectType, objectId] = routeMatch;
    const normalizedObjectId = decodeURIComponent(objectId);
    const entityId = normalizedObjectId && !normalizedObjectId.startsWith('new')
      ? normalizedObjectId
      : null;
    const normalizedPathname = parsedBuilderUrl.pathname.replace(/\/$/, '');

    return {
      objectType,
      objectId: normalizedObjectId,
      entityId,
      editorStateUrl: `${parsedBuilderUrl.origin}${normalizedPathname}/editor-state`,
    };
  }

  updateBuilderUrlForEntity(objectType, entityId) {
    const builderUrlInput = document.getElementById('builder_url');
    if (!builderUrlInput || typeof builderUrlInput.value !== 'string' || !builderUrlInput.value.length) {
      return;
    }

    try {
      const parsedBuilderUrl = new URL(builderUrlInput.value, window.location.origin);
      const normalizedPathname = parsedBuilderUrl.pathname
        .replace(/\/s\/(pages|emails)\/builder\/[^/]+\/?$/, `/s/grapesjsbuilder/${objectType}/${entityId}`)
        .replace(/\/s\/grapesjsbuilder\/(page|email)\/[^/]+\/?$/, `/s/grapesjsbuilder/${objectType}/${entityId}`)
        .replace(/\/$/, '');

      parsedBuilderUrl.pathname = normalizedPathname;
      builderUrlInput.value = parsedBuilderUrl.toString();
    } catch (error) {
      console.warn('Unable to update GrapesJS builder URL after entity save', error);
    }
  }

  syncContextAfterFirstSave(requestUrl, response) {
    if (!response || typeof response !== 'object' || typeof response.route !== 'string') {
      return;
    }

    const requestLastPart = typeof requestUrl === 'string' ? requestUrl.split('/').pop() : null;
    if (requestLastPart !== 'new') {
      return;
    }

    const routeMatch = response.route.match(/\/(pages|emails)\/edit\/(\d+)(?:\/|$)/);
    if (!routeMatch) {
      return;
    }

    const [, rawType, rawEntityId] = routeMatch;
    const objectType = rawType === 'pages' ? 'page' : 'email';
    const entityId = String(rawEntityId);

    this.updateBuilderUrlForEntity(objectType, entityId);

    const sessionFieldId = objectType === 'page' ? 'page_sessionId' : 'emailform_sessionId';
    const sessionField = document.getElementById(sessionFieldId);
    if (sessionField) {
      sessionField.value = entityId;
    }

    if (this.context) {
      this.context.objectType = objectType;
      this.context.objectId = entityId;
      this.context.entityId = entityId;
      this.context.resetEditorState = false;
      if (this.context.form) {
        this.context.form.dataset.grapesjsbuilderReset = 'false';
      }

      const refreshedRouteContext = this.getBuilderRouteContext();
      this.context.editorStateUrl = refreshedRouteContext ? refreshedRouteContext.editorStateUrl : null;
    }
  }

  syncOptimisticLockVersionFromResponse(response) {
    if (!response || typeof response !== 'object') {
      return;
    }

    let normalizedVersion = null;
    const beforeSaveVersion = this.optimisticLockVersion || this.resolveOptimisticLockVersion();
    const beforeSaveVersionNumber = Number.parseInt(beforeSaveVersion, 10);
    const isSuccessfulEditResponse = !response.validationError
      && typeof response.route === 'string'
      && /\/(pages|emails)\/edit\/\d+(?:\/|$)/.test(response.route);

    const responseVersion = response.version ?? response.data?.version;
    if (typeof responseVersion === 'string' || typeof responseVersion === 'number') {
      normalizedVersion = `${responseVersion}`.trim();
    }

    if (!normalizedVersion && typeof response.newContent === 'string') {
      const selectors = [
        '#page_version',
        '#emailform_version',
        'input[name="page[version]"]',
        'input[name="emailform[version]"]',
      ];
      const parsedResponseContent = mQuery(response.newContent);
      let updatedVersion;

      selectors.some((selector) => {
        const candidateVersion = parsedResponseContent.find(selector).val();
        if (typeof candidateVersion === 'undefined' || candidateVersion === null) {
          return false;
        }

        updatedVersion = candidateVersion;
        return true;
      });

      if (typeof updatedVersion === 'string' || typeof updatedVersion === 'number') {
        normalizedVersion = `${updatedVersion}`.trim();
      }
    }

    const normalizedVersionNumber = Number.parseInt(normalizedVersion, 10);

    if (
      normalizedVersion
      && isSuccessfulEditResponse
      && !Number.isNaN(beforeSaveVersionNumber)
      && !Number.isNaN(normalizedVersionNumber)
      && normalizedVersionNumber === beforeSaveVersionNumber
    ) {
      normalizedVersion = `${beforeSaveVersionNumber + 1}`;
    }

    if (!normalizedVersion && isSuccessfulEditResponse && !Number.isNaN(beforeSaveVersionNumber) && beforeSaveVersionNumber > 0) {
      normalizedVersion = `${beforeSaveVersionNumber + 1}`;
    }

    if (!normalizedVersion) {
      return;
    }

    const currentFormVersionField = this.getOptimisticLockField()
      || (this.context?.formName ? document.getElementById(`${this.context.formName}_version`) : null);

    if (currentFormVersionField) {
      currentFormVersionField.value = normalizedVersion;
    }

    this.optimisticLockVersion = normalizedVersion;
  }

  patchApplyFormCommandForSubmitGuard() {
    if (!this.editor?.Commands || typeof this.editor.Commands.get !== 'function') {
      return;
    }

    const command = this.editor.Commands.get('preset-mautic:apply-form');
    if (!command || typeof command.run !== 'function' || command.__gjsSubmitGuardPatched) {
      return;
    }

    const originalRun = command.run.bind(command);

    command.run = (...args) => {
      if (typeof MauticVars !== 'undefined' && MauticVars.formSubmitInProgress) {
        return;
      }

      if (typeof MauticVars !== 'undefined') {
        MauticVars.formSubmitInProgress = true;
      }

      try {
        return originalRun(...args);
      } catch (error) {
        if (typeof MauticVars !== 'undefined') {
          MauticVars.formSubmitInProgress = false;
        }
        throw error;
      }
    };

    command.__gjsSubmitGuardPatched = true;
  }

  setEditorStateFieldValue(value) {
    if (!this.editorStateField) {
      return;
    }

    this.editorStateField.value = value;
  }

  setContextEditorStateReset(context, resetEditorState) {
    if (!context) {
      return;
    }

    if (context.form) {
      context.form.dataset.grapesjsbuilderReset = resetEditorState ? 'true' : 'false';
    }

    context.resetEditorState = resetEditorState;
  }

  getOptimisticLockField() {
    if (!this.context?.form || !this.context?.formName) {
      return null;
    }

    return this.context.form.querySelector(`[name="${this.context.formName}[version]"]`);
  }

  cacheOptimisticLockVersion() {
    const versionField = this.getOptimisticLockField()
      || (this.context?.formName
        ? document.getElementById(`${this.context.formName}_version`)
        : null);
    if (!versionField) {
      return;
    }

    const rawValue = typeof versionField.value === 'string' ? versionField.value.trim() : `${versionField.value || ''}`.trim();
    if (rawValue) {
      const cachedValue = typeof this.optimisticLockVersion === 'string'
        ? this.optimisticLockVersion.trim()
        : `${this.optimisticLockVersion || ''}`.trim();

      const rawValueNumber = Number.parseInt(rawValue, 10);
      const cachedValueNumber = Number.parseInt(cachedValue, 10);

      if (!Number.isNaN(rawValueNumber) && !Number.isNaN(cachedValueNumber)) {
        this.optimisticLockVersion = rawValueNumber >= cachedValueNumber ? rawValue : cachedValue;
      } else {
        this.optimisticLockVersion = rawValue;
      }
    }
  }

  resolveOptimisticLockVersion() {
    const byName = this.getOptimisticLockField();
    const byId = this.context?.formName
      ? document.getElementById(`${this.context.formName}_version`)
      : null;
    const fallbackInForm = this.context?.form
      ? this.context.form.querySelector('input[name$="[version]"]')
      : null;

    const candidates = [byName, byId, fallbackInForm];
    for (let index = 0; index < candidates.length; index += 1) {
      const candidate = candidates[index];
      if (!candidate) {
        continue;
      }

      const value = typeof candidate.value === 'string'
        ? candidate.value.trim()
        : `${candidate.value || ''}`.trim();

      if (value) {
        return value;
      }
    }

    if (this.optimisticLockVersion) {
      return this.optimisticLockVersion;
    }

    return '1';
  }

  ensureOptimisticLockVersion() {
    if (!this.context?.form || !this.context?.formName) {
      return;
    }

    const resolvedVersion = this.resolveOptimisticLockVersion();
    this.optimisticLockVersion = resolvedVersion;

    let versionField = this.getOptimisticLockField();

    if (!versionField) {
      versionField = document.createElement('input');
      versionField.type = 'hidden';
      versionField.name = `${this.context.formName}[version]`;
      versionField.id = `${this.context.formName}_version`;
      versionField.value = resolvedVersion;
      this.context.form.appendChild(versionField);
      return;
    }

    const currentValue = typeof versionField.value === 'string' ? versionField.value.trim() : `${versionField.value || ''}`.trim();

    if (!currentValue && resolvedVersion) {
      versionField.value = resolvedVersion;
      return;
    }

    const cachedValue = typeof this.optimisticLockVersion === 'string'
      ? this.optimisticLockVersion.trim()
      : `${this.optimisticLockVersion || ''}`.trim();

    if (currentValue) {
      const currentValueNumber = Number.parseInt(currentValue, 10);
      const cachedValueNumber = Number.parseInt(cachedValue, 10);

      if (!Number.isNaN(currentValueNumber) && !Number.isNaN(cachedValueNumber) && cachedValueNumber > currentValueNumber) {
        versionField.value = cachedValue;
        this.optimisticLockVersion = cachedValue;
        return;
      }

      this.optimisticLockVersion = currentValue;
    }
  }

  loadEditorState(editorState) {
    if (!editorState || !this.editor || typeof this.editor.loadProjectData !== 'function') {
      return;
    }

    try {
      this.editor.loadProjectData(editorState);
      this.editorStateLoaded = true;
    } catch (error) {
      console.warn('Unable to load GrapesJS editor state into the editor', error);
    }
  }

  persistEditorState() {
    this.ensureOptimisticLockVersion();

    if (!this.editorStateField || !this.editor || typeof this.editor.getProjectData !== 'function') {
      return;
    }

    try {
      const editorState = this.editor.getProjectData();
      if (editorState && typeof editorState === 'object') {
        const serialized = JSON.stringify(editorState);
        this.setEditorStateFieldValue(serialized);
        this.pendingEditorState = editorState;
        this.editorStateLoaded = true;
        if (this.context?.form) {
          this.context.form.dataset.grapesjsbuilderReset = 'false';
        }
        if (this.context) {
          this.context.resetEditorState = false;
        }
      } else {
        this.setEditorStateFieldValue('');
        this.pendingEditorState = null;
        this.editorStateLoaded = false;
        if (this.context?.form) {
          this.context.form.dataset.grapesjsbuilderReset = 'false';
        }
        if (this.context) {
          this.context.resetEditorState = false;
        }
      }
    } catch (error) {
      console.warn('Unable to collect GrapesJS editor state from the editor', error);
    }
  }

  /**
   * Initialize GrapesJsBuilder
   *
   * @param object
   */
  setListeners() {
    if (!this.editor) {
      throw Error('No editor found');
    }

    this.patchApplyFormCommandForSubmitGuard();

    // Why would we not want to keep the history?
    //
    // this.editor.on('load', () => {
    //   const um = this.editor.UndoManager;
    //   // Clear stack of undo/redo
    //   um.clear();
    // });

    const keymaps = this.editor.Keymaps;
    let allKeymaps;

    if (mauticEditorFonts) {
      this.editor.on('load', () => editorFontsService.loadEditorFonts(this.editor));
    }

    this.editor.on('modal:open', () => {
      // Save all keyboard shortcuts
      allKeymaps = { ...keymaps.getAll() };

      // Remove keyboard shortcuts to prevent launch behind popup
      keymaps.removeAll();
    });

    this.editor.on('modal:close', () => {
      // ReMap keyboard shortcuts on modal close
      Object.keys(allKeymaps).map((objectKey) => {
        const shortcut = allKeymaps[objectKey];

        keymaps.add(shortcut.id, shortcut.keys, shortcut.handler);
        return keymaps;
      });
    });

    this.editor.on('asset:remove', (response) => {
      // Delete file on server
      mQuery.ajax({
        url: this.assetService.getDeletePath(),
        data: { filename: response.getFilename() },
      });
    });

    this.editor.on('asset:upload:error', (error) => {
      Mautic.setFlashes(Mautic.addErrorFlashMessage(error));
    });

    this.editor.on('asset:open', () => {
      const editor = this.editor;
      const assetsService = this.assetService;
      const assetsContainer = document.querySelector('.gjs-am-assets');
      const $assetsSpinner = document.createElement('div');
      $assetsSpinner.className = 'gjs-assets-spinner';
      $assetsSpinner.innerHTML = '<i class="ri-loader-3-line ri-spin"></i>';

      if (assetsContainer) {
        let isLoading = false;

        const loadNextPage = async () => {
          if (isLoading) return;
          isLoading = true;
          assetsContainer.appendChild($assetsSpinner);

          try {
            const result = await assetsService.getAssetsNextPageXhr();
            if (result) {
              const assetManager = editor.AssetManager;
              const currentAssets = assetManager.getAll().models;
              const newAssets = result.data;

              // Combine current assets with new assets
              const combinedAssets = [...currentAssets, ...newAssets];

              // Reset the entire collection with combined assets
              assetManager.getAll().reset(combinedAssets);
              assetManager.render();
            }
          } catch (error) {
            console.error('Error loading next page of assets:', error);
          } finally {
            isLoading = false;
          }
        };

        assetsContainer.addEventListener('scroll', function () {
          const hasScrolledToBottom = this.scrollTop + this.clientHeight >= this.scrollHeight - 5;
          if (hasScrolledToBottom && !assetsService.hasLoadedAllAssets()) {
            loadNextPage();
          }
        });
      } else {
        console.warn('Element with class "gjs-am-assets" not found');
      }
    });

    const triggerBuilderHide = () => {
      this.persistEditorState();
      // trigger hide event on DOM element
      mQuery('.builder').trigger('builder:hide', [this.editor]);
      // trigger hide event on editor instance
      this.editor.trigger('hide');
    };
    
    if (this.context?.form) {
      const $form = mQuery(this.context.form);
      $form
        .off('submit.grapesjsbuilder form-pre-serialize.grapesjsbuilder submit:success.grapesjsbuilder')
        .on('submit.grapesjsbuilder', () => this.persistEditorState())
        .on('form-pre-serialize.grapesjsbuilder', () => this.persistEditorState())
        .on('submit:success.grapesjsbuilder', (event, requestUrl, response) => {
          this.syncOptimisticLockVersionFromResponse(response);
          this.syncContextAfterFirstSave(requestUrl, response);
          this.cacheOptimisticLockVersion();
        });
    }
    this.editor.on('run:mautic-editor-page-html-close', triggerBuilderHide);
    this.editor.on('run:mautic-editor-email-html-close', triggerBuilderHide);
    this.editor.on('run:mautic-editor-email-mjml-close', triggerBuilderHide);
    this.editor.on('run:preset-mautic:apply-form', () => this.persistEditorState());

    this.editor.on('load', () => this.setupTypographySectorVisibility());
    this.setupTypographySectorVisibility();

    // add offset to flashes container for better UI visibility when builder is on
    this.editor.on('show', () => mQuery('#flashes').addClass('alert-offset'));
    this.editor.on('hide', () => mQuery('#flashes').removeClass('alert-offset'));
  }

  /**
   * Initialize the grapesjs build in the
   * correct mode
   */
  initGrapesJS(object) {
    this.context = this.getContext(object);
    this.cacheOptimisticLockVersion();
    this.editorStateField = this.editorStateService.ensureEditorStateField(this.context);
    this.pendingEditorState = null;
    this.editorStateLoaded = false;

    const editorStatePrefetch = this.context.resetEditorState
      ? null
      : this.editorStateService.prefillEditorStateField(this.context);

    // grapesjs-custom-plugins: add globally defined mautic-grapesjs-plugins using name as pluginId for the plugin-function
    if (window.MauticGrapesJsPlugins) {
      window.MauticGrapesJsPlugins.forEach((item) => {
        if (!item.name) {
          console.warn('A name is required for Mautic-GrapesJs plugins in window.MauticGrapesJsPlugins. Registration skipped!');
          return;
        }

        if (typeof item.plugin !== 'function') {
          console.warn('The Mautic-GrapesJs plugin must be a function in window.MauticGrapesJsPlugins. Registration skipped!');
          return;
        }

        grapesjs.plugins.add(item.name, item.plugin);
      });
    }

    // disable mautic global shortcuts
    Mousetrap.reset();
    if (object === 'page') {
      this.editor = this.initPage();
    } else if (object === 'emailform') {
      if (MjmlService.getOriginalContentMjml()) {
        this.editor = this.initEmailMjml();
      } else {
        this.editor = this.initEmailHtml();
      }
    } else {
      throw Error(`Not supported builder type: ${object}`);
    }

    this.editor.on('load', () => {
      if (!this.editorStateLoaded && this.pendingEditorState) {
        this.loadEditorState(this.pendingEditorState);
      }
    });

    if (typeof editorStatePrefetch?.then === 'function') {
      editorStatePrefetch.then((editorState) => {
        if (editorState && typeof editorState === 'object') {
          this.pendingEditorState = editorState;
          if (!this.editorStateLoaded && this.editor) {
            this.loadEditorState(editorState);
          }
        }
      });
    }

    if (this.context.resetEditorState) {
      this.setEditorStateFieldValue('');
      this.pendingEditorState = null;
      if (this.context.form) {
        this.context.form.dataset.grapesjsbuilderReset = 'false';
      }
      this.context.resetEditorState = false;
    }

    // add code mode button
    // @todo: only show button if configured: sourceEdit: 1,
    const codeModeButton = new CodeModeButton(this.editor);
    codeModeButton.addCommand();
    codeModeButton.addButton();

    /**
     * Add command that will allow users
     * to copy paste component across tabs
     */
    new CompCopyPaste(this.editor).addCommand();

    this.storageService = new StorageService(this.editor, object);
    this.setListeners();
  }

  static getMauticConf(mode) {
    return {
      mode,
    };
  }

  static getCkEditorContentPolicy() {
    const defaultPolicy = {
      allowTables: true,
      allowImages: false,
      mapRightIndentToHanging: true,
      // Preserve Word inline formatting (font-size, font-family, colors, etc.) in exported HTML.
      stripWordInlineStyles: false,
    };

    const globalPolicy = (typeof window !== 'undefined' && window.MauticGrapesJsCkEditorContentPolicy && typeof window.MauticGrapesJsCkEditorContentPolicy === 'object')
      ? window.MauticGrapesJsCkEditorContentPolicy
      : {};

    return {
      ...defaultPolicy,
      ...globalPolicy,
    };
  }

  static getCkeConf(tokenCallback) {
    const contentPolicy = BuilderService.getCkEditorContentPolicy();
    const blockToolbar = ['undo', 'redo', '|', 'bold', 'italic', 'underline', 'strikethrough', '|', 'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|', 'alignment', 'outdent', 'indent', '|', 'bulletedList', 'numberedList', '|', 'link'];

    if (contentPolicy.allowTables !== false) {
      blockToolbar.push('|', 'insertTable');
    }

    blockToolbar.push('|', 'TokenPlugin', 'heading');

    const blockConfig = Mautic.GetCkEditorConfigOptions(blockToolbar, tokenCallback) || {};

    blockConfig.licenseKey = 'GPL';
    blockConfig.mauticContentPolicy = contentPolicy;

    const fontFamilyConfig = blockConfig.fontFamily ? { ...blockConfig.fontFamily } : {};
    fontFamilyConfig.supportAllValues = true;
    blockConfig.fontFamily = fontFamilyConfig;

    const htmlSupport = blockConfig.htmlSupport ? { ...blockConfig.htmlSupport } : {};
    htmlSupport.allow = [
      {
        name: /.*/,
        attributes: true,
        classes: true,
        styles: true,
      },
    ];
    htmlSupport.fullPage = {
      ...(htmlSupport.fullPage || {}),
      allowRenderStylesFromHead: true,
    };
    blockConfig.htmlSupport = htmlSupport;

    if (blockConfig.toolbar) {
      blockConfig.toolbar = {
        ...blockConfig.toolbar,
        items: blockToolbar,
        shouldNotGroupWhenFull: true,
      };
    }

    const linkConfig = blockConfig.link ? { ...blockConfig.link } : {};
    const decorators = linkConfig.decorators ? { ...linkConfig.decorators } : {};
    const existingOpenInNewTab = decorators.openInNewTab && typeof decorators.openInNewTab === 'object'
      ? decorators.openInNewTab
      : {};

    const normalizeRel = (value) => {
      const tokens = new Set();

      if (typeof value === 'string') {
        value.split(/\s+/).forEach((token) => {
          const trimmed = token.trim();
          if (trimmed) {
            tokens.add(trimmed);
          }
        });
      }

      tokens.add('noopener');
      tokens.add('noreferrer');

      return Array.from(tokens).join(' ');
    };

    const mode = typeof existingOpenInNewTab.mode === 'string' ? existingOpenInNewTab.mode : 'manual';
    const label = typeof existingOpenInNewTab.label === 'string' && existingOpenInNewTab.label.trim()
      ? existingOpenInNewTab.label
      : 'Open in new tab';
    const attributes = {
      ...(existingOpenInNewTab.attributes || {}),
      target: '_blank',
      rel: normalizeRel(existingOpenInNewTab.attributes?.rel),
    };

    const openInNewTabDecorator = {
      ...existingOpenInNewTab,
      mode,
      label,
      attributes,
    };

    if (mode === 'manual') {
      openInNewTabDecorator.defaultValue = true;
    }

    decorators.openInNewTab = openInNewTabDecorator;
    linkConfig.decorators = decorators;
    blockConfig.link = linkConfig;

    if (contentPolicy.allowTables !== false) {
      const tableConfig = blockConfig.table ? { ...blockConfig.table } : {};
      const existingToolbar = Array.isArray(tableConfig.contentToolbar) ? tableConfig.contentToolbar : [];
      const fullTableToolbar = [
        'tableColumn',
        'tableRow',
        'mergeTableCells',
        'toggleTableCaption',
        'tableCellProperties',
        'tableProperties',
      ];

      tableConfig.contentToolbar = Array.from(new Set([...existingToolbar, ...fullTableToolbar]));
      blockConfig.table = tableConfig;
    }

    if (contentPolicy.allowImages === false) {
      const imagePlugins = [
        'AutoImage',
        'Image',
        'ImageBlock',
        'ImageCaption',
        'ImageInline',
        'ImageInsert',
        'ImageResize',
        'ImageStyle',
        'ImageToolbar',
        'ImageUpload',
        'Base64UploadAdapter',
        'CKFinderUploadAdapter',
        'CKFinder',
        'EasyImage',
      ];

      const removePlugins = Array.isArray(blockConfig.removePlugins) ? [...blockConfig.removePlugins] : [];
      blockConfig.removePlugins = Array.from(new Set([...removePlugins, ...imagePlugins]));
    }

    if (blockConfig.dynamicToken) {
      blockConfig.dynamicToken = [
        {
          id: 'token-tip',
          name: "Tip: Type '{' directly in the editor to search for tokens!"
        },
        ...blockConfig.dynamicToken.filter(t => t.id !== 'token-tip')
      ];
    }

    return blockConfig;
  }

  static getCkeditorModuleUrl() {
    const baseUrl = typeof mauticBaseUrl !== 'undefined' ? mauticBaseUrl : '';

    return `${baseUrl}media/libraries/ckeditor/ckeditor.js`;
  }

  static getInlineElements() {
    return ['span', 'a', 'button', 'label', 'strong', 'em', 'small', 'sup', 'sub', 'h1', 'h2', 'h3', 'h4', 'h5'];
  }

  static buildInlineCkeConf(baseOptions) {
    const contentPolicy = BuilderService.getCkEditorContentPolicy();
    const inlineToolbar = ['undo', 'redo', '|', 'bold', 'italic', 'underline', 'strikethrough', '|', 'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|', 'link'];

    if (contentPolicy.allowTables !== false) {
      inlineToolbar.push('|', 'insertTable');
    }

    inlineToolbar.push('|', 'removeFormat', '|', 'TokenPlugin', 'heading');

    const options = baseOptions ? { ...baseOptions } : {};
    const toolbarConfig = baseOptions?.toolbar ? { ...baseOptions.toolbar } : {};

    toolbarConfig.items = inlineToolbar;
    toolbarConfig.shouldNotGroupWhenFull = true;
    options.toolbar = toolbarConfig;

    const fontFamilyConfig = options.fontFamily ? { ...options.fontFamily } : {};
    fontFamilyConfig.supportAllValues = true;
    options.fontFamily = fontFamilyConfig;

    const htmlSupport = options.htmlSupport ? { ...options.htmlSupport } : {};
    htmlSupport.allow = [
      {
        name: /.*/,
        attributes: true,
        classes: true,
        styles: true,
      },
    ];
    htmlSupport.fullPage = {
      ...(htmlSupport.fullPage || {}),
      allowRenderStylesFromHead: true,
    };
    options.htmlSupport = htmlSupport;

    if (options.dynamicToken) {
      options.dynamicToken = [
        {
          id: 'token-tip',
          name: "Tip: Type '{' directly in the editor to search for tokens!"
        },
        ...options.dynamicToken.filter(t => t.id !== 'token-tip')
      ];
    }

    return options;
  }

  static getActiveThemeAlias() {
    if (typeof window === 'undefined' || typeof document === 'undefined') {
      return null;
    }

    const { Mautic: mauticGlobal } = window;
    if (mauticGlobal && typeof mauticGlobal.builderTheme === 'string') {
      const fromGlobal = mauticGlobal.builderTheme.trim();
      if (fromGlobal) {
        return fromGlobal;
      }
    }

    const templateField = document.querySelector('[name$="[template]"]');
    if (templateField && typeof templateField.value === 'string') {
      const fromField = templateField.value.trim();
      if (fromField) {
        return fromField;
      }
    }

    const selectedTheme = document.querySelector('.theme-selected [data-theme]');
    if (selectedTheme) {
      const fromSelection = selectedTheme.dataset ? selectedTheme.dataset.theme : null;
      if (typeof fromSelection === 'string' && fromSelection.trim()) {
        return fromSelection.trim();
      }
    }

    return null;
  }

  /**
   * Initialize the builder in the landingapge mode
   */
  initPage() {
    // Launch GrapesJS with body part
    const ckeditorModuleUrl = BuilderService.getCkeditorModuleUrl();
    const inlineElements = BuilderService.getInlineElements();
    const pageCkEditorOptions = BuilderService.getCkeConf('page:getBuilderTokens');
    const pageInlineOptions = BuilderService.buildInlineCkeConf(pageCkEditorOptions);

    this.editor = grapesjs.init({
      clearOnRender: true,
      container: '.builder-panel',
      components: contentService.getOriginalContentHtml().body.innerHTML,
      height: '100%',
      canvas: {
        styles: [
          ...contentService.getStyles(),
          `${mauticBaseUrl}plugins/GrapesJsBuilderBundle/Assets/library/js/grapesjs-editor.css`
        ],
      },
      storageManager: false, // https://grapesjs.com/docs/modules/Storage.html#basic-configuration
      assetManager: this.getAssetManagerConf(),
      styleManager: {
        clearProperties: true, // Temp fix https://github.com/artf/grapesjs-preset-webpage/issues/27
      },
      plugins: [
        // partially copied from: https://github.com/GrapesJS/grapesjs/blob/gh-pages/demo.html
        grapesjswebpage,
        grapesjspostcss,
        grapesjsmautic,
        grapesjsckeditor,
        grapesjsblocksbasic,
        grapesjscomponentcountdown,
        grapesjsnavbar,
        grapesjscustomcode,
        grapesjstouch,
        grapesjspostcss,
        grapesjstuiimageeditor,
        grapesjsstylebg,
        ...BuilderService.getPluginNames('page'), // grapesjs-custom-plugins: load custom plugins by their name
      ],
      pluginsOpts: {
        [grapesjswebpage]: {
          formsOpts: false,
          useCustomTheme: false,
        },
        grapesjsmautic: BuilderService.getMauticConf('page-html'),
        [grapesjsckeditor]: {
          ckeditor_module: ckeditorModuleUrl,
          licenseKey: 'GPL',
          inlineMode: true,
          inline: inlineElements,
          inline_options: pageInlineOptions,
          options: pageCkEditorOptions,
          content_policy: BuilderService.getCkEditorContentPolicy(),
          reuse_editor: false,
          toolbar_max_width: '445px',
          inline_toolbar_max_width: '360px',
          theme_alias: BuilderService.getActiveThemeAlias(),
        },
        ...BuilderService.getPluginOptions('page'), // grapesjs-custom-plugins: add the plugin-options
      },
    });

    this.moveBlocksPage();
    return this.editor;
  }

  mjmlToHtml(mjml) {
    const converted = MjmlService.mjmlToHtml(mjml);

    if (0 === converted.errors.length) {
      return converted.html;
    }

    return '';
  }

  initEmailMjml() {
    const components = MjmlService.getOriginalContentMjml();
    // validate
    MjmlService.mjmlToHtml(components);

    const styles = [
      `${mauticBaseUrl}plugins/GrapesJsBuilderBundle/Assets/library/js/grapesjs-editor.css`
    ];

    const ckeditorModuleUrl = BuilderService.getCkeditorModuleUrl();
    const inlineElements = BuilderService.getInlineElements();
    const emailCkEditorOptions = BuilderService.getCkeConf('email:getBuilderTokens');
    const emailInlineOptions = BuilderService.buildInlineCkeConf(emailCkEditorOptions);

    this.editor = grapesjs.init({
      selectorManager: {
        componentFirst: true,
      },
      avoidInlineStyle: false, // TEMP: fixes issue with disappearing inline styles
      forceClass: false, // create new styles if there are some already on the element: https://github.com/GrapesJS/grapesjs/issues/1531
      clearOnRender: true,
      container: '.builder-panel',
      height: '100%',
      canvas: {
        styles,
      },
      domComponents: {
        // disable all except link components
        disableTextInnerChilds: (child) => !child.is('link'), // https://github.com/GrapesJS/grapesjs/releases/tag/v0.21.2
      },
      storageManager: false,
      assetManager: this.getAssetManagerConf(),
      plugins: [grapesjsmjml, grapesjspostcss, grapesjsmautic, grapesjsckeditor, ...BuilderService.getPluginNames('email-mjml')],
      pluginsOpts: {
        [grapesjsmjml]: {
          hideSelector: false,
          custom: false,
          useCustomTheme: false,
        },
        grapesjsmautic: BuilderService.getMauticConf('email-mjml'),
        [grapesjsckeditor]: {
          ckeditor_module: ckeditorModuleUrl,
          licenseKey: 'GPL',
          inlineMode: true,
          inline: inlineElements,
          inline_options: emailInlineOptions,
          options: emailCkEditorOptions,
          content_policy: BuilderService.getCkEditorContentPolicy(),
          reuse_editor: false,
          toolbar_max_width: '445px',
          inline_toolbar_max_width: '360px',
          theme_alias: BuilderService.getActiveThemeAlias(),
        },
        ...BuilderService.getPluginOptions('email-mjml'),
      },
    });

    this.unsetComponentVoidTypes(this.editor);
    this.editor.setComponents(components);

    // Reinitialize the content after parsing MJML.
    // This can be removed once the issue with self-closing tags is resolved in grapesjs-mjml.
    // See: https://github.com/GrapesJS/mjml/issues/149
    const parsedContent = MjmlService.getEditorMjmlContent(this.editor);
    this.editor.setComponents(parsedContent);

    this.editor.BlockManager.get('mj-button').set({
      content: '<mj-button href="https://">Button</mj-button>',
    });

    this.removeSelectedElementsEmailMjml();

    return this.editor;
  }

  unsetComponentVoidTypes(editor) {
    // Support for self-closing components is temporarily disabled due to parsing issues with mjml tags.
    // Browsers only recognize explicit self-closing tags like <img /> and <br />, leading to rendering problems.
    // This can be reverted once the issue with self-closing tags is resolved in grapesjs-mjml.
    // See: https://github.com/GrapesJS/mjml/issues/149
    const voidTypes = ['mj-image', 'mj-divider', 'mj-font', 'mj-spacer'];
    voidTypes.forEach(function (component) {
      editor.DomComponents.addType(component, {
        model: {
          defaults: {
            void: false
          },
          toHTML() {
            const tag = this.get('tagName');
            const attr = this.getAttrToHTML();
            const content = this.get('content');
            let strAttr = '';

            for (let prop in attr) {
              const val = attr[prop];
              const hasValue = typeof val !== 'undefined' && val !== '';
              strAttr += hasValue ? ` ${prop}="${val}"` : '';
            }

            let html = `<${tag}${strAttr}>${content}</${tag}>`;

            // Add the components after the closing tag
            const componentsHtml = this.get('components')
              .map(model => model.toHTML())
              .join('');
            return html + componentsHtml;
          },
        }
      });
    });
  }

  initEmailHtml() {
    const components = contentService.getOriginalContentHtml().body.innerHTML;
    if (!components) {
      throw new Error('no components');
    }

    const styles = [
      `${mauticBaseUrl}plugins/GrapesJsBuilderBundle/Assets/library/js/grapesjs-editor.css`
    ];

    const ckeditorModuleUrl = BuilderService.getCkeditorModuleUrl();
    const inlineElements = BuilderService.getInlineElements();
    const emailCkEditorOptions = BuilderService.getCkeConf('email:getBuilderTokens');
    const emailInlineOptions = BuilderService.buildInlineCkeConf(emailCkEditorOptions);

    // Launch GrapesJS with body part
    this.editor = grapesjs.init({
      clearOnRender: true,
      container: '.builder-panel',
      components,
      height: '100%',
      canvas: {
        styles,
      },
      storageManager: false,
      assetManager: this.getAssetManagerConf(),
      plugins: [grapesjsnewsletter, grapesjspostcss, grapesjsmautic, grapesjsckeditor, ...BuilderService.getPluginNames('email-html')],
      pluginsOpts: {
        grapesjsnewsletter: {
          useCustomTheme: false,
        },
        grapesjsmautic: BuilderService.getMauticConf('email-html'),
        [grapesjsckeditor]: {
          ckeditor_module: ckeditorModuleUrl,
          inlineMode: true,
          inline: inlineElements,
          inline_options: emailInlineOptions,
          options: emailCkEditorOptions,
          content_policy: BuilderService.getCkEditorContentPolicy(),
          reuse_editor: false,
          toolbar_max_width: '445px',
          inline_toolbar_max_width: '360px',
          theme_alias: BuilderService.getActiveThemeAlias(),
        },
        ...BuilderService.getPluginOptions('email-html'),
      },
    });

    // add a Mautic custom block Button
    this.editor.BlockManager.get('button').set({
      content:
        '<a href="#" target="_blank" style="display:inline-block;text-decoration:none;border-color:#4e5d9d;border-width: 10px 20px;border-style:solid; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; background-color: #4e5d9d; display: inline-block;font-size: 16px; color: #ffffff; ">\n' +
        'Button\n' +
        '</a>',
    });

    return this.editor;
  }

  /**
   * Return the names of dynamically added plugins
   * @param context
   * @returns string[]
   */
  static getPluginNames(context) {
    let plugins = [];

    if (window.MauticGrapesJsPlugins) {
      window.MauticGrapesJsPlugins.forEach((item) => {
        if (item.name) {
          if (!item.context || !Array.isArray(item.context) || item.context.length === 0) {
            // if no context is given, the plugin is always added
            plugins.push(item.name);
          } else {
            // check if the plugin should be added for the current editor context
            item.context.forEach((pluginContext) => {
              if (pluginContext === context) {
                plugins.push(item.name);
              }
            })
          }
        }
      });
    }

    return plugins;
  }

  /**
   * Return the options of dynamically added plugins
   * @param context
   * @returns object[]
   */
  static getPluginOptions(context) {
    let pluginOptions = {};

    if (window.MauticGrapesJsPlugins) {
      window.MauticGrapesJsPlugins.forEach((item) => {
        if (!item.context || !Array.isArray(item.context) || item.context.length === 0) {
          // if no context is given, the plugin is always added
          pluginOptions[item.name] = item.pluginOptions ?? {};
        } else {
          // check if the plugin should be added for the current editor context
          item.context.forEach((pluginContext) => {
            if (pluginContext === context) {
              pluginOptions[item.name] = item.pluginOptions ?? {};
            }
          })
        }
      });
    }

    return pluginOptions;
  }

  /**
   * Manage button loading indicator
   *
   * @param activate - true or false
   */
  static setupButtonLoadingIndicator(activate) {
    const builderButton = mQuery('.btn-builder');
    const saveButton = mQuery('.btn-save');
    const applyButton = mQuery('.btn-apply');

    if (activate) {
      Mautic.activateButtonLoadingIndicator(builderButton);
      Mautic.activateButtonLoadingIndicator(saveButton);
      Mautic.activateButtonLoadingIndicator(applyButton);
    } else {
      Mautic.removeButtonLoadingIndicator(builderButton);
      Mautic.removeButtonLoadingIndicator(saveButton);
      Mautic.removeButtonLoadingIndicator(applyButton);
    }
  }

  /**
   * Configure the Asset Manager for all modes
   * @link https://grapesjs.com/docs/modules/Assets.html#configuration
   */
  getAssetManagerConf() {
    const noAssetsTranslationKey = 'grapesjsbuilder.assetManager.noAssets';
    const translatedNoAssets = Mautic.translate(noAssetsTranslationKey);
    const noAssetsMessage = (translatedNoAssets && translatedNoAssets !== noAssetsTranslationKey)
      ? translatedNoAssets
      : 'No assets here, drop files to upload';
    const stripHtml = value => {
      if (typeof value !== 'string') {
        return '';
      }

      if (typeof document === 'undefined') {
        return value;
      }

      const container = document.createElement('div');
      container.innerHTML = value;

      return container.textContent || container.innerText || '';
    };

    const normalizedNoAssetsMessage = stripHtml(noAssetsMessage)
      .replace(/\s+/g, ' ')
      .trim();

    return {
      assets: [],
      noAssets: normalizedNoAssetsMessage,
      upload: this.assetService.getUploadPath(),
      uploadName: 'files',
      multiUpload: 1,
      embedAsBase64: false,
      openAssetsOnDrop: 1,
      autoAdd: 1,
      headers: { 'X-CSRF-Token': mauticAjaxCsrf }, // global variable
    };
  }

  getEditor() {
    return this.editor;
  }

  /**
   * Move the blocks and categories in the sidebar
   */
  moveBlocksPage() {
    const blocks = this.editor.BlockManager.getAll();
    blocks.map(block => {
      // columns go into a new category, at the top
      if (block.attributes.id.indexOf('column') !== -1) {
        this.editor.BlockManager.get(block.attributes.id).set('category', {
          label: "Sections",
          order: -1
        });
      }
      // 'Blocks' category goes after 'Basic'
      if (block.attributes.category === 'Basic') {
        this.editor.BlockManager.get(block.attributes.id).set('category', {
          label: "Basic",
          order: -1
        });
      }
    });
  }

  removeSelectedElementsEmailMjml() {

    // Remove the RAW block (it's just not usable)
    const rawblock = this.editor.BlockManager.get('mj-raw');

    if (rawblock !== null) {
      this.editor.BlockManager.remove(rawblock);
    }
  }

  setupTypographySectorVisibility() {
    if (!this.editor || this.typographySectorInitialized) {
      return;
    }

    const styleManager = this.editor.StyleManager;
    if (!styleManager || typeof styleManager.getSector !== 'function') {
      return;
    }

    const sector = styleManager.getSector('typography');
    if (!sector) {
      return;
    }

    this.typographySector = sector;
    // Delay updates slightly so GrapesJS finishes its own selection bookkeeping before we toggle the sector.
    const scheduleUpdate = (target, delay) => this.scheduleTypographySectorVisibilityUpdate(target, delay);
    const selectionDelay = 15;

    this.editor.on('component:selected', (component) => scheduleUpdate(component, selectionDelay));
    this.editor.on('component:deselected', () => this.scheduleTypographySectorVisibilityUpdate(null, selectionDelay));
    this.editor.on('rte:enable', (component) => scheduleUpdate(component, selectionDelay));
    this.editor.on('rte:disable', () => this.scheduleTypographySectorVisibilityUpdate(null, selectionDelay));

    this.typographySectorInitialized = true;
    this.scheduleTypographySectorVisibilityUpdate(null, selectionDelay);
  }

  scheduleTypographySectorVisibilityUpdate(target, delay = 0) {
    if (!this.typographySector) {
      return;
    }

    this.clearTypographySectorUpdateTimeout();

    const run = () => {
      this.typographySectorTimeout = null;
      this.updateTypographySectorVisibility(target);
    };

    this.typographySectorTimeout = this.scheduleTypographyTimeout(run, delay);
  }

  clearTypographySectorUpdateTimeout() {
    if (!this.typographySectorTimeout) {
      return;
    }

    const timeoutId = this.typographySectorTimeout;
    this.typographySectorTimeout = null;

    if (typeof window !== 'undefined' && typeof window.clearTimeout === 'function') {
      window.clearTimeout(timeoutId);
      return;
    }

    clearTimeout(timeoutId);
  }

  scheduleTypographyTimeout(callback, delay = 0) {
    const timeoutDelay = typeof delay === 'number' && delay > 0 ? delay : 0;

    if (typeof window !== 'undefined' && typeof window.setTimeout === 'function') {
      return window.setTimeout(callback, timeoutDelay);
    }

    return setTimeout(callback, timeoutDelay);
  }

  updateTypographySectorVisibility(target = null) {
    const styleManager = this.editor?.StyleManager;
    if (!styleManager || typeof styleManager.getSector !== 'function') {
      return;
    }

    const sector = styleManager.getSector('typography');
    if (!sector) {
      return;
    }

    this.typographySector = sector;

    const component = this.getTypographyTargetComponent(target);
    const shouldHide = this.shouldHideTypographySector(component);

    this.setTypographySectorModelVisibility(sector, shouldHide);
    this.setTypographySectorDomVisibility(sector, shouldHide);
  }

  getTypographyTargetComponent(target) {
    const resolvedTarget = this.resolveComponentFromTarget(target);
    if (resolvedTarget) {
      return resolvedTarget;
    }

    if (this.editor && typeof this.editor.getSelected === 'function') {
      return this.editor.getSelected();
    }

    return null;
  }

  setTypographySectorModelVisibility(sector, shouldHide) {
    if (typeof sector.set === 'function') {
      sector.set('visible', !shouldHide);
      return;
    }

    sector.visible = !shouldHide;
  }

  setTypographySectorDomVisibility(sector, shouldHide) {
    const sectorEl = this.resolveTypographySectorElement(sector);

    if (sectorEl) {
      sectorEl.style.display = shouldHide ? 'none' : '';
    }
  }

  resolveTypographySectorElement(sector) {
    const sectorId = typeof sector.getId === 'function' ? sector.getId() : 'typography';
    const editorContainer = this.editor && typeof this.editor.getContainer === 'function'
      ? this.editor.getContainer()
      : null;

    if (editorContainer) {
      const sectorEl = editorContainer.querySelector(`.gjs-sm-sector[id*="${sectorId}"]`);
      if (sectorEl) {
        return sectorEl;
      }
    }

    if (sector.view?.el) {
      return sector.view.el;
    }

    return null;
  }

  resolveComponentFromTarget(target) {
    if (!target) {
      return null;
    }

    if (typeof target.get === 'function' && typeof target.getId === 'function') {
      return target;
    }

    if (target.model && typeof target.model.get === 'function') {
      return target.model;
    }

    if (target.component && typeof target.component.get === 'function') {
      return target.component;
    }

    return null;
  }

  shouldHideTypographySector(component) {
    return true;
  }
}
