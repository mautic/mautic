Mautic.launchBuilder = function () {
    alert('Please enable the GrapesJS builder plugin (or another builder plugin) to use this feature.');
};

/**
 * Adds a hidded field which adds inBuilder=1 param to the request and will be returned in the response
 *
 * @param jQuery object of form
 */
Mautic.inBuilderSubmissionOn = function(form) {
    var inBuilder = mQuery('<input type="hidden" name="inBuilder" value="1" />');
    form.append(inBuilder);
}

/**
 * Removes the hidded field which adds inBuilder=1 param to the request
 *
 * @param jQuery object of form
 */
Mautic.inBuilderSubmissionOff = function(form) {
    Mautic.isInBuilder = false;
    mQuery('input[name="inBuilder"]').remove();
}

/**
 * Processes the Apply's button response
 *
 * @param  object response
 */
Mautic.processBuilderErrors = function(response) {
    if (response.validationError) {
        mQuery('.btn-apply-builder').attr('disabled', true);
        mQuery('#builder-errors').show('fast').text(response.validationError);
    }
};

/**
 * Opens Filemanager window
 */
Mautic.openMediaManager = function() {
    Mautic.openServerBrowser(
        mauticBasePath + '/elfinder',
        screen.width * 0.7,
        screen.height * 0.7
    );
}

/**
 * Removes stuff the Builder needs for it's magic but cannot be in the HTML result
 *
 * @param  object htmlContent
 */
Mautic.sanitizeHtmlBeforeSave = function(htmlContent) {
    // Remove Mautic's assets
    htmlContent.find('[data-source="mautic"]').remove();
    htmlContent.find('.atwho-container').remove();
    htmlContent.find('.fr-image-overlay, .fr-quick-insert, .fr-tooltip, .fr-toolbar, .fr-popup, .fr-image-resizer').remove();

    // Remove the slot focus highlight
    htmlContent.find('[data-slot-focus], [data-section-focus]').remove();

    // Replace all url("${URL}") with url('${URL}')
    var customHtml = Mautic.domToString(htmlContent).replace(/url\(&quot;(.+)&quot;\)/g, 'url(\'$1\')');

    // Convert dynamic slot definitions into tokens
    // customHtml = Mautic.convertDynamicContentSlotsToTokens(customHtml);

    // return Mautic.prepareCodeModeBlocksBeforeSave(customHtml);
    return customHtml;
};

/**
 * Serializes DOM (full HTML document) to string
 *
 * @param  object dom
 * @return string
 */
Mautic.domToString = function(dom) {
    if (typeof dom === 'string') {
        return dom;
    }
    var xs = new XMLSerializer();
    return xs.serializeToString(dom.get(0));
};

/**
 * Opens new window on the URL
 */
Mautic.openServerBrowser = function(url, width, height) {
    var iLeft = (screen.width - width) / 2 ;
    var iTop = (screen.height - height) / 2 ;
    var sOptions = "toolbar=no,status=no,resizable=yes,dependent=yes" ;
    sOptions += ",width=" + width ;
    sOptions += ",height=" + height ;
    sOptions += ",left=" + iLeft ;
    sOptions += ",top=" + iTop ;
    var oWindow = window.open( url, "BrowseWindow", sOptions ) ;
}

/**
 * Initialize theme selection
 *
 * @param themeField
 */
Mautic.initSelectTheme = function(themeField) {
    var customHtml = mQuery('textarea.builder-html');
    var isNew = Mautic.isNewEntity('#page_sessionId, #emailform_sessionId');
    Mautic.showChangeThemeWarning = true;
    Mautic.builderTheme = themeField.val();

    if (isNew) {
        Mautic.showChangeThemeWarning = false;

        // Populate default content
        if (!customHtml.length || !customHtml.val().length) {
            Mautic.setThemeHtml(Mautic.builderTheme);
        }
    }

    if (customHtml.length) {
        mQuery('[data-theme]').click(function(e) {
            e.preventDefault();
            var currentLink = mQuery(this);
            var theme = currentLink.attr('data-theme');
            var isCodeMode = (theme === 'mautic_code_mode');
            Mautic.builderTheme = theme;

            if (Mautic.showChangeThemeWarning && customHtml.val().length) {
                if (!isCodeMode) {
                    if (confirm(Mautic.translate('mautic.core.builder.theme_change_warning'))) {
                        customHtml.val('');
                        Mautic.showChangeThemeWarning = false;
                    } else {
                        return;
                    }
                } else {
                    if (confirm(Mautic.translate('mautic.core.builder.code_mode_warning'))) {
                    } else {
                        return;
                    }
                }
            }

            // Set the theme field value
            themeField.val(theme);

            // Code Mode
            if (isCodeMode) {
                mQuery('.builder').addClass('code-mode');
                mQuery('.builder .code-editor').removeClass('hide');
                mQuery('.builder .code-mode-toolbar').removeClass('hide');
                mQuery('.builder .builder-toolbar').addClass('hide');
            } else {
                mQuery('.builder').removeClass('code-mode');
                mQuery('.builder .code-editor').addClass('hide');
                mQuery('.builder .code-mode-toolbar').addClass('hide');
                mQuery('.builder .builder-toolbar').removeClass('hide');

                // Load the theme HTML to the source textarea
                Mautic.setThemeHtml(theme);
            }

            // Manipulate classes to achieve the theme selection illusion
            mQuery('.theme-list .panel').removeClass('theme-selected');
            currentLink.closest('.panel').addClass('theme-selected');
            mQuery('.theme-list .select-theme-selected').addClass('hide');
            mQuery('.theme-list .select-theme-link').removeClass('hide');
            currentLink.closest('.panel').find('.select-theme-selected').removeClass('hide');
            currentLink.addClass('hide');
        });
    }
};

/**
 * Set theme's HTML
 *
 * @param theme
 */
Mautic.setThemeHtml = function(theme) {
    mQuery.get(mQuery('#builder_url').val()+'?template=' + theme, function(themeHtml) {
        var textarea = mQuery('textarea.builder-html');
        textarea.val(themeHtml);
    });
};

Mautic.toggleBuilderButton = function (hide) {
    if (mQuery('.toolbar-form-buttons .toolbar-standard .btn-builder')) {
        if (hide) {
            // Move the builder button out of the group and hide it
            mQuery('.toolbar-form-buttons .toolbar-standard .btn-builder')
                .addClass('hide btn-standard-toolbar')
                .appendTo('.toolbar-form-buttons')

            mQuery('.toolbar-form-buttons .toolbar-dropdown i.ri-instance-fill').parent().addClass('hide');
        } else {
            if (!mQuery('.btn-standard-toolbar.btn-builder').length) {
                mQuery('.toolbar-form-buttons .toolbar-standard .btn-builder').addClass('btn-standard-toolbar')
            } else {
                // Move the builder button out of the group and hide it
                mQuery('.toolbar-form-buttons .btn-standard-toolbar.btn-builder')
                    .prependTo('.toolbar-form-buttons .toolbar-standard')
                    .removeClass('hide');

                mQuery('.toolbar-form-buttons .toolbar-dropdown i.ri-instance-fill').parent().removeClass('hide');
            }
        }
    }
};

Mautic.removeAddVariantButton = function() {
    // Remove the Add Variant button for dynamicContent slots
    parent.mQuery('#customize-slot-panel').find('.panel-heading button').remove();
    Mautic.reattachDEC();
};

Mautic.reattachDEC = function() {
    if (typeof Mautic.activeDEC !== 'undefined') {
        var element = Mautic.activeDEC.detach();
        Mautic.activeDECParent.append(element);
    }
};


Mautic.isCodeMode = function() {
    return mQuery('a[data-theme=mautic_code_mode]').first().hasClass('hide');
};

window.document.fileManagerInsertImageCallback = function(selector, url) {
    if (Mautic.isCodeMode()) {
        Mautic.insertTextAtCMCursor(url);
    }
};

/**
 * @returns {string}
 */
Mautic.getBuilderTokensMethod = function() {
    var method = 'page:getBuilderTokens';
    if (parent.mQuery('.builder').hasClass('email-builder')) {
        method = 'email:getBuilderTokens';
    }
    return method;
};
