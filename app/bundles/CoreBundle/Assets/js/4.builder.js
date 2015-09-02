/**
 * Launch builder
 *
 * @param formName
 */
Mautic.launchBuilder = function (formName, actionName) {
    Mautic.builderMode     = (mQuery('#' + formName + '_template').val() == '') ? 'custom' : 'template';
    Mautic.builderFormName = formName;

    mQuery('body').css('overflow-y', 'hidden');

    // Activate the builder
    mQuery('.builder').addClass('builder-active').removeClass('hide');

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

    var panelHeight = (mQuery('.builder-content').css('right') == '0px') ? mQuery('.builder-panel').height() : 0,
        panelWidth = (mQuery('.builder-content').css('right') == '0px') ? 0 : mQuery('.builder-panel').width(),
        spinnerLeft = (mQuery(window).width() - panelWidth - 60) / 2,
        spinnerTop = (mQuery(window).height() - panelHeight - 60) / 2;

    var overlay     = mQuery('<div id="builder-overlay" class="modal-backdrop fade in"><div style="position: absolute; top:' + spinnerTop + 'px; left:' + spinnerLeft + 'px" class="builder-spinner"><i class="fa fa-spinner fa-spin fa-5x"></i></div></div>').css(builderCss).appendTo('.builder-content');

    // Disable the close button until everything is loaded
    mQuery('.btn-close-builder').prop('disabled', true);

    if (Mautic.builderMode == 'template') {
        // Template
        var src = mQuery('#builder_url').val();
        src += '?template=' + mQuery('#' + formName + '_template').val();

        var builder = mQuery("<iframe />", {
            css: builderCss,
            id: "builder-template-content"
        })
            .attr('src', src)
            .appendTo('.builder-content')
            .load(function () {
                var contents = mQuery(this).contents();
                // here, catch the droppable div and create a droppable widget
                contents.find('.mautic-editable').droppable({
                    iframeFix: true,
                    drop: function (event, ui) {
                        var editorId = mQuery(this).attr("id");
                        var drop     = mQuery(ui.draggable).data('drop');
                        var token    = mQuery(ui.draggable).data('token');

                        if (drop) {
                            Mautic[drop](event, ui, editorId, token);
                        } else {
                            Mautic.insertBuilderEditorToken(editorId, token);
                        }
                        mQuery(this).removeClass('over-droppable');
                    },
                    over: function (e, ui) {
                        mQuery(this).addClass('over-droppable');
                    },
                    out: function (e, ui) {
                        mQuery(this).removeClass('over-droppable');
                    }
                });

                // Activate draggables
                Mautic.activateBuilderDragTokens();

                mQuery('#builder-overlay').addClass('hide');

                mQuery('.btn-close-builder').prop('disabled', false);
            });
    } else {
        // Custom HTML

        // Add a padding to builder-content
        mQuery('.builder-content').addClass('pr-10');

        var editorId = 'builder-custom-content';
        var builder  = mQuery('<textarea />', {
            id: editorId
        }).appendTo('.builder-content');

        builder.data('token-callback', actionName + ':getBuilderTokens');
        builder.data('token-activator', '{');

        mQuery('#customHtmlDropzone').droppable({
            drop: function (event, ui) {
                var drop  = mQuery(ui.draggable).data('drop');
                var token = mQuery(ui.draggable).data('token');

                if (drop) {
                    Mautic[drop](event, ui, editorId, token);
                } else {
                    Mautic.insertBuilderEditorToken(editorId, token);
                }
                mQuery('#customHtmlDropzone').removeClass('over-droppable text-danger');
                mQuery('.custom-drop-message').addClass('hide');
                mQuery('.custom-general-message').removeClass('hide');
            }
        });

        builder.ckeditor(function() {
                CKEDITOR.instances['builder-custom-content'].resize('100%', mQuery('.builder-content').height());

                var data = CKEDITOR.instances[formName + '_customHtml'].getData();
                CKEDITOR.instances['builder-custom-content'].setData(data);

                mQuery('.btn-close-builder').prop('disabled', false);

                // Activate draggables
                Mautic.activateBuilderDragTokens();

                mQuery('#builder-overlay').addClass('hide');
            },
            {
                toolbar: 'fullpage',
                fullPage: true,
                extraPlugins: 'sourcedialog,docprops,tokens',
                width: '100%',
                allowedContent: true // Do not strip classes and the like
            }
        );
    }
};

/**
 * Set builder token draggables
 *
 * @param target
 */
Mautic.activateBuilderDragTokens = function (target) {
    if (typeof target == 'undefined') {
        target = '.builder-panel';
    }

    if (Mautic.builderMode == 'template') {
        var settings = {
            iframeFix: true,
            iframeId: 'builder-template-content',
            helper: 'clone',
            appendTo: '.builder',
            zIndex: 8000,
            scroll: true,
            scrollSensitivity: 100,
            scrollSpeed: 100,
            cursorAt: {top: 15, left: 15}
        }
    } else {
        var settings = {
            helper: 'clone',
            appendTo: '.builder',
            scroll: false,
            cursorAt: {top: 15, left: 15},
            start: function(event, ui ) {
                mQuery(ui.helper).css('max-width', mQuery(this).css('width'));
                mQuery(ui.helper).css('max-height', mQuery(this).css('height'));

                mQuery('#customHtmlDropzone').addClass('over-droppable text-danger');
                mQuery('.custom-drop-message').removeClass('hide');
                mQuery('.custom-general-message').addClass('hide');
            }
        }
    }

    //activate builder drag and drop
    mQuery(target + " *[data-token]").draggable(settings);
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
        spinnerTop = (mQuery(window).height() - panelHeight - 60) / 2;
    mQuery('.builder-spinner').css({
        left: spinnerLeft,
        top: spinnerTop
    });
    mQuery('#builder-overlay').removeClass('hide');
    mQuery('.btn-close-builder').prop('disabled', true);

    if (Mautic.builderMode == 'template') {
        // Save content
        var editors = Mautic.getBuilderEditorInstance();
        var content = {};

        var builderContents = mQuery('#builder-template-content').contents();

        // Make sure editors have lost focus so the content is updated
        builderContents.find('.mautic-editable').each(function (index) {
            mQuery(this).blur();
        });

        // Get the content of each editor
        mQuery.each(editors, function (slot, editor) {
            slot = slot.replace("slot-", "");
            content[slot] = editor.getData();
        });

        Mautic.saveBuilderContent(model, builderContents.find('#builder_entity_id').val(), content, function (response) {
            if (response.success) {
                try {
                    // Kill droppables
                    builderContents.find('.mautic-editable').droppable('destroy');

                    // Kill draggables
                    mQuery(".ui-draggable[data-token]").draggable('destroy');
                } catch (err) {
                    console.log(err);
                }

                // Kill the overlay
                mQuery('#builder-overlay').remove();

                // Hide builder
                mQuery('.builder').removeClass('builder-active').addClass('hide');
                mQuery('.btn-close-builder').prop('disabled', false);

                mQuery('body').css('overflow-y', '');

                // mQuery('.builder').addClass('hide');
                Mautic.stopIconSpinPostEvent();
            }
        });

        mQuery('#builder-template-content').remove();
    } else {
        try {
            // Kill droppable
            mQuery('#customHtmlDropzone').droppable('destroy');

            // Kill draggables
            mQuery(".ui-draggable[data-token]").draggable('destroy');

            // Get the contents of the editor
            var data = CKEDITOR.instances['builder-custom-content'].getData();
            CKEDITOR.instances[Mautic.builderFormName + '_customHtml'].setData(data);

            // Destroy the editor
            CKEDITOR.instances['builder-custom-content'].destroy(true);
        } catch (err) {
            console.log(err);
        }

        mQuery('#builder-custom-content').remove();

        // Kill the overlay
        mQuery('#builder-overlay').remove();

        // Hide builder
        mQuery('.builder').removeClass('builder-active').addClass('hide');
        mQuery('.btn-close-builder').prop('disabled', false);

        mQuery('body').css('overflow-y', '');

        Mautic.stopIconSpinPostEvent();
    }

    delete Mautic.builderMode;
    delete Mautic.builderFormName;
};

/**
 * Makes changes based on what builder mode is selected
 *
 * @param el
 */
Mautic.onBuilderModeSwitch = function(el) {
    var builderMode = (mQuery(el).val() == '') ? false : true;

    if (builderMode) {
        mQuery('.custom-html-mask').removeClass('hide');
        mQuery('.template-dnd-help').removeClass('hide');
        mQuery('.custom-dnd-help').addClass('hide');
        mQuery('.template-fields').removeClass('hide');
        Mautic.toggleBuilderButton(false);

    } else {
        mQuery('.custom-html-mask').addClass('hide');
        mQuery('.template-dnd-help').addClass('hide');
        mQuery('.custom-dnd-help').removeClass('hide');
        mQuery('.template-fields').addClass('hide');
        Mautic.toggleBuilderButton(true);
    }
};

/**
 *
 * @param formName
 */
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

/**
 * Save the builder content
 *
 * @param model
 * @param entityId
 * @param content
 * @param callback
 */
Mautic.saveBuilderContent = function (model, entityId, content, callback) {
    mQuery.ajax({
        url: mauticAjaxUrl + '?action=' + model + ':setBuilderContent',
        type: "POST",
        data: {
            slots: content,
            entity: entityId
        },
        success: function (response) {
            if (typeof callback === "function") {
                callback(response);
            }
        }
    });
};

/**
 * Get ckeditor instance
 *
 * @param id
 * @returns {*}
 */
Mautic.getBuilderEditorInstance = function (id) {
    var editors = (Mautic.builderMode == 'template') ?
        document.getElementById('builder-template-content').contentWindow.CKEDITOR.instances :
        CKEDITOR.instances;

    if (id) {
        return editors[id];
    } else {
        return editors;
    }
};

/**
 * Insert token into ckeditor
 *
 * @param editorId
 * @param token
 * @param isHtml
 */
Mautic.insertBuilderEditorToken = function(editorId, token, isHtml) {
    var editor = Mautic.getBuilderEditorInstance(editorId);

    if (typeof isHtml == 'undefined') {
        var first = token.charAt(0);
        if (first == '<') {
            isHtml = true;
        }
    }

    if (isHtml) {
        editor.insertHtml(token);
    } else {
        editor.insertText(token);
    }
};

/**
 * Show modal to insert a link into ckeditor
 * @param event
 * @param ui
 * @param editorId
 */
Mautic.showBuilderLinkModal = function (event, ui, editorId) {
    // Reset in case the modal wasn't closed via cancel
    mQuery('#BuilderLinkModal input[name="link"]').val('');
    mQuery('#BuilderLinkModal input[name="text"]').val('');
    mQuery('#BuilderLinkModal input[name="text"]').parent().removeClass('hide');

    var token  = mQuery(ui.draggable).data('token');
    mQuery('#BuilderLinkModal input[name="editor"]').val(editorId);
    mQuery('#BuilderLinkModal input[name="token"]').val(token);

    var defaultUrl = token.match(/%url=(.*?)%/);

    if (defaultUrl && defaultUrl[1]) {
        mQuery('#BuilderLinkModal input[name="url"]').val(defaultUrl[1]);
    }

    var defaultText = token.match(/%text=(.*?)%/);
    if (defaultText && defaultText[1]) {
        mQuery('#BuilderLinkModal input[name="text"]').val(defaultText[1]);
    } else if (!token.match(/%text%/g)) {
        //hide the text
        mQuery('#BuilderLinkModal input[name="text"]').parent().addClass('hide');
    }

    //append the modal to the builder or else it won't display
    mQuery('#BuilderLinkModal').appendTo('body');
    mQuery('#BuilderLinkModal').modal('show');
};

/**
 * Insert link into ckeditor
 */
Mautic.insertBuilderLink = function () {
    var editorId = mQuery('#BuilderLinkModal input[name="editor"]').val();
    var token    = mQuery('#BuilderLinkModal input[name="token"]').val();
    var url      = mQuery('#BuilderLinkModal input[name="url"]').val();
    var text     = mQuery('#BuilderLinkModal input[name="text"]').val();

    if (url) {
        if (!text) {
            text = url;
        }
        token = token.replace(/%url(.*?)%/, url).replace(/%text(.*?)%/, text);
        Mautic.insertBuilderEditorToken(editorId, token);
    }

    mQuery('#BuilderLinkModal').modal('hide');
    mQuery('#BuilderLinkModal input[name="editor"]').val('');
    mQuery('#BuilderLinkModal input[name="url"]').val('');
    mQuery('#BuilderLinkModal input[name="text"]').val('');
    mQuery('#BuilderLinkModal input[name="text"]').parent().removeClass('hide');
};

/**
 * Show builder feedback modal (accept input and insert into editor)
 *
 * @param event
 * @param ui
 * @param editorId
 */
Mautic.showBuilderFeedbackModal = function (event, ui, editorId) {
    // Reset in case the modal wasn't closed via cancel
    mQuery('#BuilderFeedbackModal input[name="feedback"]').val('');
    mQuery('#BuilderFeedbackModal input[name="feedback"]').attr('placeholder', '');

    var token = mQuery(ui.draggable).data('token');

    mQuery('#BuilderFeedbackModal input[name="editor"]').val(editorId);
    mQuery('#BuilderFeedbackModal input[name="token"]').val(token);

    var placeholder = token.match(/%(.*?)%/);
    if (placeholder && placeholder[1]) {
        mQuery('#BuilderFeedbackModal input[name="feedback"]').attr('placeholder', placeholder[1]);
    }

    //append the modal to the builder or else it won't display
    mQuery('#BuilderFeedbackModal').appendTo('body');
    mQuery('#BuilderFeedbackModal').modal('show');
};

/**
 * Insert input feedback into ckeditor
 */
Mautic.insertBuilderFeedback = function () {
    var editorId = mQuery('#BuilderFeedbackModal input[name="editor"]').val();
    var token    = mQuery('#BuilderFeedbackModal input[name="token"]').val();
    var feedback = mQuery('#BuilderFeedbackModal input[name="feedback"]').val();

    if (feedback) {
        token = token.replace(/%(.*?)%/, feedback);
        Mautic.insertBuilderEditorToken(editorId, token);
    }

    mQuery('#BuilderFeedbackModal').modal('hide');
    mQuery('#BuilderFeedbackModal input[name="editor"]').val('');
    mQuery('#BuilderFeedbackModal input[name="feedback"]').val('');
    mQuery('#BuilderFeedbackModal input[name="feedback"]').attr('placeholder', '');
};

/**
 * Prepare builder
 *
 * @param target
 */
Mautic.builderOnLoad = function (target) {
    Mautic.activateBuilderDragTokens(target);
};