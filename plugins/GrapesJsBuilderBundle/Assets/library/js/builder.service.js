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
import grapesjsMjmlThemeTokens, { pluginId as mjmlThemeTokensPluginId } from './plugins/grapesjs.mjmlThemeTokens';
import grapesjsMjmlCustomBlocks, { pluginId as mjmlCustomBlocksPluginId } from './plugins/grapesjs.mjmlCustomBlocks';
import contentService from 'grapesjs-preset-mautic/dist/content.service';
import grapesjsmautic from 'grapesjs-preset-mautic';
import editorFontsService from 'grapesjs-preset-mautic/dist/editorFonts/editorFonts.service';
import StorageService from "./storage.service";
import mjml2html from 'mjml-browser';

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
   * Cached mj-head inner content from the theme/template.
   * Used to inject theme tokens (mj-attributes, mj-class) into fragment compilation
   * so styles are visible in the editor canvas.
   */
  cachedMjHeadContent = '';

  /**
   * @param {AssetService} assetService
   */
  constructor(assetService) {
    this.assetService = assetService;
  }

  /**
   * Extract mj-head inner content from MJML (without the tags).
   * @param {string} mjml - Full MJML content
   * @returns {string}
   */
  extractMjHeadContent(mjml) {
    if (!mjml) return '';
    const mjHeadMatch = mjml.match(/<mj-head[^>]*>([\s\S]*?)<\/mj-head>/i);
    return mjHeadMatch && mjHeadMatch[1] ? mjHeadMatch[1].trim() : '';
  }

  /**
   * Create an MJML parser wrapper that injects the cached <mj-head> into MJML fragments.
   * This avoids patching every MJML component View.
   *
   * @param {() => string} getHeadContent
   * @returns {(input: string|any, opts: any) => any}
   */
  createHeadInjectingMjmlParser(getHeadContent) {
    return (input, opts) => {
      // mjml-browser can accept MJML JSON too; only inject into string inputs
      if (typeof input !== 'string') {
        return mjml2html(input, opts);
      }

      const headContent = (getHeadContent && getHeadContent()) || '';
      if (!headContent) {
        return mjml2html(input, opts);
      }

      // Avoid double injecting if fragment already includes mj-head
      if (/<mj-head[\s>]/i.test(input)) {
        return mjml2html(input, opts);
      }

      // Inject right after the opening <mjml ...> tag
      const withHead = input.replace(
        /<mjml(\s[^>]*)?>/i,
        (m) => `${m}<mj-head>${headContent}</mj-head>`
      );

      return mjml2html(withHead, opts);
    };
  }

  /**
   * Remove component "style-default" attributes when the component uses `mj-class`.
   * This prevents GrapesJS-MJML default attributes (eg. mj-text font-size="13px",
   * mj-button background-color="#414141", paddings, etc.) from overriding your theme
   * tokens defined in `<mj-attributes>/<mj-class>`.
   *
   * It only strips attributes that exactly match the component's `style-default`,
   * so user-provided overrides remain.
   *
   * @param {Editor} editor
   */
  stripDefaultAttrsForTokenizedComponents(editor) {
    const wrapper = editor.getWrapper?.();
    if (!wrapper) return;

    const walk = (cmp) => {
      const attrs = { ...(cmp.get('attributes') || {}) };
      const mjClass = attrs['mj-class'];

      if (mjClass) {
        const styleDefault = cmp.get('style-default') || {};
        let changed = false;

        Object.keys(styleDefault).forEach((key) => {
          if (key in attrs && attrs[key] === styleDefault[key]) {
            delete attrs[key];
            changed = true;
          }
        });

        if (changed) {
          cmp.set('attributes', attrs);
        }
      }

      const children = cmp.components?.();
      if (children && children.length) {
        children.forEach((child) => walk(child));
      }
    };

    wrapper.components?.().forEach((c) => walk(c));
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

    // Cache mj-head inner content BEFORE init (used by mjmlParser wrapper)
    this.cachedMjHeadContent = this.extractMjHeadContent(components);

    const styles = [
      `${mauticBaseUrl}plugins/GrapesJsBuilderBundle/Assets/library/js/grapesjs-editor.css`
    ];

    // IMPORTANT: mjmlParser must be provided directly to grapesjs-mjml via pluginsOpts
    const headInjectingParser = this.createHeadInjectingMjmlParser(() => this.cachedMjHeadContent);

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

      plugins: [
        grapesjsmjml,
        grapesjsMjmlThemeTokens,
        grapesjsMjmlCustomBlocks,
        grapesjspostcss,
        grapesjsmautic,
        grapesjsckeditor,
        ...BuilderService.getPluginNames('email-mjml'),
      ],

      pluginsOpts: {
        [grapesjsmjml]: {
          hideSelector: false,
          custom: false,
          useCustomTheme: false,
          mjmlParser: headInjectingParser,
        },

        [grapesjsMjmlThemeTokens]: {
          headContent: this.cachedMjHeadContent
        },

        [mjmlCustomBlocksPluginId]: {
          category: Mautic.translate('grapesjsbuilder.categoryBlockLabel') || 'Blocks',
          label: 'Secondary Button',
        },

        grapesjsmautic: BuilderService.getMauticConf('email-mjml'),
        [grapesjsckeditor]: BuilderService.getCkeConf('email:getBuilderTokens'),
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

    // Tell plugin initial content is ready: strip defaults for existing tokenized nodes + enable defaults for new drops
    this.editor.trigger('mjml-theme-tokens:content:ready');

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