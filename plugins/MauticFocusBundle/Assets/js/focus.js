Mautic.focusOnLoad = function () {
    if (typeof Mautic.loadedPreviewImage !== 'undefined') {
        delete Mautic.loadedPreviewImage;
    }

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

        mQuery('#focus_html').on('froalaEditor.contentChanged', function (e, editor) {
            mQuery('.mf-content').html(editor.html.get());
        });
    } else {
        Mautic.initDateRangePicker();
    }
};

Mautic.focusOnUnload = function () {
    if (typeof Mautic.loadedPreviewImage != 'undefined') {
        delete Mautic.loadedPreviewImage;
    }

    if (typeof Mautic.focusStatsChart != 'undefined') {
        Mautic.focusStatsChart.destroy();
    }
};

Mautic.launchFocusBuilder = function (forceFetch) {
    mQuery('.website-placeholder').addClass('hide');
    mQuery('body').css('overflow-y', 'hidden');

    // Prevent preview updates till the website snapshot is loaded
    Mautic.ignoreMauticFocusPreviewUpdate = true;

    if (!mQuery('#builder-overlay').length) {
        var builderCss = {
            margin: "0",
            padding: "0",
            border: "none",
            width: "100%",
            height: "100%"
        };

        var spinnerLeft = (mQuery(document).width() - 300) / 2;
        var overlay = mQuery('<div id="builder-overlay" class="modal-backdrop fade in"><div style="position: absolute; top:50%; left:' + spinnerLeft + 'px"><i class="fa fa-spinner fa-spin fa-5x"></i></div></div>').css(builderCss).appendTo('.builder-content');
    } else {
        mQuery('#builder-overlay').removeClass('hide');
    }

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
        mQuery('#builder-overlay').addClass('hide');
        mQuery('.btn-close-builder').prop('disabled', false);
        mQuery('#websiteUrlPlaceholderInput').prop('disabled', false);
    } else if (forceFetch || typeof Mautic.loadedPreviewImage == 'undefined' || Mautic.loadedPreviewImage != url) {
        Mautic.loadedPreviewImage = url;

        // Fetch image
        var data = {
            id: mQuery('#focus_unlockId').val(),
            website: url
        }

        mQuery('.preview-body').html('');
        Mautic.ajaxActionRequest('plugin:focus:getWebsiteSnapshot', data, function (response) {
            mQuery('#builder-overlay').addClass('hide');
            mQuery('.btn-close-builder').prop('disabled', false);

            if (response.image) {
                // Enable droppers
                mQuery('.btn-dropper').removeClass('disabled');
                mQuery('#websiteUrlPlaceholderInput').prop('disabled', true);

                var canvas = document.getElementById('websiteCanvas');
                var context = canvas.getContext('2d');
                var img = new Image();
                img.onload = function () {
                    mQuery('#websiteScreenshot').removeClass('css-device css-device--mobile');

                    // Get the width of the
                    var useWidth = mQuery('.website-preview').width();

                    if (useWidth > img.width) {
                        useWidth = img.width;
                    }

                    // Get height proportionate to image width used
                    var ratio = useWidth / img.width;
                    var useHeight = img.height * ratio;

                    mQuery('#websiteCanvas').attr({width: useWidth, height: useHeight})
                    context.drawImage(this, 0, 0, useWidth, useHeight);

                    function findPos(obj) {
                        var current_left = 0, current_top = 0;
                        if (obj.offsetParent) {
                            do {
                                current_left += obj.offsetLeft;
                                current_top += obj.offsetTop;
                            } while (obj = obj.offsetParent);
                            return {x: current_left, y: current_top};
                        }
                        return undefined;
                    }

                    function rgbToHex(r, g, b) {
                        if (r > 255 || g > 255 || b > 255)
                            throw "Invalid color component";
                        return ((r << 16) | (g << 8) | b).toString(16);
                    }

                    mQuery('#websiteCanvas').off('click.canvas');
                    mQuery('#websiteCanvas').on('click.canvas', function (e) {
                        // Check that a dropper is active
                        if (mQuery('button.btn-dropper.active').length) {
                            var dropper = mQuery('button.btn-dropper.active').data('dropper');

                            var position = findPos(this);
                            var x = e.pageX - position.x;
                            var y = e.pageY - position.y;
                            var canvas = this.getContext('2d');
                            var p = canvas.getImageData(x, y, 1, 1).data;
                            var hex = "#" + ("000000" + rgbToHex(p[0], p[1], p[2])).slice(-6);

                            mQuery('#' + dropper).minicolors('value', hex);
                        }
                    });

                    if (mQuery('.btn-viewport').data('viewport') == 'mobile') {
                        mQuery('#websiteScreenshot').addClass('css-device css-device--mobile');
                    }
                };

                img.src = (mQuery('.btn-viewport').data('viewport') == 'mobile') ? response.image.mobile : response.image.desktop;

                if (forceFetch) {
                    // Only override colors if fetch button is clicked
                    if (response.colors) {
                        if ('bar' != mQuery('#focus_style').val() && response.colors.textColor == '#ffffff') {
                            response.colors.textColor = '#000000';
                        }
                        mQuery('#focus_properties_colors_primary').minicolors('value', response.colors.primaryColor);
                        mQuery('#focus_properties_colors_text').minicolors('value', response.colors.textColor);
                        mQuery('#focus_properties_colors_button').minicolors('value', response.colors.buttonColor);
                        mQuery('#focus_properties_colors_button_text').minicolors('value', response.colors.buttonTextColor);
                    } else if (!response.ignoreDefaultColors) {
                        Mautic.setFocusDefaultColors();
                    }

                    if (response.palette) {
                        // Wipe them out
                        mQuery('.site-color-list').html('').removeClass('hide');

                        var colorTypes = ['primary', 'text', 'button', 'button_text'];
                        response.palette.push('#ffffff');
                        response.palette.push('#000000');
                        mQuery.each(response.palette, function (index, value) {
                            mQuery.each(colorTypes, function (ctIndex, ctValue) {
                                mQuery('<span class="label label-site-color" />')
                                    .css('backgroundColor', value)
                                    .css('border', '1px solid #cccccc')
                                    .on('click', function () {
                                        mQuery('#focus_properties_colors_' + ctValue).minicolors('value', value);
                                    })
                                    .appendTo('#' + ctValue + '_site_colors');
                            });
                        });
                    }
                }

                Mautic.ignoreMauticFocusPreviewUpdate = false;
                Mautic.focusUpdatePreview();
            } else {
                mQuery('.website-placeholder').removeClass('hide');
                mQuery('#websiteUrlPlaceholderInput').prop('disabled', false);

                // Disable droppers
                mQuery('.btn-dropper').addClass('disabled');

                Mautic.ignoreMauticFocusPreviewUpdate = false;
            }
        });
    } else {
        mQuery('#builder-overlay').addClass('hide');
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

    // Kill the overlay
    mQuery('#builder-overlay').remove();

    // Hide builder
    mQuery('.builder').removeClass('builder-active').addClass('hide');

    mQuery('body').css('overflow-y', '');
};

Mautic.focusUpdatePreview = function () {
    mQuery('.preview-body').html('');
    // Generate a preview
    var data = mQuery('form[name=focus]').formToArray();
    Mautic.ajaxActionRequest('plugin:focus:generatePreview', data, function (response) {
        var container = mQuery('<div />').html(response.style);
        var innerContainer = mQuery('<div />').html(response.html);

        if (mQuery('.btn-viewport').data('viewport') == 'mobile') {
            innerContainer.addClass('mf-responsive');
        } else {
            innerContainer.removeClass('mf-responsive');
        }

        container.append(innerContainer);

        mQuery('.preview-body').html(container);

        if (!mQuery('.mf-bar').length && mQuery('.builder-content').length) {
            mQuery('.builder-content').on('click', function () {
                Mautic.closeFocusModal(mQuery('#focus_style').val());
            });
            mQuery('.mautic-focus').on('click', function (e) {
                e.stopPropagation();
            });
        }

    });
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