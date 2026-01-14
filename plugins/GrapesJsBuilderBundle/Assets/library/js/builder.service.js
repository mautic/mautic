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
import grapesjsckeditor from './plugins/grapesjs.ckeditor';
import contentService from 'grapesjs-preset-mautic/dist/content.service';
import grapesjsmautic from 'grapesjs-preset-mautic';
import editorFontsService from 'grapesjs-preset-mautic/dist/editorFonts/editorFonts.service';
import StorageService from "./storage.service";

// for local dev
// import contentService from '../../../../../../grapesjs-preset-mautic/src/content.service';
// import grapesjsmautic from '../../../../../../grapesjs-preset-mautic/src';

import CodeModeButton from './codeMode/codeMode.button';
import MjmlService from 'grapesjs-preset-mautic/dist/mjml/mjml.service';

export default class BuilderService {
  editor;

  storageService;

  assetService;

  /**
   * Cached mj-head content from the theme for injection into component rendering.
   * This ensures theme tokens (mj-attributes, mj-class) are applied in the editor canvas.
   */
  cachedMjHeadContent = '';

  /**
   * Parsed mj-class definitions from the theme.
   * Maps class names to their attribute values.
   */
  mjClassDefinitions = {};

  /**
   * @param {AssetService} assetService
   */
  constructor(assetService) {
    this.assetService = assetService;
  }

  /**
   * Extract mj-head content from MJML for injection into component rendering.
   * @param {string} mjml - Full MJML content
   * @returns {string} - The inner content of mj-head (without the tags)
   */
  extractMjHeadContent(mjml) {
    if (!mjml) return '';
    const mjHeadMatch = mjml.match(/<mj-head[^>]*>([\s\S]*?)<\/mj-head>/i);
    return mjHeadMatch && mjHeadMatch[1] ? mjHeadMatch[1].trim() : '';
  }

  /**
   * Parse mj-class definitions from mj-head content.
   * Returns a map of class names to their attribute values.
   * @param {string} mjHeadContent - The inner content of mj-head
   * @returns {Object} - Map of class name to attributes object
   */
  parseMjClassDefinitions(mjHeadContent) {
    const classes = {};
    if (!mjHeadContent) return classes;

    // Match all mj-class elements: <mj-class name="t-h1" font-size="32px" ...>
    const mjClassRegex = /<mj-class\s+([^>]*?)(?:\/>|><\/mj-class>|>)/gi;
    let match;

    while ((match = mjClassRegex.exec(mjHeadContent)) !== null) {
      const attrString = match[1];

      // Extract the name attribute
      const nameMatch = attrString.match(/name\s*=\s*["']([^"']+)["']/i);
      if (!nameMatch) continue;

      const className = nameMatch[1];
      const attrs = {};

      // Extract all other attributes
      const attrRegex = /(\S+)\s*=\s*["']([^"']*)["']/g;
      let attrMatch;
      while ((attrMatch = attrRegex.exec(attrString)) !== null) {
        const [, attrName, attrValue] = attrMatch;
        if (attrName.toLowerCase() !== 'name') {
          attrs[attrName] = attrValue;
        }
      }

      classes[className] = attrs;
    }

    return classes;
  }

  /**
   * Apply mj-class styles to components that have mj-class attribute.
   * This ensures theme token styles are reflected in the editor.
   * @param {Editor} editor - GrapesJS editor instance
   * @param {Object} mjClassDefinitions - Map of class names to attributes
   */
  applyMjClassStylesToComponents(editor, mjClassDefinitions) {
    if (!mjClassDefinitions || Object.keys(mjClassDefinitions).length === 0) return;

    const processComponent = (component) => {
      const attrs = component.get('attributes') || {};
      const mjClassAttr = attrs['mj-class'];

      if (mjClassAttr) {
        // mj-class can have multiple classes: "t-btn t-btn-primary"
        const classNames = mjClassAttr.split(/\s+/);
        const mergedAttrs = { ...attrs };

        // Apply each class's attributes (later classes override earlier ones)
        classNames.forEach(className => {
          const classAttrs = mjClassDefinitions[className];
          if (classAttrs) {
            Object.assign(mergedAttrs, classAttrs);
          }
        });

        // Update component attributes
        component.set('attributes', mergedAttrs);

        // Also update the style (GrapesJS uses style for visual representation)
        const currentStyle = component.get('style') || {};
        component.set('style', { ...currentStyle, ...mergedAttrs });
      }

      // Process child components
      const children = component.get('components');
      if (children) {
        children.forEach(child => processComponent(child));
      }
    };

    // Process all components in the editor
    const wrapper = editor.getWrapper();
    if (wrapper) {
      const components = wrapper.get('components');
      if (components) {
        components.forEach(component => processComponent(component));
      }
    }
  }

  /**
   * Override MJML component views to inject mj-head content into their
   * MJML compilation wrappers. This ensures theme tokens (mj-attributes, mj-class)
   * are applied when components render in the editor canvas.
   *
   * @param {Editor} editor - GrapesJS editor instance
   * @param {string} mjHeadContent - The inner content of mj-head from the theme
   */
  injectMjHeadIntoComponentViews(editor, mjHeadContent) {
    if (!mjHeadContent) return;

    const mjHeadWrapper = `<mj-head>${mjHeadContent}</mj-head>`;

    // List of all MJML component types that need the mj-head injection
    const mjmlComponentTypes = [
      'mj-text', 'mj-button', 'mj-image', 'mj-divider', 'mj-spacer',
      'mj-social', 'mj-social-element', 'mj-navbar', 'mj-navbar-link',
      'mj-section', 'mj-column', 'mj-wrapper', 'mj-group', 'mj-hero',
      'mj-raw', 'mj-body',
    ];

    mjmlComponentTypes.forEach((typeName) => {
      const existingType = editor.DomComponents.getType(typeName);
      if (!existingType) return;

      // Get the View class (not the config object)
      const ViewClass = existingType.view;

      // Check if this type has a view with getMjmlTemplate on its prototype
      if (!ViewClass || !ViewClass.prototype || typeof ViewClass.prototype.getMjmlTemplate !== 'function') {
        return;
      }

      // Store reference to original method
      const originalGetMjmlTemplate = ViewClass.prototype.getMjmlTemplate;

      // Patch getMjmlTemplate to inject mj-head
      ViewClass.prototype.getMjmlTemplate = function() {
        const original = originalGetMjmlTemplate.call(this);

        // Inject mj-head after <mjml> tag in the start wrapper
        const enhancedStart = original.start.replace(
          '<mjml>',
          `<mjml>${mjHeadWrapper}`
        );

        return {
          start: enhancedStart,
          end: original.end,
        };
      };
    });
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

        assetsContainer.addEventListener('scroll', function() {
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
      // trigger hide event on DOM element
      mQuery('.builder').trigger('builder:hide', [this.editor]);
      // trigger hide event on editor instance
      this.editor.trigger('hide');
    };
    this.editor.on('run:mautic-editor-page-html-close', triggerBuilderHide);
    this.editor.on('run:mautic-editor-email-html-close', triggerBuilderHide);
    this.editor.on('run:mautic-editor-email-mjml-close', triggerBuilderHide);

    // add offset to flashes container for better UI visibility when builder is on
    this.editor.on('show', () => mQuery('#flashes').addClass('alert-offset'));
    this.editor.on('hide', () => mQuery('#flashes').removeClass('alert-offset'));
  }

  /**
   * Initialize the grapesjs build in the
   * correct mode
   */
  initGrapesJS(object) {
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

    // add code mode button
    // @todo: only show button if configured: sourceEdit: 1,
    const codeModeButton = new CodeModeButton(this.editor);
    codeModeButton.addCommand();
    codeModeButton.addButton();

    this.storageService = new StorageService(this.editor, object);
    this.setListeners();
  }

  static getMauticConf(mode) {
    return {
      mode,
    };
  }

  static getCkeConf(tokenCallback) {
    const ckEditorToolbarOptions = ['undo', 'redo', '|', 'bold','italic', 'underline','strikethrough', '|', 'fontSize','fontFamily','fontColor','fontBackgroundColor', '|' ,'alignment','outdent', 'indent', '|', 'blockQuote', 'insertTable', '|', 'bulletedList','numberedList', '|', 'link', '|', 'TokenPlugin'];
    return Mautic.GetCkEditorConfigOptions(ckEditorToolbarOptions, tokenCallback);
  }

  /**
   * Initialize the builder in the landingapge mode
   */
  initPage() {
    // Launch GrapesJS with body part
    this.editor = grapesjs.init({
      clearOnRender: true,
      container: '.builder-panel',
      components: contentService.getOriginalContentHtml().body.innerHTML,
      height: '100%',
      canvas: {
        styles: contentService.getStyles(),
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
        [grapesjsckeditor]: BuilderService.getCkeConf('page:getBuilderTokens'),
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

    // Extract mj-head content for theme token injection into component rendering
    this.cachedMjHeadContent = this.extractMjHeadContent(components);

    const styles = [
      `${mauticBaseUrl}plugins/GrapesJsBuilderBundle/Assets/library/js/grapesjs-editor.css`
    ];

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
        [grapesjsckeditor]: BuilderService.getCkeConf('email:getBuilderTokens'),
        ...BuilderService.getPluginOptions('email-mjml'),
      },
    });

    // Inject mj-head into component views for proper theme token rendering in canvas
    // Must be done AFTER grapesjs.init() (so component types are registered) but BEFORE setComponents()
    if (this.cachedMjHeadContent) {
      this.injectMjHeadIntoComponentViews(this.editor, this.cachedMjHeadContent);
    }

    // Parse mj-class definitions for applying to components
    this.mjClassDefinitions = this.parseMjClassDefinitions(this.cachedMjHeadContent);

    this.unsetComponentVoidTypes(this.editor);
    this.editor.setComponents(components);

    // Reinitialize the content after parsing MJML.
    // This can be removed once the issue with self-closing tags is resolved in grapesjs-mjml.
    // See: https://github.com/GrapesJS/mjml/issues/149
    const parsedContent = MjmlService.getEditorMjmlContent(this.editor);
    this.editor.setComponents(parsedContent);

    // Apply mj-class styles to components after they're loaded
    // This ensures theme token values are set on component attributes
    if (this.mjClassDefinitions && Object.keys(this.mjClassDefinitions).length > 0) {
      this.applyMjClassStylesToComponents(this.editor, this.mjClassDefinitions);
    }

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
    voidTypes.forEach(function(component) {
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
        [grapesjsckeditor]: BuilderService.getCkeConf('email:getBuilderTokens'),
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
    return {
      assets: [],
      noAssets: Mautic.translate('grapesjsbuilder.assetManager.noAssets'),
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
      if(block.attributes.id.indexOf('column') !== -1) {
        this.editor.BlockManager.get(block.attributes.id).set('category', {
          label:"Sections",
          order: -1
        });
      }
      // 'Blocks' category goes after 'Basic'
      if(block.attributes.category === 'Basic') {
        this.editor.BlockManager.get(block.attributes.id).set('category', {
          label:"Basic",
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
}
