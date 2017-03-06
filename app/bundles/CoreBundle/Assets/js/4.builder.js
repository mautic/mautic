/**
 * Launch builder
 *
 * @param formName
 * @param actionName
 */
Mautic.launchBuilder = function (formName, actionName) {
    var builder = mQuery('.builder');
    Mautic.codeMode = builder.hasClass('code-mode');
    Mautic.showChangeThemeWarning = true;

    mQuery('body').css('overflow-y', 'hidden');

    // Activate the builder
    builder.addClass('builder-active').removeClass('hide');

    if (typeof actionName == 'undefined') {
        actionName = formName;
    }

    var builderCss = {
        margin: "0",
        padding: "0",
        border: "none",
        width: "100%",
        height: "100%"
    };

    // Load the theme from the custom HTML textarea
    var themeHtml = mQuery('textarea.builder-html').val();

    if (Mautic.codeMode) {
        var rawTokens = mQuery.map(Mautic.builderTokens, function (element, index) {
            return index
        }).sort();
        Mautic.builderCodeMirror = CodeMirror(document.getElementById('customHtmlContainer'), {
            value: themeHtml,
            lineNumbers: true,
            mode: 'htmlmixed',
            extraKeys: {"Ctrl-Space": "autocomplete"},
            lineWrapping: true,
            hintOptions: {
                hint: function (editor) {
                    var cursor = editor.getCursor();
                    var currentLine = editor.getLine(cursor.line);
                    var start = cursor.ch;
                    var end = start;
                    while (end < currentLine.length && /[\w|}$]+/.test(currentLine.charAt(end))) ++end;
                    while (start && /[\w|{$]+/.test(currentLine.charAt(start - 1))) --start;
                    var curWord = start != end && currentLine.slice(start, end);
                    var regex = new RegExp('^' + curWord, 'i');
                    var result = {
                        list: (!curWord ? rawTokens : mQuery(rawTokens).filter(function(idx) {
                            return (rawTokens[idx].indexOf(curWord) !== -1);
                        })),
                        from: CodeMirror.Pos(cursor.line, start),
                        to: CodeMirror.Pos(cursor.line, end)
                    };

                    return result;
                }
            }
        });

        Mautic.keepPreviewAlive('builder-template-content');
    }

    var builderPanel = mQuery('.builder-panel');
    var builderContent = mQuery('.builder-content');
    var btnCloseBuilder = mQuery('.btn-close-builder');
    var panelHeight = (builderContent.css('right') == '0px') ? builderPanel.height() : 0;
    var panelWidth = (builderContent.css('right') == '0px') ? 0 : builderPanel.width();
    var spinnerLeft = (mQuery(window).width() - panelWidth - 60) / 2;
    var spinnerTop = (mQuery(window).height() - panelHeight - 60) / 2;

    // Blur and focus the focussed inputs to fix the browser autocomplete bug on scroll
    builderPanel.on('scroll', function(e) {
        builderPanel.find('input:focus').blur();
    });

    var overlay = mQuery('<div id="builder-overlay" class="modal-backdrop fade in"><div style="position: absolute; top:' + spinnerTop + 'px; left:' + spinnerLeft + 'px" class="builder-spinner"><i class="fa fa-spinner fa-spin fa-5x"></i></div></div>').css(builderCss).appendTo('.builder-content');

    // Disable the close button until everything is loaded
    btnCloseBuilder.prop('disabled', true);

    // Insert the Mautic assets to the header
    var assets = Mautic.htmlspecialchars_decode(mQuery('[data-builder-assets]').html());
    themeHtml = themeHtml.replace('</head>', assets+'</head>');

    Mautic.buildBuilderIframe(themeHtml, 'builder-template-content', function() {
        mQuery('#builder-overlay').addClass('hide');
        btnCloseBuilder.prop('disabled', false);
    });
};

/**
 * Frmats code style in the CodeMirror editor
 */
Mautic.formatCode = function() {
    Mautic.builderCodeMirror.autoFormatRange({line: 0, ch: 0}, {line: Mautic.builderCodeMirror.lineCount()});
}

/**
 * Opens Filemanager window
 */
Mautic.openMediaManager = function() {
    Mautic.openServerBrowser(
        mauticBasePath + '/' + mauticAssetPrefix + 'app/bundles/CoreBundle/Assets/js/libraries/ckeditor/filemanager/index.html?type=Images',
        screen.width * 0.7,
        screen.height * 0.7
    );
}

/**
 * Receives a file URL from Filemanager when selected
 */
Mautic.setFileUrl = function(url, width, height, alt) {
    Mautic.insertTextAtCMCursor(url);
}

/**
 * Inserts the text to the cursor position or replace selected range
 */
Mautic.insertTextAtCMCursor = function(text) {
    var doc = Mautic.builderCodeMirror.getDoc();
    var cursor = doc.getCursor();
    doc.replaceRange(text, cursor);
}

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
 * Creates an iframe and keeps its content live from CodeMirror changes
 *
 * @param iframeId
 * @param slot
 */
Mautic.keepPreviewAlive = function(iframeId, slot) {
    var codeChanged = false;
    // Watch for code changes
    Mautic.builderCodeMirror.on('change', function(cm, change) {
        codeChanged = true;
    });

    window.setInterval(function() {
        if (codeChanged) {
            var value = (Mautic.builderCodeMirror)?Mautic.builderCodeMirror.getValue():'';
            Mautic.livePreviewInterval = Mautic.updateIframeContent(iframeId, value, slot);
            codeChanged = false;
        }
    }, 2000);
};

Mautic.killLivePreview = function() {
    window.clearInterval(Mautic.livePreviewInterval);
};

Mautic.destroyCodeMirror = function() {
    delete Mautic.builderCodeMirror;
    mQuery('#customHtmlContainer').empty();
};

/**
 * @param themeHtml
 * @param id
 * @param onLoadCallback
 */
Mautic.buildBuilderIframe = function(themeHtml, id, onLoadCallback) {
    if (mQuery('iframe#'+id).length) {
        var builder = mQuery('iframe#'+id);
    } else {
        var builder = mQuery("<iframe />", {
            css: {
                margin: "0",
                padding: "0",
                border: "none",
                width: "100%",
                height: "100%"
            },
            id: id
        }).appendTo('.builder-content');
    }

    builder.on('load', function() {
        if (typeof onLoadCallback === 'function') {
            onLoadCallback();
        }
    });

    Mautic.updateIframeContent(id, themeHtml);
};

/**
 * @param encodedHtml
 * @returns {*}
 */
Mautic.htmlspecialchars_decode = function(encodedHtml) {
    encodedHtml = encodedHtml.replace(/&quot;/g, '"');
    encodedHtml = encodedHtml.replace(/&#039;/g, "'");
    encodedHtml = encodedHtml.replace(/&amp;/g, '&');
    encodedHtml = encodedHtml.replace(/&lt;/g, '<');
    encodedHtml = encodedHtml.replace(/&gt;/g, '>');
    return encodedHtml;
};

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
 * Updates content of an iframe
 *
 * @param iframeId ID
 * @param content HTML content
 * @param slot
 */
Mautic.updateIframeContent = function(iframeId, content, slot) {
    if (iframeId) {
        var iframe = document.getElementById(iframeId);
        var doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(content);
        doc.close();
    } else if (slot) {
        slot.html(content);
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

/**
 * Close the builder
 *
 * @param model
 */
Mautic.closeBuilder = function(model) {
    var panelHeight = (mQuery('.builder-content').css('right') == '0px') ? mQuery('.builder-panel').height() : 0,
        panelWidth = (mQuery('.builder-content').css('right') == '0px') ? 0 : mQuery('.builder-panel').width(),
        spinnerLeft = (mQuery(window).width() - panelWidth - 60) / 2,
        spinnerTop = (mQuery(window).height() - panelHeight - 60) / 2,
        customHtml;
    mQuery('.builder-spinner').css({
        left: spinnerLeft,
        top: spinnerTop
    });
    mQuery('#builder-overlay').removeClass('hide');
    mQuery('.btn-close-builder').prop('disabled', true);

    try {
        if (Mautic.codeMode) {
            customHtml = Mautic.builderCodeMirror.getValue();
            Mautic.killLivePreview();
            Mautic.destroyCodeMirror();
            delete Mautic.codeMode;
        } else {
            // Trigger slot:destroy event
            document.getElementById('builder-template-content').contentWindow.Mautic.destroySlots();

            var themeHtml = mQuery('iframe#builder-template-content').contents();

            // Remove Mautic's assets
            themeHtml.find('[data-source="mautic"]').remove();
            themeHtml.find('.atwho-container').remove();
            themeHtml.find('.fr-image-overlay, .fr-quick-insert, .fr-tooltip, .fr-toolbar, .fr-popup, .fr-image-resizer').remove();

            // Remove the slot focus highlight
            themeHtml.find('[data-slot-focus], [data-section-focus]').remove();

            // Clear the customize forms
            mQuery('#slot-form-container, #section-form-container').html('');

            customHtml = themeHtml.find('html').get(0).outerHTML
        }

        // Store the HTML content to the HTML textarea
        mQuery('.builder-html').val(customHtml);
    } catch (error) {
        // prevent from being able to close builder
    }

    // Kill the overlay
    mQuery('#builder-overlay').remove();

    // Hide builder
    mQuery('.builder').removeClass('builder-active').addClass('hide');
    mQuery('.btn-close-builder').prop('disabled', false);
    mQuery('body').css('overflow-y', '');
    mQuery('.builder').addClass('hide');
    Mautic.stopIconSpinPostEvent();
    mQuery('#builder-template-content').remove();
};

Mautic.destroySlots = function() {
    // Trigger destroy slots event
    if (typeof Mautic.builderSlots !== 'undefined' && Mautic.builderSlots.length) {
        mQuery.each(Mautic.builderSlots, function(i, slotParams) {
            mQuery(slotParams.slot).trigger('slot:destroy', slotParams);
        });
        delete Mautic.builderSlots;
    }

    // Destroy sortable
    Mautic.builderContents.find('[data-slot-container]').sortable('destroy');

    // Remove empty class="" attr
    Mautic.builderContents.find('*[class=""]').removeAttr('class');

    // Remove border highlighted by Froala
    Mautic.builderContents = Mautic.clearFroalaStyles(Mautic.builderContents);

    // Remove style="z-index: 2501;" which Froala forgets there
    Mautic.builderContents.find('*[style="z-index: 2501;"]').removeAttr('style');

    // Make sure that the Froala editor is gone
    Mautic.builderContents.find('.fr-toolbar, .fr-line-breaker').remove();

    // Remove the class attr vrom HTML tag used by Modernizer
    var htmlTags = document.getElementsByTagName('html');
    htmlTags[0].removeAttribute('class');
};

Mautic.clearFroalaStyles = function(content) {
    mQuery.each(content.find('td, th, table, [fr-original-class], [fr-original-style]'), function() {
        var el = mQuery(this);
        if (el.attr('fr-original-class')) {
            el.attr('class', el.attr('fr-original-class'));
            el.removeAttr('fr-original-class');
        }
        if (el.attr('fr-original-style')) {
            el.attr('style', el.attr('fr-original-style'));
            el.removeAttr('fr-original-style');
        }
        if (el.css('border') === '1px solid rgb(221, 221, 221)') {
            el.css('border', '');
        }
    });
    content.find('link[href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.4.0/css/font-awesome.min.css"]').remove();

    // fix Mautc's tokens in the strong tag
    content.find('strong[contenteditable="false"]').removeAttr('style');

    // data-atwho-at-query causes not working tokens
    content.find('[data-atwho-at-query]').removeAttr('data-atwho-at-query');
    return content;
}

Mautic.toggleBuilderButton = function (hide) {
    if (mQuery('.toolbar-form-buttons .toolbar-standard .btn-builder')) {
        if (hide) {
            // Move the builder button out of the group and hide it
            mQuery('.toolbar-form-buttons .toolbar-standard .btn-builder')
                .addClass('hide btn-standard-toolbar')
                .appendTo('.toolbar-form-buttons')

            mQuery('.toolbar-form-buttons .toolbar-dropdown i.fa-cube').parent().addClass('hide');
        } else {
            if (!mQuery('.btn-standard-toolbar.btn-builder').length) {
                mQuery('.toolbar-form-buttons .toolbar-standard .btn-builder').addClass('btn-standard-toolbar')
            } else {
                // Move the builder button out of the group and hide it
                mQuery('.toolbar-form-buttons .btn-standard-toolbar.btn-builder')
                    .prependTo('.toolbar-form-buttons .toolbar-standard')
                    .removeClass('hide');

                mQuery('.toolbar-form-buttons .toolbar-dropdown i.fa-cube').parent().removeClass('hide');
            }
        }
    }
};

Mautic.initSectionListeners = function() {
    Mautic.activateGlobalFroalaOptions();
    Mautic.selectedSlot = null;

    Mautic.builderContents.on('section:init', function(event, section, isNew) {
        section = mQuery(section);

        if (isNew) {
            Mautic.initSlots(section.find('[data-slot-container]'));
        }

        section.on('click', function(e) {
            var clickedSection = mQuery(this);
            var previouslyFocused = Mautic.builderContents.find('[data-section-focus]');
            var sectionWrapper = mQuery(this);
            var section = sectionWrapper.find('[data-section]');
            var focusParts = {
                'top': {},
                'right': {},
                'bottom': {},
                'left': {},
                'handle': {
                    classes: 'fa fa-arrows-v'
                },
                'delete': {
                    classes: 'fa fa-remove',
                    onClick: function() {
                        if (confirm(parent.Mautic.translate('mautic.core.builder.section_delete_warning'))) {
                            var deleteBtn = mQuery(this);
                            var focusSeciton = deleteBtn.closest('[data-section-wrapper]').remove();
                        }
                    }
                }
            };
            var sectionForm = mQuery(parent.mQuery('script[data-section-form]').html());
            var sectionFormContainer = parent.mQuery('#section-form-container');

            if (previouslyFocused.length) {

                // Unfocus other section
                previouslyFocused.remove();

                // Destroy minicolors
                sectionFormContainer.find('input[data-toggle="color"]').each(function() {
                    mQuery(this).minicolors('destroy');
                });
            }

            Mautic.builderContents.find('[data-slot-focus]').each(function() {
                if (!mQuery(e.target).attr('data-slot-focus') && !mQuery(e.target).closest('data-slot').length && !mQuery(e.target).closest('[data-slot-container]').length) {
                    mQuery(this).remove();
                }
            });

            // Highlight the section
            mQuery.each(focusParts, function (key, config) {
                var focusPart = mQuery('<div/>').attr('data-section-focus', key).addClass(config.classes);

                if (config.onClick) {
                    focusPart.on('click', config.onClick);
                }

                sectionWrapper.append(focusPart);
            });

            // Open the section customize form
            sectionFormContainer.html(sectionForm);

            // Prefill the sectionform with section color
            if (section.length && section.css('background-color') !== 'rgba(0, 0, 0, 0)') {
                sectionForm.find('#builder_section_content-background-color').val(Mautic.rgb2hex(section.css('backgroundColor')));
            }

            // Prefill the sectionform with section wrapper color
            if (sectionWrapper.css('background-color') !== 'rgba(0, 0, 0, 0)') {
                sectionForm.find('#builder_section_wrapper-background-color').val(Mautic.rgb2hex(sectionWrapper.css('backgroundColor')));
            }

            // Initialize the color picker
            sectionFormContainer.find('input[data-toggle="color"]').each(function() {
                parent.Mautic.activateColorPicker(this);
            });

            // Handle color change events
            sectionForm.on('keyup paste change touchmove', function(e) {
                var field = mQuery(e.target);
                if (section.length && field.attr('id') === 'builder_section_content-background-color') {
                    Mautic.sectionBackgroundChanged(section, field.val());
                } else if (field.attr('id') === 'builder_section_wrapper-background-color') {
                    Mautic.sectionBackgroundChanged(sectionWrapper, field.val());
                }
            });

            parent.mQuery('#section-form-container').on('change.minicolors', function(e, hex) {
                var field = mQuery(e.target);
                var focusedSectionWrapper = mQuery('[data-section-focus]').parent();
                var focusedSection = focusedSectionWrapper.find('[data-section]');
                if (focusedSection.length && field.attr('id') === 'builder_section_content-background-color') {
                    Mautic.sectionBackgroundChanged(focusedSection, field.val());
                } else if (field.attr('id') === 'builder_section_wrapper-background-color') {
                    Mautic.sectionBackgroundChanged(focusedSectionWrapper, field.val());
                }
            });
        });
    });
}

Mautic.initSections = function() {
    Mautic.initSectionListeners();
    var sectionWrappers = Mautic.builderContents.find('[data-section-wrapper]');

    // Make slots sortable
    var bodyOverflow = {};
    Mautic.sortActive = false;

    mQuery('body').sortable({
        helper: function(e, ui) {
            // Fix body overflow that messes sortable up
            bodyOverflow.overflowX = mQuery('body').css('overflow-x');
            bodyOverflow.overflowY = mQuery('body').css('overflow-y');
            mQuery('body').css({
                overflowX: 'visible',
                overflowY: 'visible'
            });

            return ui;
        },
        axis: 'y',
        items: '[data-section-wrapper]',
        handle: '[data-section-focus="handle"]',
        placeholder: 'slot-placeholder',
        connectWith: 'body',
        start: function(event, ui) {
            Mautic.sortActive = true;
            ui.placeholder.height(ui.helper.outerHeight());
        },
        stop: function(event, ui) {
            if (ui.item.hasClass('section-type-handle')) {
                // Restore original overflow
                mQuery('body', parent.document).css(bodyOverflow);

                var newSection = mQuery('<div/>')
                    .attr('data-section-wrapper', ui.item.attr('data-section-type'))
                    .html(ui.item.find('script').html());
                ui.item.replaceWith(newSection);

                Mautic.builderContents.trigger('section:init', [newSection, true]);
            } else {
                // Restore original overflow
                mQuery('body').css(bodyOverflow);
            }

            Mautic.sortActive = false;
        },
    });

    // Allow to drag&drop new sections from the section type menu
    var iframe = mQuery('#builder-template-content', parent.document).contents();
    mQuery('#section-type-container .section-type-handle', parent.document).draggable({
        iframeFix: true,
        connectToSortable: 'body',
        revert: 'invalid',
        iframeOffset: iframe.offset(),
        helper: function(e, ui) {
            // Fix body overflow that messes sortable up
            bodyOverflow.overflowX = mQuery('body', parent.document).css('overflow-x');
            bodyOverflow.overflowY = mQuery('body', parent.document).css('overflow-y');
            mQuery('body', parent.document).css({
                overflowX: 'hidden',
                overflowY: 'hidden'
            });

            var helper = mQuery(this).clone()
                .css('height', mQuery(this).height())
                .css('width', mQuery(this).width());

            return helper;
        },
        zIndex: 8000,
        cursorAt: {top: 15, left: 15},
        start: function(event, ui) {
            mQuery('#builder-template-content', parent.document).css('overflow', 'hidden');
            mQuery('#builder-template-content', parent.document).attr('scrolling', 'no');
        },
        stop: function(event, ui) {
            // Restore original overflow
            mQuery('body', parent.document).css(bodyOverflow);

            mQuery('#builder-template-content', parent.document).css('overflow', 'visible');
            mQuery('#builder-template-content', parent.document).attr('scrolling', 'yes');
        }
    }).disableSelection();

    // Initialize the slots
    sectionWrappers.each(function() {
        mQuery(this).trigger('section:init', this);
    });
};

Mautic.sectionBackgroundChanged = function(element, color) {
    if (color.length) {
        color = '#'+color;
    } else {
        color = 'transparent';
    }
    element.css('background-color', color).attr('bgcolor', color);


    // Change the color of the editor for selected slots
    mQuery(element).find('[data-slot-focus]').each(function() {
        var focusedSlot = mQuery(this).closest('[data-slot]');
        if (focusedSlot.attr('data-slot') == 'text') {
            Mautic.setTextSlotEditorStyle(parent.mQuery('#slot_text_content'), focusedSlot);
        }
    });
};

Mautic.rgb2hex = function(orig) {
    var rgb = orig.replace(/\s/g,'').match(/^rgba?\((\d+),(\d+),(\d+)/i);
    return (rgb && rgb.length === 4) ? "#" +
        ("0" + parseInt(rgb[1],10).toString(16)).slice(-2) +
        ("0" + parseInt(rgb[2],10).toString(16)).slice(-2) +
        ("0" + parseInt(rgb[3],10).toString(16)).slice(-2) : orig;
};

Mautic.initSlots = function(slotContainers) {
    if (!slotContainers) {
        slotContainers = Mautic.builderContents.find('[data-slot-container]');
    }

    Mautic.builderContents.find('a').on('click', function(e) {
        e.preventDefault();
    });

    // Make slots sortable
    var bodyOverflow = {};
    Mautic.sortActive = false;

    slotContainers.sortable({
        helper: function(e, ui) {
            // Fix body overflow that messes sortable up
            bodyOverflow.overflowX = mQuery('body').css('overflow-x');
            bodyOverflow.overflowY = mQuery('body').css('overflow-y');
            mQuery('body').css({
                overflowX: 'visible',
                overflowY: 'visible'
            });

            return ui;
        },
        items: '[data-slot]',
        handle: '[data-slot-toolbar]',
        placeholder: 'slot-placeholder',
        connectWith: '[data-slot-container]',
        start: function(event, ui) {
            Mautic.sortActive = true;
            ui.placeholder.height(ui.helper.outerHeight());

            Mautic.builderContents.find('[data-slot-focus]').each( function() {
                var focusedSlot = mQuery(this).closest('[data-slot]');
                if (focusedSlot.attr('data-slot') === 'image') {
                    // Deactivate froala toolbar
                    focusedSlot.find('img').each( function() {
                        mQuery(this).froalaEditor('popups.hideAll');
                    });
                    Mautic.builderContents.find('.fr-image-resizer.fr-active').removeClass('fr-active');
                }
            });

            Mautic.builderContents.find('[data-slot-focus]').remove();
        },
        stop: function(event, ui) {
            if (ui.item.hasClass('slot-type-handle')) {
                // Restore original overflow
                mQuery('body', parent.document).css(bodyOverflow);

                var newSlot = mQuery('<div/>')
                    .attr('data-slot', ui.item.attr('data-slot-type'))
                    .html(ui.item.find('script').html())
                ui.item.replaceWith(newSlot);

                Mautic.builderContents.trigger('slot:init', newSlot);
            } else {
                // Restore original overflow
                mQuery('body').css(bodyOverflow);
            }

            Mautic.sortActive = false;
        }
    });

    // Allow to drag&drop new slots from the slot type menu
    var iframe = mQuery('#builder-template-content', parent.document).contents();
    mQuery('#slot-type-container .slot-type-handle', parent.document).draggable({
        iframeFix: true,
        connectToSortable: '[data-slot-container]',
        revert: 'invalid',
        iframeOffset: iframe.offset(),
        helper: function(e, ui) {
            // Fix body overflow that messes sortable up
            bodyOverflow.overflowX = mQuery('body', parent.document).css('overflow-x');
            bodyOverflow.overflowY = mQuery('body', parent.document).css('overflow-y');
            mQuery('body', parent.document).css({
                overflowX: 'hidden',
                overflowY: 'hidden'
            });

            return mQuery(this).clone()
                .css('height', mQuery(this).height())
                .css('width', mQuery(this).width());
        },
        zIndex: 8000,
        cursorAt: {top: 15, left: 15},
        start: function(event, ui) {
            mQuery('#builder-template-content', parent.document).css('overflow', 'hidden');
            mQuery('#builder-template-content', parent.document).attr('scrolling', 'no');
            slotContainers.sortable('option', 'scroll', false);
        },
        stop: function(event, ui) {
            // Restore original overflow
            mQuery('body', parent.document).css(bodyOverflow);

            mQuery('#builder-template-content', parent.document).css('overflow', 'visible');
            mQuery('#builder-template-content', parent.document).attr('scrolling', 'yes');
            slotContainers.sortable('option', 'scroll', true);
        }
    }).disableSelection();

    iframe.on('scroll', function() {
        mQuery('#slot-type-container .slot-type-handle', parent.document).draggable("option", "cursorAt", { top: -1 * iframe.scrollTop() + 15 });
    });

    // Initialize the slots
    slotContainers.find('[data-slot]').each(function() {
        mQuery(this).trigger('slot:init', this);
    });
};

Mautic.initSlotListeners = function() {
    Mautic.activateGlobalFroalaOptions();
    Mautic.builderSlots = [];
    Mautic.selectedSlot = null;

    Mautic.builderContents.on('slot:selected', function(event, slot) {
        slot = mQuery(slot);
        Mautic.builderContents.find('[data-slot-focus]').remove();
        var focus = mQuery('<div/>').attr('data-slot-focus', true);
        slot.append(focus);
    });

    Mautic.builderContents.on('slot:init', function(event, slot) {
        slot = mQuery(slot);
        var type = slot.attr('data-slot');

        // initialize the drag handle
        var slotToolbar = mQuery('<div/>').attr('data-slot-toolbar', true);
        var deleteLink = mQuery('<a><i class="fa fa-lg fa-times"></i></a>')
            .attr('data-slot-action', 'delete')
            .attr('alt', 'delete')
            .addClass('btn btn-delete btn-default');
        deleteLink.appendTo(slotToolbar);

        Mautic.builderContents.find('[data-slot-focus]').remove();
        var focus = mQuery('<div/>').attr('data-slot-focus', true);

        slot.hover(function() {
            if (Mautic.sortActive) {
                // don't activate while sorting

                return;
            }

            slot.append(focus);
            deleteLink.click(function(e) {
                slot.trigger('slot:destroy', {slot: slot, type: type});
                mQuery.each(Mautic.builderSlots, function(i, slotParams) {
                    if (slotParams.slot.is(slot)) {
                        Mautic.builderSlots.splice(i, 1);
                        return false; // break the loop
                    }
                });
                slot.remove();
                focus.remove();
            });

            if (slot.offset().top < 25) {
                // If at the top of the page, move the toolbar to be visible
                slotToolbar.css('top', '0');
            } else {
                slotToolbar.css('top', '-24px');
            }

            slot.append(slotToolbar);
        }, function() {
            if (Mautic.sortActive) {
                // don't activate while sorting

                return;
            }

            slotToolbar.remove();
            focus.remove();
        });

        slot.on('click', function() {
            Mautic.deleteCodeModeSlot();

            var clickedSlot = mQuery(this);

            // Trigger the slot:change event
            clickedSlot.trigger('slot:selected', clickedSlot);

            // Destroy previously initiated minicolors
            var minicolors = parent.mQuery('#slot-form-container .minicolors');
            if (minicolors.length) {
                parent.mQuery('#slot-form-container input[data-toggle="color"]').each(function() {
                    mQuery(this).minicolors('destroy');
                });
                parent.mQuery('#slot-form-container').off('change.minicolors');
            }

            if (parent.mQuery('#slot-form-container').find('textarea.editor')) {
                // Deactivate all popups
                parent.mQuery('#slot-form-container').find('textarea.editor').each( function() {
                    parent.mQuery(this).froalaEditor('popups.hideAll');
                });
            }

            // Update form in the Customize tab to the form of the focused slot type
            var focusType = clickedSlot.attr('data-slot');
            var focusForm = mQuery(parent.mQuery('script[data-slot-type-form="'+focusType+'"]').html());
            parent.mQuery('#slot-form-container').html(focusForm);

            // Prefill the form field values with the values from slot attributes if any
            parent.mQuery.each(clickedSlot.get(0).attributes, function(i, attr) {
                var regex = /data-param-(.*)/;
                var match = regex.exec(attr.name);

                console.log('Attribute', attr);
                console.log('Match', match);
                if (match !== null) {

                    focusForm.find('input[type="text"][data-slot-param="'+match[1]+'"]').val(attr.value);
                    focusForm.find('input[type="radio"][data-slot-param="'+match[1]+'"][value="'+attr.value+'"]').prop('checked', true);

                    var selectField = focusForm.find('select[data-slot-param="'+match[1]+'"]');

                    if (selectField.length) {
                        selectField.val(attr.value)
                    }

                    // URL fields
                    var urlField = focusForm.find('input[type="url"][data-slot-param="'+match[1]+'"]');

                    if (urlField.length) {
                        urlField.val(attr.value);
                    }

                    // Number fields
                    var numberField = focusForm.find('input[type="number"][data-slot-param="'+match[1]+'"]');

                    if (numberField.length) {
                        numberField.val(attr.value);
                    }

                    var radioField = focusForm.find('input[type="radio"][data-slot-param="'+match[1]+'"][value="'+attr.value+'"]');

                    if (radioField.length) {
                        radioField.parent('.btn').addClass('active');
                        radioField.attr('checked', true);
                    }
                }
            });

            focusForm.on('keyup change', function(e) {
                var field = mQuery(e.target);

                // Store the slot settings as attributes
                clickedSlot.attr('data-param-'+field.attr('data-slot-param'), field.val());

                // Trigger the slot:change event
                clickedSlot.trigger('slot:change', {slot: clickedSlot, field: field, type: focusType});
            });

            focusForm.find('.btn').on('click', function(e) {
                var field = mQuery(this).find('input:radio');

                if (field.length) {
                    // Store the slot settings as attributes
                    clickedSlot.attr('data-param-'+field.attr('data-slot-param'), field.val());

                    // Trigger the slot:change event
                    clickedSlot.trigger('slot:change', {slot: clickedSlot, field: field, type: focusType});
                }
            });

            // Initialize the color picker
            focusForm.find('input[data-toggle="color"]').each(function() {
                parent.Mautic.activateColorPicker(this, {
                    change: function() {
                        var field = mQuery(this);

                        // Store the slot settings as attributes
                        clickedSlot.attr('data-param-'+field.attr('data-slot-param'), field.val());

                        clickedSlot.trigger('slot:change', {slot: clickedSlot, field: field, type: focusType});
                    }
                });
            });

            // initialize code mode slots
            if ('codemode' === type) {
                Mautic.codeMode = true;
                var rawTokens = [];
                var element = focusForm.find('#slot_codemode_content')[0];
                if (element) {
                    Mautic.builderCodeMirror = CodeMirror.fromTextArea(element, {
                        //value: slot.find('#codemodeHtmlContainer').html(),
                        lineNumbers: true,
                        mode: 'htmlmixed',
                        extraKeys: {"Ctrl-Space": "autocomplete"},
                        lineWrapping: true,
                        // hintOptions: {
                        //     hint: function (editor) {
                        //         var cursor = editor.getCursor();
                        //         var currentLine = editor.getLine(cursor.line);
                        //         var start = cursor.ch;
                        //         var end = start;
                        //         while (end < currentLine.length && /[\w|}$]+/.test(currentLine.charAt(end))) ++end;
                        //         while (start && /[\w|{$]+/.test(currentLine.charAt(start - 1))) --start;
                        //         var curWord = start != end && currentLine.slice(start, end);
                        //         var regex = new RegExp('^' + curWord, 'i');
                        //         return {
                        //             list: (!curWord ? rawTokens : mQuery(rawTokens).filter(function (idx) {
                        //                 return (rawTokens[idx].indexOf(curWord) !== -1);
                        //             })),
                        //             from: CodeMirror.Pos(cursor.line, start),
                        //             to: CodeMirror.Pos(cursor.line, end)
                        //         };
                        //     }
                        // }
                    });
                    Mautic.builderCodeMirror.getDoc().setValue(slot.find('#codemodeHtmlContainer').html());
                    // Mautic.builderCodeMirror.on('mousedown', function(instance, e){
                    //     console.log(Mautic.builderCodeMirror);
                    //     instance.focus();
                    // });
                    Mautic.keepPreviewAlive(null, slot.find('#codemodeHtmlContainer'));
                }
            }

            focusForm.find('textarea.editor').each(function() {
                var theEditor = this;
                var slotHtml = parent.mQuery('<div/>').html(clickedSlot.html());
                slotHtml.find('[data-slot-focus]').remove();
                slotHtml.find('[data-slot-toolbar]').remove();

                var buttons = ['undo', 'redo', '|', 'bold', 'italic', 'underline', 'paragraphFormat', 'fontFamily', 'fontSize', 'color', 'align', 'formatOL', 'formatUL', 'quote', 'clearFormatting', 'token', 'insertLink', 'insertImage', 'insertGatedVideo', 'insertTable', 'html', 'fullscreen'];

                var builderEl = parent.mQuery('.builder');

                if (builderEl.length && builderEl.hasClass('email-builder')) {
                    buttons = parent.mQuery.grep(buttons, function (value) {
                        return value != 'insertGatedVideo';
                    });
                }

                var froalaOptions = {
                    toolbarButtons: buttons,
                    toolbarButtonsMD: buttons,
                    toolbarButtonsSM: buttons,
                    toolbarButtonsXS: buttons,
                    linkList: [], // TODO push here the list of tokens from Mautic.getPredefinedLinks
                    imageEditButtons: ['imageReplace', 'imageAlign', 'imageRemove', 'imageAlt', 'imageSize', '|', 'imageLink', 'linkOpen', 'linkEdit', 'linkRemove']
                };

                // init AtWho in a froala editor
                parent.mQuery(this).on('froalaEditor.initialized', function (e, editor) {
                    parent.Mautic.initAtWho(editor.$el, parent.Mautic.getBuilderTokensMethod(), editor);

                    Mautic.setTextSlotEditorStyle(editor.$el, clickedSlot);
                });

                parent.mQuery(this).on('froalaEditor.contentChanged', function (e, editor) {
                    var slotHtml = mQuery('<div/>').append(parent.mQuery(theEditor).froalaEditor('html.get'));
                    clickedSlot.html(slotHtml.html());
                });
                parent.mQuery(this).val(slotHtml.html());

                parent.mQuery(this).froalaEditor(parent.mQuery.extend({}, Mautic.basicFroalaOptions, froalaOptions));
            });
        });

        // Initialize different slot types
        if (type === 'image' || type === 'imagecaption' || type === 'imagecard') {
            var image = slot.find('img');
            // fix of badly destroyed image slot
            image.removeAttr('data-froala.editor');

            image.on('froalaEditor.click', function (e, editor) {
                slot.closest('[data-slot]').trigger('click');
            });

            // Init Froala editor
            var froalaOptions = mQuery.extend({}, Mautic.basicFroalaOptions, {
                    linkList: [], // TODO push here the list of tokens from Mautic.getPredefinedLinks
                    imageEditButtons: ['imageReplace', 'imageAlign', 'imageAlt', 'imageSize', '|', 'imageLink', 'linkOpen', 'linkEdit', 'linkRemove'],
                    useClasses: false
                }
            );
            image.froalaEditor(froalaOptions);
        } else if (type === 'button') {
            slot.find('a').click(function(e) {
                e.preventDefault();
            });
        }

        // Store the slot to a global var
        Mautic.builderSlots.push({slot: slot, type: type});
    });

    Mautic.builderContents.on('slot:change', function(event, params) {
        // Change some slot styles when the values are changed in the slot edit form
        var fieldParam = params.field.attr('data-slot-param');
        var type = params.type;

        Mautic.clearSlotFormError(fieldParam);

        if (fieldParam === 'padding-top' || fieldParam === 'padding-bottom') {
            params.slot.css(fieldParam, params.field.val() + 'px');
        } else if ('glink' === fieldParam || 'flink' === fieldParam || 'tlink' === fieldParam) {
            params.slot.find('#'+fieldParam).attr('href', params.field.val());
        } else if (fieldParam === 'href') {
            params.slot.find('a').attr('href', params.field.val());
        } else if (fieldParam === 'link-text') {
            params.slot.find('a').text(params.field.val());
        } else if (fieldParam === 'float') {
            var values = ['left', 'center', 'right'];
            params.slot.find('a').parent().attr('align', values[params.field.val()]);
        } else if (fieldParam === 'caption') {
            params.slot.find('figcaption').text(params.field.val());
        } else if (fieldParam === 'cardcaption') {
            params.slot.find('td.imagecard-caption').text(params.field.val());
        } else if (fieldParam === 'text-align') {
            var values = ['left', 'center', 'right'];
            if (type === 'imagecard') {
                params.slot.find('.imagecard-caption').css(fieldParam, values[params.field.val()]);
            } else if (type === 'imagecaption') {
                params.slot.find('figcaption').css(fieldParam, values[params.field.val()]);
            }
        } else if (fieldParam === 'align') {
            Mautic.builderContents.find('[data-slot-focus]').each( function() {
                var focusedSlot = mQuery(this).closest('[data-slot]');
                if (focusedSlot.attr('data-slot') == 'image') {
                    // Deactivate froala toolbar
                    focusedSlot.find('img').each( function() {
                        mQuery(this).froalaEditor('popups.hideAll');
                    });
                    Mautic.builderContents.find('.fr-image-resizer.fr-active').removeClass('fr-active');
                }
            });

            var values = ['left', 'center', 'right'];
            if ('socialfollow' === type) {
                params.slot.find('div.socialfollow').css('text-align', values[params.field.val()]);
            } else if ('imagecaption' === type) {
                params.slot.find('figure').css('text-align', values[params.field.val()]);
            } else if ('imagecard' === type) {
                params.slot.find('td.imagecard-image').css('text-align', values[params.field.val()]);
            } else {
                params.slot.find('img').closest('div').css('text-align', values[params.field.val()]);
            }
        } else if (fieldParam === 'button-size') {
            var values = [
                {padding: '10px 13px', fontSize: '14px'},
                {padding: '15px 20px', fontSize: '20px'},
                {padding: '22px 30px', fontSize: '30px'}
            ];
            params.slot.find('a').css(values[params.field.val()]);
        } else if (fieldParam === 'caption-color') {
            params.slot.find('.imagecard-caption').css('background-color', '#' + params.field.val());
        } else if (fieldParam === 'background-color') {
            if ('imagecard' === type) {
                params.slot.find('.imagecard').css(fieldParam, '#' + params.field.val());
            } else {
                params.slot.find('a').css(fieldParam, '#' + params.field.val());
                params.slot.find('a').attr('background', '#' + params.field.val());
            }
        } else if (fieldParam === 'color') {
            if ('imagecard' === type) {
                params.slot.find('.imagecard-caption').css(fieldParam, '#' + params.field.val());
            } else if ('imagecaption' === type) {
                params.slot.find('figcaption').css(fieldParam, '#' + params.field.val());
            } else {
                params.slot.find('a').css(fieldParam, '#' + params.field.val());
            }
        } else if (/gatedvideo/.test(fieldParam)) {
            // Handle gatedVideo replacements
            var toInsert = fieldParam.split('-')[1];
            var insertVal = params.field.val();

            if (toInsert === 'url') {
                var videoProvider = Mautic.getVideoProvider(insertVal);

                if (videoProvider == null) {
                    Mautic.slotFormError(fieldParam, 'Please enter a valid YouTube, Vimeo, or MP4 url.');
                } else {
                    params.slot.find('source')
                        .attr('src', insertVal)
                        .attr('type', videoProvider);
                }
            } else if (toInsert === 'gatetime') {
                params.slot.find('video').attr('data-gate-time', insertVal);
            } else if (toInsert === 'formid') {
                params.slot.find('video').attr('data-form-id', insertVal);
            } else if (toInsert === 'height') {
                params.slot.find('video').attr('height', insertVal);
            } else if (toInsert === 'width') {
                params.slot.find('video').attr('width', insertVal);
            }
        }

        if (params.type == 'text') {
            Mautic.setTextSlotEditorStyle(parent.mQuery('#slot_text_content'), params.slot);
        }
    });

    Mautic.builderContents.on('slot:destroy', function(event, params) {
        Mautic.deleteCodeModeSlot();
        if (params.type === 'image') {
            var image = params.slot.find('img');
            if (typeof image !== 'undefined' && image.hasClass('fr-view')) {
                image.froalaEditor('destroy');
                image.removeAttr('data-froala.editor');
                image.removeClass('fr-view');
            }
        }

        // Remove Symfony toolbar
        Mautic.builderContents.find('.sf-toolbar').remove();
    });
};

Mautic.deleteCodeModeSlot = function() {
    Mautic.killLivePreview();
    Mautic.destroyCodeMirror();
    delete Mautic.codeMode;
};

Mautic.clearSlotFormError = function(field) {
    var customizeSlotField = parent.mQuery('#customize-form-container').find('[data-slot-param="'+field+'"]');

    if (customizeSlotField.length) {
        customizeSlotField.attr('style', '');
        customizeSlotField.next('[data-error]').remove();
    }
};

Mautic.slotFormError = function (field, message) {
    var customizeSlotField = parent.mQuery('#customize-form-container').find('[data-slot-param="'+field+'"]');

    if (customizeSlotField.length) {
        customizeSlotField.css('border-color', 'red');

        if (message.length) {
            var messageContainer = mQuery('<p/>')
                .text(message)
                .attr('data-error', 'true')
                .css({
                    color: 'red',
                    padding: '5px 0'
                });

            messageContainer.insertAfter(customizeSlotField);
        }
    }
};

Mautic.getVideoProvider = function(url) {
    var providers = [
        {
            test_regex: /^.*((youtu.be)|(youtube.com))\/((v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))?\??v?=?([^#\&\?]*).*/,
            provider: 'video/youtube'
        },
        {
            test_regex: /^.*(vimeo\.com\/)((channels\/[A-z]+\/)|(groups\/[A-z]+\/videos\/))?([0-9]+)/,
            provider: 'video/vimeo'
        },
        {
            test_regex: /mp4/,
            provider: 'video/mp4'
        }
    ];

    for (var i = 0; i < providers.length; i++) {
        var vp = providers[i];
        if (vp.test_regex.test(url)) {
            return vp.provider;
        }
    }

    return null;
};

Mautic.setTextSlotEditorStyle = function(editorEl, slot)
{
    // Set the editor CSS to that of the slot
    var wrapper = parent.mQuery(editorEl).closest('.form-group').find('.fr-wrapper .fr-element').first();

    if (typeof wrapper == 'undefined') {
        return;
    }

    if (typeof slot.attr('style') !== 'undefined') {
        wrapper.attr('style', slot.attr('style'));
    }

    mQuery.each(['background-color', 'color', 'font-family', 'font-size', 'line-height', 'text-align'], function(key, style) {
        var overrideStyle = Mautic.getSlotStyle(slot, style, false);
        if (overrideStyle) {
            wrapper.css(style, overrideStyle);
        }
    });
};

Mautic.getSlotStyle = function(slot, styleName, fallback) {
    if ('background-color' == styleName) {
        // Get this browser's take on no fill
        // Must be appended else Chrome etc return 'initial'
        var temp = mQuery('<div style="background:none;display:none;"/>').appendTo('body');
        var transparent = temp.css(styleName);
        temp.remove();
    }

    var findStyle = function (slot) {
        function test(elem) {
            if ('background-color' == styleName) {
                if (typeof elem.attr('bgcolor') !== 'undefined') {
                    // Email tables
                    return elem.attr('bgcolor');
                }

                if (elem.css(styleName) == transparent) {
                    return !elem.is('body') ? test(elem.parent()) : fallback || transparent;
                } else {
                    return elem.css(styleName);
                }
            } else if (typeof elem.css(styleName) !== 'undefined') {
                return elem.css(styleName);
            } else {
                return !elem.is('body') ? test(elem.parent()) : fallback;
            }
        }

        return test(slot);
    };

    return findStyle(slot);
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


Mautic.getPredefinedLinks = function(callback) {
    var linkList = [];
    Mautic.getTokens(Mautic.getBuilderTokensMethod(), function(tokens) {
        if (tokens.length) {
            mQuery.each(tokens, function(token, label) {
                if (token.startsWith('{pagelink=') ||
                    token.startsWith('{assetlink=') ||
                    token.startsWith('{webview_url') ||
                    token.startsWith('{unsubscribe_url')) {

                    linkList.push({
                        text: label,
                        href: token
                    });
                }
            });
        }
        return callback(linkList);
    });
}

// Init inside the builder's iframe
mQuery(function() {
    if (parent && parent.mQuery && parent.mQuery('#builder-template-content').length) {
        Mautic.builderContents = mQuery('body');
        if (!parent.Mautic.codeMode) {
            Mautic.initSlotListeners();
            Mautic.initSections();
            Mautic.initSlots();
        }
    }
});
