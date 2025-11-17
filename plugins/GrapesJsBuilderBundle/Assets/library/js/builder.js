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

/**
 * Set theme's HTML
 *
 * @param theme
 */
function setThemeHtml(theme) {
  BuilderService.setupButtonLoadingIndicator(true);
  // Load template and fill field
  mQuery.ajax({
    url: mQuery('#builder_url').val(),
    data: `template=${theme}`,
    dataType: 'json',
    success(response) {
      const textareaHtml = mQuery('textarea.builder-html');
      const textareaMjml = mQuery('textarea.builder-mjml');

      textareaHtml.val(response.templateHtml);

      if (typeof textareaMjml !== 'undefined') {
        textareaMjml.val(response.templateMjml);
      }

      // If MJML template, generate HTML before save
      if (!textareaHtml.val().length && textareaMjml.val().length) {
        const assetService = new AssetService();
        const builder = new BuilderService(assetService);

        textareaHtml.val(builder.mjmlToHtml(response.templateMjml));
      }
    },
    error(request, textStatus) {
      console.log(`setThemeHtml - Request failed: ${textStatus}`);
    },
    complete() {
      BuilderService.setupButtonLoadingIndicator(false);
    },
  });
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
