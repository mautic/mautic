import AssetService from './asset.service';
import BuilderService from './builder.service';

// all css get combined into one builder.css and automatically loaded via js/parcel
import 'grapesjs/dist/css/grapes.min.css';
import './grapesjs-custom.css';

/**
 * Launch builder
 *
 * @param formName
 */
function launchBuilderGrapesjs(formName) {
  if (useBuilderForCodeMode() === false) {
    return;
  }

  Mautic.showChangeThemeWarning = true;

  // Prepare HTML
  mQuery('html').css('font-size', '100%');
  mQuery('body').css('overflow-y', 'hidden');
  mQuery('.builder-panel').css('padding', 0);
  mQuery('.builder-panel').css('display', 'block');
  const $builder = mQuery('.builder');
  $builder.addClass('builder-active').removeClass('hide');

  const assetService = new AssetService();
  const builder = new BuilderService(assetService);
  // Initialize GrapesJS
  builder.initGrapesJS(formName);

  // trigger show event on DOM element
  $builder.trigger('builder:show', [builder.editor])
  // trigger show event on editor instance
  builder.editor.trigger('show');

  // Load and add assets
  (async () => {
    try {
      const result = await assetService.getAssetsXhr();
      builder.editor.AssetManager.add(result.data);
    } catch (error) {
      console.error('Error loading initial assets:', error);
    }
  })();
}

/**
 * The user acknowledges the risk before editing an email or landing page created in Code Mode in the Builder
 */
function useBuilderForCodeMode() {
  const theme = mQuery('.theme-selected').find('[data-theme]').attr('data-theme');
  const isCodeMode = theme === 'mautic_code_mode';
  if (isCodeMode) {
    if (confirm(Mautic.translate('grapesjsbuilder.builder.warning.code_mode')) === false) {
      return false;
    }
  }

  return true;
}

let themeHtmlLoadCount = 0;
let themeHtmlLoadPromise = Promise.resolve();
let themeHtmlRequestId = 0;
let pendingThemeHtmlReject = null;

const THEME_HTML_REQUEST_SUPERSEDED = 'ThemeHtmlRequestSuperseded';

function isThemeHtmlLoading() {
  return themeHtmlLoadCount > 0;
}

function waitForThemeHtml() {
  return themeHtmlLoadPromise;
}

function isThemeHtmlMissing(theme) {
  if (!theme || theme === 'mautic_code_mode') {
    return false;
  }

  const textareaHtml = mQuery('textarea.builder-html');

  return !textareaHtml.length || !textareaHtml.val()?.trim().length;
}

function ensureThemeHtmlFromMjml() {
  const textareaHtml = mQuery('textarea.builder-html');
  const textareaMjml = mQuery('textarea.builder-mjml');

  if (textareaHtml.val()?.trim().length) {
    return true;
  }

  const mjml = textareaMjml.val()?.trim();

  if (!mjml?.length) {
    return false;
  }

  const assetService = new AssetService();
  const builder = new BuilderService(assetService);
  const html = builder.mjmlToHtml(mjml);

  if (html?.trim().length) {
    textareaHtml.val(html);

    return true;
  }

  return false;
}

function hasStoredThemeMjml() {
  return Boolean(mQuery('textarea.builder-mjml').val()?.trim().length);
}

function resolveThemeHtmlBeforeSubmit(theme) {
  if (isThemeHtmlLoading()) {
    return waitForThemeHtml();
  }

  if (hasStoredThemeMjml()) {
    ensureThemeHtmlFromMjml();

    return Promise.resolve();
  }

  return setThemeHtml(theme);
}

function showThemeHtmlSaveError(translationKey) {
  Mautic.setFlashes(Mautic.addErrorFlashMessage(Mautic.translate(translationKey)));
}

function shouldWaitForThemeHtml(theme) {
  if (!theme || theme === 'mautic_code_mode') {
    return false;
  }

  return isThemeHtmlLoading() || isThemeHtmlMissing(theme);
}

function attachThemeHtmlSubmitGuard(themeField) {
  const form = themeField.closest('form')[0];

  if (!form || form.dataset.themeHtmlGuardAttached === 'true') {
    return;
  }

  form.dataset.themeHtmlGuardAttached = 'true';

  form.addEventListener(
    'submit',
    (event) => {
      const theme = themeField.val();

      if (!shouldWaitForThemeHtml(theme)) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      event.stopImmediatePropagation();

      const submitAfterThemeHtml = () => {
        ensureThemeHtmlFromMjml();

        if (shouldWaitForThemeHtml(theme)) {
          showThemeHtmlSaveError('grapesjsbuilder.builder.error.theme_html_empty');

          return;
        }

        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit();
          return;
        }

        mQuery(form).trigger('submit');
      };

      const themeHtmlPromise = resolveThemeHtmlBeforeSubmit(theme);

      themeHtmlPromise
        .catch((error) => {
          if (error?.message === THEME_HTML_REQUEST_SUPERSEDED) {
            return waitForThemeHtml();
          }

          throw error;
        })
        .then(() => submitAfterThemeHtml())
        .catch(() => {
          showThemeHtmlSaveError('grapesjsbuilder.builder.error.theme_html_load');
        });
    },
    true
  );
}

/**
 * Set theme's HTML
 *
 * @param theme
 * @returns {Promise<void>}
 */
function setThemeHtml(theme) {
  if (!theme || theme === 'mautic_code_mode') {
    return Promise.resolve();
  }

  if (pendingThemeHtmlReject) {
    pendingThemeHtmlReject(new Error(THEME_HTML_REQUEST_SUPERSEDED));
    pendingThemeHtmlReject = null;
  }

  themeHtmlLoadCount += 1;
  const requestId = ++themeHtmlRequestId;
  BuilderService.setupButtonLoadingIndicator(true);

  themeHtmlLoadPromise = new Promise((resolve, reject) => {
    pendingThemeHtmlReject = reject;

    mQuery.ajax({
      url: mQuery('#builder_url').val(),
      data: {
        template: theme,
        resetEditorState: 1,
      },
      dataType: 'json',
      success(response) {
        if (requestId !== themeHtmlRequestId) {
          return;
        }

        pendingThemeHtmlReject = null;

        const textareaHtml = mQuery('textarea.builder-html');
        const textareaMjml = mQuery('textarea.builder-mjml');
        const textareaJson = mQuery('textarea.builder-json');
        const form = textareaHtml.closest('form');

        textareaHtml.val(response.templateHtml);

        if (textareaMjml.length) {
          textareaMjml.val(response.templateMjml);
        }

        if (textareaJson.length) {
          textareaJson.val('');
        }

        if (form.length) {
          form.attr('data-grapesjsbuilder-reset', 'true');
        }

        // If MJML template, generate HTML before save
        ensureThemeHtmlFromMjml();

        resolve();
      },
      error(request, textStatus) {
        if (requestId !== themeHtmlRequestId) {
          return;
        }

        pendingThemeHtmlReject = null;
        console.log(`setThemeHtml - Request failed: ${textStatus}`);
        reject(new Error(textStatus || 'setThemeHtml failed'));
      },
      complete() {
        themeHtmlLoadCount = Math.max(0, themeHtmlLoadCount - 1);

        if (!isThemeHtmlLoading()) {
          BuilderService.setupButtonLoadingIndicator(false);
        }
      },
    });
  });

  return themeHtmlLoadPromise;
}

/**
 * The builder button to launch GrapesJS will be disabled when the code mode theme is selected
 *
 * @param theme
 */
function switchBuilderButton(theme) {
  const builderButton = mQuery('.btn-builder');
  const mEmailBuilderButton = mQuery('#emailform_buttons_builder_toolbar_mobile');
  const mPageBuilderButton = mQuery('#page_buttons_builder_toolbar_mobile');
  const isCodeMode = theme === 'mautic_code_mode';

  builderButton.attr('disabled', isCodeMode);

  if (isCodeMode) {
    mPageBuilderButton.addClass('link-is-disabled');
    mEmailBuilderButton.addClass('link-is-disabled');

    mPageBuilderButton.parent().addClass('is-not-allowed');
    mEmailBuilderButton.parent().addClass('is-not-allowed');
  } else {
    mPageBuilderButton.removeClass('link-is-disabled');
    mEmailBuilderButton.removeClass('link-is-disabled');

    mPageBuilderButton.parent().removeClass('is-not-allowed');
    mEmailBuilderButton.parent().removeClass('is-not-allowed');
  }
}

/**
 * The textarea with the HTML source will be displayed if the code mode theme is selected
 *
 * @param theme
 */
function switchCustomHtml(theme) {
  const customHtmlRow = mQuery('#custom-html-row');
  const isPageMode = mQuery('[name="page"]').length !== 0;
  const isCodeMode = theme === 'mautic_code_mode';
  const advancedTab = isPageMode ? mQuery('#advanced-tab') : null;

  if (isCodeMode === true) {
    customHtmlRow.removeClass('hidden');
    isPageMode && advancedTab.removeClass('hidden');
  } else {
    customHtmlRow.addClass('hidden');
    isPageMode && advancedTab.addClass('hidden');
  }
}

/**
 * Initialize original Mautic theme selection with grapejs specific modifications
 */
function initSelectThemeGrapesjs(parentInitSelectTheme) {
  function childInitSelectTheme(themeField) {
    const builderUrl = mQuery('#builder_url');
    let url;

    switchBuilderButton(themeField.val());
    switchCustomHtml(themeField.val());

    // Replace Mautic URL by plugin URL
    if (builderUrl.length) {
      if (builderUrl.val().indexOf('pages') !== -1) {
        url = builderUrl.val().replace('s/pages/builder', 's/grapesjsbuilder/page');
      } else {
        url = builderUrl.val().replace('s/emails/builder', 's/grapesjsbuilder/email');
      }

      builderUrl.val(url);
    }

    // Launch original Mautic.initSelectTheme function
    parentInitSelectTheme(themeField);
    attachThemeHtmlSubmitGuard(themeField);

    mQuery('[data-theme]').click((event) => {
      const target = mQuery(event.target);
      const theme = target.closest('[data-theme]').attr('data-theme');

      switchBuilderButton(theme);
      switchCustomHtml(theme);
    });
  }
  return childInitSelectTheme;
}

Mautic.launchBuilder = launchBuilderGrapesjs;
Mautic.initSelectTheme = initSelectThemeGrapesjs(Mautic.initSelectTheme);
Mautic.setThemeHtml = setThemeHtml;
Mautic.waitForThemeHtml = waitForThemeHtml;
Mautic.isThemeHtmlLoading = isThemeHtmlLoading;
