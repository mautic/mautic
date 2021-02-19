import BuilderService from './builder.service';
// import builder from './builder.service';

/**
 * Launch builder
 *
 * @param formName
 * @param actionName
 */
function launchBuilderGrapesjs(formName) {
  // Parse HTML template
  const parser = new DOMParser();
  const textareaHtml = mQuery('textarea.builder-html');
  // const textareaAssets = mQuery('textarea#grapesjsbuilder_assets');
  const fullHtml = parser.parseFromString(textareaHtml.val(), 'text/html');

  const canvasContent = fullHtml.body.innerHTML
    ? fullHtml.body.innerHTML
    : mQuery('textarea.builder-mjml').val();

  const builder = new BuilderService(canvasContent);

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

function manageDynamicContentTokenToSlot(component) {
  const regex = RegExp(/\{dynamiccontent="(.*)"\}/, 'g');

  const content = component.get('content');
  const regexEx = regex.exec(content);

  if (regexEx !== null) {
    const dynConName = regexEx[1];
    const dynConTabA = mQuery('#dynamicContentTabs a').filter(
      () => mQuery(this).text().trim() === dynConName
    );

    if (typeof dynConTabA !== 'undefined' && dynConTabA.length) {
      // If exist -> fill
      const dynConTarget = dynConTabA.attr('href');
      let dynConContent = '';

      if (mQuery(dynConTarget).html()) {
        const dynConContainer = mQuery(dynConTarget).find(`${dynConTarget}_content`);

        if (dynConContainer.hasClass('editor')) {
          dynConContent = dynConContainer.froalaEditor('html.get');
        } else {
          dynConContent = dynConContainer.html();
        }
      }

      if (dynConContent === '') {
        dynConContent = dynConTabA.text();
      }

      component.addAttributes({
        'data-param-dec-id': parseInt(dynConTarget.replace(/[^0-9]/g, ''), 10),
      });
      component.set('content', dynConContent);
    } else {
      // If doesn't exist -> create
      const dynConTarget = Mautic.createNewDynamicContentItem(mQuery);
      const dynConTab = mQuery('#dynamicContentTabs').find(`a[href="${dynConTarget}"]`);

      component.addAttributes({
        'data-param-dec-id': parseInt(dynConTarget.replace(/[^0-9]/g, ''), 10),
      });
      component.set('content', dynConTab.text());
    }
  }
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
 * Convert dynamic content tokens to slot and load content
 */
function grapesConvertDynamicContentTokenToSlot(editor) {
  const dc = editor.DomComponents;

  const dynamicContents = dc.getWrapper().find('[data-slot="dynamicContent"]');

  if (dynamicContents.length) {
    dynamicContents.forEach((dynamicContent) => {
      manageDynamicContentTokenToSlot(dynamicContent);
    });
  }
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

Mautic.grapesConvertDynamicContentTokenToSlot = grapesConvertDynamicContentTokenToSlot;
Mautic.launchBuilder = launchBuilderGrapesjs;
Mautic.initSelectTheme = initSelectThemeGrapesjs(Mautic.initSelectTheme);
Mautic.setThemeHtml = setThemeHtml;
