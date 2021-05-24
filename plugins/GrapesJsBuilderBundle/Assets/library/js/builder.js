import BuilderService from './builder.service';
import ContentService from './content.service';

/**
 * Launch builder
 *
 * @param formName
 * @param actionName
 */
function launchBuilderGrapesjs(formName) {
  const assets = ContentService.getAssets();

  const builder = new BuilderService(assets);

  Mautic.showChangeThemeWarning = true;

  // Prepare HTML
  mQuery('html').css('font-size', '100%');
  mQuery('body').css('overflow-y', 'hidden');
  mQuery('.builder-panel').css('padding', 0);
  mQuery('.builder-panel').css('display', 'block');
  mQuery('.builder').addClass('builder-active').removeClass('hide');

  // Initialize GrapesJS
  builder.initGrapesJS(formName);
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
      // if (!textareaHtml.val().length && textareaMjml.val().length) {
      //   builder.mjmlToHtml(textareaMjml, textareaHtml);
      // }
      // }
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
 * Initialize original Mautic theme selection with grapejs specific modifications
 */
function initSelectThemeGrapesjs(parentInitSelectTheme) {
  function childInitSelectTheme(themeField) {
    const builderUrl = mQuery('#builder_url');
    let url;

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
  }
  return childInitSelectTheme;
}

Mautic.launchBuilder = launchBuilderGrapesjs;
Mautic.initSelectTheme = initSelectThemeGrapesjs(Mautic.initSelectTheme);
Mautic.setThemeHtml = setThemeHtml;
