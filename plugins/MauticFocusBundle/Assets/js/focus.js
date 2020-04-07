Mautic.disabledFocusActions = function(opener) {
    if (typeof opener == 'undefined') {
        opener = window;
    }
    var email = opener.mQuery('#campaignevent_properties_focus').val();

    var disabled = email === '' || email === null;

    // opener.mQuery('#campaignevent_properties_editFocusButton').prop('disabled', disabled);
    // opener.mQuery('#campaignevent_properties_previewFocusButton').prop('disabled', disabled);
};

Mautic.standardFocusUrl = function(options) {
    if (!options) {
        return;
    }

    var url = options.windowUrl;
    if (url) {
        var editFocusKey = '/focus/edit/focusId';
        var previewFocusKey = '/focus/preview/focusId';
        if (url.indexOf(editFocusKey) > -1 ||
            url.indexOf(previewFocusKey) > -1) {
            options.windowUrl = url.replace('focusId', mQuery('#campaignevent_properties_focus').val());
        }
    }

    return options;
};

Mautic.focusOnLoad = function () {
    if (mQuery('.builder').length) {
        // Activate droppers
        mQuery('.btn-dropper').each(function () {
            mQuery(this).click(function () {
                if (mQuery(this).hasClass('active')) {
                    // Deactivate
                    mQuery(this).removeClass('active btn-primary').addClass('btn-default');

                    mQuery('#websiteCanvas').css('cursor', 'inherit');
                } else {
                    // Remove active state from all the droppers
                    mQuery('.btn-dropper').removeClass('active btn-primary').addClass('btn-default');

                    // Activate this dropper
                    mQuery(this).removeClass('btn-default').addClass('active btn-primary');

                    // Activate the cross hairs for image
                    mQuery('#websiteCanvas').css('cursor', 'crosshair');
                }
            });
        });

        // Update type
        var activateType = function (el, thisType) {
            mQuery('[data-focus-type]').removeClass('focus-active');
            mQuery(el).addClass('focus-active');

            mQuery('#focusFormContent').removeClass(function (index, css) {
                return (css.match(/(^|\s)focus-type\S+/g) || []).join(' ');
            }).addClass('focus-type-' + thisType);

            mQuery('.focus-type-header').removeClass('text-danger');
            mQuery('#focus_type').val(thisType);

            var props = '.focus-' + thisType + '-properties';
            mQuery('#focusTypeProperties').appendTo(
                mQuery(props)
            ).removeClass('hide');

            mQuery('#focusType .focus-properties').each(function () {
                if (!mQuery(this).is(':hidden') && mQuery(this).data('focus-type') != thisType) {
                    mQuery(this).slideUp('fast', function () {
                        mQuery(this).hide();
                    });
                }
            });
            if (mQuery(props).length) {
                if (mQuery(props).is(':hidden')) {
                    mQuery(props).slideDown('fast');
                }
            }
        }

        mQuery('[data-focus-type]').on({
            click: function () {
                var thisType = mQuery(this).data('focus-type');

                if (mQuery('#focus_type').val() == thisType) {
                    return;
                }

                activateType(this, thisType);

                Mautic.focusUpdatePreview();
            },
            mouseenter: function () {
                mQuery(this).addClass('focus-hover');
            },
            mouseleave: function () {
                mQuery(this).removeClass('focus-hover');
            }
        });

        var activateStyle = function (el, thisStyle) {
            mQuery('[data-focus-style]').removeClass('focus-active');
            mQuery(el).addClass('focus-active');

            if (!mQuery('#focusType').hasClass('hidden-focus-style-all')) {
                mQuery('#focusType').addClass('hidden-focus-style-all');
            }

            mQuery('#focusFormContent').removeClass(function (index, css) {
                return (css.match(/(^|\s)focus-style\S+/g) || []).join(' ');
            }).addClass('focus-style-' + thisStyle);

            mQuery('.focus-style-header').removeClass('text-danger');
            mQuery('#focus_style').val(thisStyle);

            var props = '.focus-' + thisStyle + '-properties';
            mQuery('#focusStyleProperties').appendTo(
                mQuery(props)
            ).removeClass('hide');

            mQuery('#focusStyle .focus-properties').each(function () {
                if (!mQuery(this).is(':hidden')) {
                    mQuery(this).slideUp('fast', function () {
                        mQuery(this).hide();
                    });
                }
            });
            if (mQuery(props).length) {
                if (mQuery(props).is(':hidden')) {
                    mQuery(props).slideDown('fast');
                }
            }
        };

        // Update style
        mQuery('[data-focus-style]').on({
            click: function () {
                var thisStyle = mQuery(this).data('focus-style');

                if (mQuery('#focus_style').val() == thisStyle) {
                    return;
                }

                activateStyle(this, thisStyle);
                Mautic.focusUpdatePreview();
            },
            mouseenter: function () {
                mQuery(this).addClass('focus-hover');
            },
            mouseleave: function () {
                mQuery(this).removeClass('focus-hover');
            }
        });

        // Select the current type and style
        var currentType = mQuery('#focus_type').val();
        if (currentType) {
            activateType(mQuery('[data-focus-type="' + currentType + '"]'), currentType);
        }

        var currentStyle = mQuery('#focus_style').val();
        if (currentStyle) {
            activateStyle(mQuery('[data-focus-style="' + currentStyle + '"]'), currentStyle);
        }

        mQuery('#focus_properties_content_font').on('chosen:showing_dropdown', function () {
            // Little trickery to add style to the chosen dropdown font list
            var arrayIndex = 1;
            mQuery('#focus_properties_content_font option').each(function () {
                mQuery('#focus_properties_content_font_chosen li[data-option-array-index="' + arrayIndex + '"]').css('fontFamily', mQuery(this).attr('value'));
                arrayIndex++;
            });
        });

        mQuery('.btn-fetch').on('click', function () {
            var url = mQuery('#websiteUrlPlaceholderInput').val();
            if (url) {
                mQuery('#focus_website').val(url);
                Mautic.launchFocusBuilder(true);
            } else {
                return;
            }
        });

        mQuery('.btn-viewport').on('click', function () {
            if (mQuery(this).data('viewport') == 'mobile') {
                mQuery('.btn-viewport i').removeClass('fa-desktop fa-2x').addClass('fa-mobile-phone fa-3x');
                mQuery(this).data('viewport', 'desktop');
                Mautic.launchFocusBuilder(true);
            } else {
                mQuery('.btn-viewport i').removeClass('fa-mobile-phone fa-3x').addClass('fa-desktop fa-2x');
                mQuery(this).data('viewport', 'mobile');
                Mautic.launchFocusBuilder(true);
            }
        });

        mQuery('#focus_editor').on('froalaEditor.contentChanged', function (e, editor) {
            var content = editor.html.get();

            if (content.indexOf('{focus_form}') !== -1) {
                Mautic.focusUpdatePreview();
            } else {
                mQuery('.mf-content').html(content);
            }

        });
    } else {
        Mautic.initDateRangePicker();
    }
};

Mautic.focusOnUnload = function () {
    if (typeof Mautic.focusStatsChart != 'undefined') {
        Mautic.focusStatsChart.destroy();
    }
};

Mautic.launchFocusBuilder = function (forceFetch) {
    mQuery('.website-placeholder').addClass('hide');
    mQuery('body').css('overflow-y', 'hidden');

    // Prevent preview updates till the website snapshot is loaded
    Mautic.ignoreMauticFocusPreviewUpdate = true;

    // Disable the close button until everything is loaded
    mQuery('.btn-close-builder').prop('disabled', true);

    // Activate the builder
    mQuery('.builder').addClass('builder-active').removeClass('hide');

    var url = mQuery('#focus_website').val();

    if (!url) {
        if (!mQuery('#focus_unlockId').val()) {
            Mautic.setFocusDefaultColors();
        }
        mQuery('.website-placeholder').removeClass('hide');
        mQuery('.btn-close-builder').prop('disabled', false);
        mQuery('#websiteUrlPlaceholderInput').prop('disabled', false);
    } else if (forceFetch) {
        // Fetch image
        var data = {
            id: mQuery('#focus_unlockId').val(),
            website: url
        }

        var builderCss = {
            margin: "0",
            padding: "0",
            border: "none",
            width: "100%",
            // @todo recalculate when window resize
            height: mQuery('#websiteScreenshot').height(), // 100% does not work. Needs to be specified
            overflow: "hidden", // Disable scrolling with scrolling="no" attr
            "pointer-events": "none", // Disable clicks in iframe
        };

        // Async load in iframe
        mQuery('.preview-body').html('<iframe src="'+url+'" scrolling="no"></iframe>');
        mQuery('iframe').css(builderCss);

        mQuery('.btn-close-builder').prop('disabled', false);
    } else {
        mQuery('.btn-close-builder').prop('disabled', false);

        Mautic.ignoreMauticFocusPreviewUpdate = false;
        if (url) {
            Mautic.focusUpdatePreview();
        }
    }
};

Mautic.closeFocusBuilder = function (el) {
    // Kill preview updates
    if (typeof Mautic.ajaxActionXhr != 'undefined' && typeof Mautic.ajaxActionXhr['plugin:focus:generatePreview'] != 'undefined') {
        Mautic.ajaxActionXhr['plugin:focus:generatePreview'].abort();
        delete Mautic.ajaxActionXhr['plugin:focus:generatePreview'];
    }

    mQuery('#websiteUrlPlaceholderInput').prop('disabled', true);

    Mautic.stopIconSpinPostEvent();

    // Hide builder
    mQuery('.builder').removeClass('builder-active').addClass('hide');

    mQuery('body').css('overflow-y', '');
};

// Called when you  click on the show builder button
Mautic.focusUpdatePreview = function () {
};

Mautic.setFocusDefaultColors = function () {
    mQuery('#focus_properties_colors_primary').minicolors('value', '4e5d9d');
    mQuery('#focus_properties_colors_text').minicolors('value', (mQuery('#focus_style').val() == 'bar') ? 'ffffff' : '000000');
    mQuery('#focus_properties_colors_button').minicolors('value', 'fdb933');
    mQuery('#focus_properties_colors_button_text').minicolors('value', 'ffffff');
};

Mautic.toggleBarCollapse = function () {
    var svg = '.mf-bar-collapser-icon svg';
    var currentSize = mQuery(svg).data('transform-size');
    var currentDirection = mQuery(svg).data('transform-direction');
    var currentScale = mQuery(svg).data('transform-scale');
    var newDirection = (parseInt(currentDirection) * -1);

    setTimeout(function () {
        mQuery(svg).find('g').first().attr('transform', 'scale(' + currentScale + ') rotate(' + newDirection + ' ' + currentSize + ' ' + currentSize + ')');
        mQuery(svg).data('transform-direction', newDirection);
    }, 500);

    if (mQuery('.mf-bar-collapser').hasClass('mf-bar-collapsed')) {
        // Open
        if (mQuery('.mf-bar').hasClass('mf-bar-top')) {
            mQuery('.mf-bar').css('margin-top', 0);
        } else {
            mQuery('.mf-bar').css('margin-bottom', 0);
        }
        mQuery('.mf-bar-collapser').removeClass('mf-bar-collapsed');
    } else {
        // Collapse
        if (mQuery('.mf-bar').hasClass('mf-bar-top')) {
            mQuery('.mf-bar').css('margin-top', -60);
        } else {
            mQuery('.mf-bar').css('margin-bottom', -60);
        }
        mQuery('.mf-bar-collapser').addClass('mf-bar-collapsed');
    }
}

Mautic.closeFocusModal = function (style) {
    mQuery('.mf-' + style).remove();
    if (mQuery('.mf-' + style + '-overlay').length) {
        mQuery('.mf-' + style + '-overlay').remove();
    }
}