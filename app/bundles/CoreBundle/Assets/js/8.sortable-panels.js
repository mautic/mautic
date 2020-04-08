/**
 * Activate sortable panels
 *
 * @param el
 */
Mautic.activateSortablePanels = function (container) {
    mQuery(container).find('.available-panel-selector').each(function() {
        var sortablesContainer = mQuery(this).closest('.sortable-panels');
        var selector = this;
        var prefix = mQuery(selector).data('prototype-prefix');
        mQuery(selector).on('change', function () {
            // Check if there is a prototype for this item
            var selected = mQuery(this).val();
            // Replace periods with dashes as they can't be used as Symfony form field names
            selected = selected.replace('.', '-');
            var prototype = '#' + prefix + selected;
console.log(prototype);
            if (mQuery(prototype).length) {
                console.log('exists');
                Mautic.appendSortablePanel(sortablesContainer, prototype);
            }

            mQuery(selector).val('');
            mQuery(selector).trigger('chosen:updated');
        });

        //make the panels sortable
        var bodyOverflow = {};
        mQuery(sortablesContainer).sortable({
            items: '.panel',
            handle: '.sortable-panel-wrapper',
            cancel: '',
            helper: function(e, ui) {
                ui.children().each(function() {
                    if (!mQuery(this).hasClass('modal')) {
                        mQuery(this).width(mQuery(this).width());
                    }
                });

                // Fix body overflow that messes sortable up
                bodyOverflow.overflowX = mQuery('body').css('overflow-x');
                bodyOverflow.overflowY = mQuery('body').css('overflow-y');
                mQuery('body').css({
                    overflowX: 'visible',
                    overflowY: 'visible'
                });

                return ui;
            },
            scroll: true,
            axis: 'y',
            containment: '#'+mQuery(sortablesContainer).attr('id')+' .drop-here',
            stop: function(e, ui) {
                // Restore original overflow
                mQuery('body').css(bodyOverflow);
                mQuery(ui.item).attr('style', '');
            }
        });
    });

    var sortable = mQuery(container).hasClass('sortable-panels') ? container : mQuery(container).find('.sortable-panels');
    mQuery(sortable).find('.sortable-panel-wrapper').each(function() {
        Mautic.activateSortablePanel(mQuery(this).closest('.panel'));
    });
};

/**
 * Activate individual sortable panel's buttons, etc
 * @param panel
 */
Mautic.activateSortablePanel = function (panel) {
    mQuery(panel).find('.sortable-panel-buttons').each( function() {
        mQuery(this).find('.btn-delete').on('click', function() {
            Mautic.deleteSortablePanel(mQuery(this).closest('.panel'));
        });

        mQuery(this).find('.btn-edit').on('click', function() {
            Mautic.showModal('#'+mQuery(panel).find('.modal').attr('id'));
        });
    });

    // Activate chosens in the new modal
    mQuery(panel).find('select').not('.multiselect, .not-chosen').each(function() {
        Mautic.activateChosenSelect(this, true);
    });

    mQuery(panel).on('dblclick.sortablepanels', function(event) {
        event.preventDefault();
        mQuery(this).find('.btn-edit').first().click();
    });

    if (mQuery(panel).hasClass('sortable-has-error')) {
        var originalClass = mQuery(panel).find('.btn-edit i.fa').attr('class');
        mQuery(panel).find('.btn-edit i.fa').attr('data-original-icon', originalClass);
        mQuery(panel).find('.btn-edit i.fa').attr('class', 'fa fa-warning text-warning');
    }
};

/**
 * Add a new sortable panel to the DOM
 *
 * @param sortablesContainer
 * @param modal
 */
Mautic.appendSortablePanel = function(sortablesContainer, modal) {
    var newIdPrefix    = mQuery(sortablesContainer).find('.available-panel-selector').attr('data-prototype-id-prefix');
    var newNamePrefix  = mQuery(sortablesContainer).find('.available-panel-selector').attr('data-prototype-name-prefix');
    var oldIdPrefix    = mQuery(modal).attr('data-id-prefix');
    var oldNamePrefix  = mQuery(modal).attr('data-name-prefix');

    // Update the number of goals
    var index               = parseInt(mQuery(sortablesContainer).attr('data-index'));
    var panelId             = index+1;
    mQuery(sortablesContainer).attr('data-index', panelId);

    // Append prototype panel name
    var panelName = mQuery(modal).attr('data-name');
    oldIdPrefix   = oldIdPrefix+panelName;
    oldNamePrefix = oldNamePrefix+'['+panelName+']';
    newIdPrefix   = newIdPrefix+index;
    newNamePrefix = newNamePrefix+'['+index+']';

    // Create the new panel
    var newPanel = mQuery(sortablesContainer).find('.panel-prototype .panel').clone();
    var selectedPanel = mQuery(sortablesContainer).find('.available-panel-selector').children('option:selected');
    var placeholders  = selectedPanel.attr('data-placeholders');
    if (placeholders) {
        placeholders = JSON.parse(placeholders);
        var newPanelContent = mQuery(newPanel).html();

        mQuery.each(placeholders, function(key, val) {
            newPanelContent = newPanelContent.replace(key, val);
        });

        newPanel.html(newPanelContent);
    }

    mQuery(newPanel).addClass('new-panel');
    mQuery(newPanel).attr('data-default-label', selectedPanel.attr('data-default-label'));

    mQuery(sortablesContainer).find('.drop-here').append(newPanel);

    // Copy the prototype's modal/form over to the new panel
    var newModal   = mQuery(modal).clone();
    mQuery(newModal).removeClass('in').css('display', 'none');
    mQuery(newModal).attr('id', mQuery(newModal).attr('id').replace(oldIdPrefix, newIdPrefix));

    // Remove data-embedded-form-clear from button
    mQuery(newModal).find('button[data-embedded-form="cancel"]').removeAttr('data-embedded-form-clear');
    mQuery(newModal).find('button[data-embedded-form-callback="cancelSortablePanel"]').removeAttr('data-embedded-form-callback');

    // Hide and append the new modal
    mQuery(newModal).modal('hide');
    newPanel.append(newModal);

    // Replace forms id/names
    Mautic.renameFormElements(newModal, oldIdPrefix, oldNamePrefix, newIdPrefix, newNamePrefix)

    Mautic.activateModalEmbeddedForms('#'+mQuery(newModal).attr('id'));

    Mautic.showModal(newModal);

    // Activate chosens in the new modal
    mQuery(newModal).find('select').not('.multiselect, .not-chosen').each(function() {
        Mautic.activateChosenSelect(this);
    });
};

/**
 * Update a sortable panel
 *
 * @param btn
 * @param modal
 */
Mautic.updateSortablePanel = function(modalBtn, modal) {
    var panel = mQuery(modal).closest('.panel');

    // Get label
    var label = '';
    var hasNameField = false;
    if (mQuery(modalBtn).attr('data-panel-label')) {
        label = mQuery(modalBtn).attr('data-panel-label');
    } else if (mQuery(modal).attr('data-panel-label')) {
        label = mQuery(modal).attr('data-panel-label');
    } else if (mQuery(modal).find("input[name$='[name]']").length) {
        // Use a name field
        label = mQuery(modal).find("input[name$='[name]']").val();
        hasNameField = true;
    }

    if (!label.length) {
        label = mQuery(panel).attr('data-default-label');
        if (hasNameField) {
            mQuery(modal).find("input[name$='[name]']").val(label);
        }
    }

    mQuery(panel).find('.sortable-panel-label').html(label);

    var footer = '';
    if (mQuery(modalBtn).attr('data-panel-footer')) {
        var footer = mQuery(modalBtn).attr('data-panel-footer');
    } else if (mQuery(modal).attr('data-panel-footer')) {
        var footer = mQuery(modal).attr('data-panel-footer');
    }
    mQuery(panel).find('.sortable-panel.footer').html(footer);

    // remove error and assume it's fixed till save again
    if (mQuery(panel).hasClass('sortable-has-error')) {
        mQuery(panel).removeClass('sortable-has-error');
        var editBtn = mQuery(panel).find('.btn-edit i');
        if (editBtn.length) {
            editBtn.attr('class', editBtn.attr('data-original-icon'));
        }
    }

    Mautic.activateSortablePanel(panel);
    mQuery(panel).removeClass('new-panel');

    // Switch add to update button
    mQuery(panel).find('.modal .btn-add').addClass('hide');
    mQuery(panel).find('.modal .btn-update').removeClass('hide');

    Mautic.toggleSortablePanelAddMessage(mQuery(panel).closest('.sortable-panels'));
};

/**
 * Delete a sortable panel
 *
 * @param panel
 */
Mautic.deleteSortablePanel = function(panel) {
    var panelContainer = mQuery(panel).closest('.sortable-panels');
    mQuery(panel).remove();

    Mautic.toggleSortablePanelAddMessage(panelContainer);
};

/**
 * Remove a cancelled panel
 *
 * @param modalBtn
 * @param modal
 */
Mautic.cancelSortablePanel = function(modalBtn, modal) {
    setTimeout(function () {
        mQuery(modal).closest('.panel').remove();
    }, 500);
}

/**
 * Toggle the add new panel message
 *
 * @param panelContainer
 */
Mautic.toggleSortablePanelAddMessage = function(panelContainer) {
    // Show/hide the add message
    var panelsLeft = mQuery(panelContainer).find('.sortable-panel-wrapper').length;
    mQuery(panelContainer).find('.sortable-panel-placeholder').each(function() {
        if (panelsLeft <= 1) {
            mQuery(this).removeClass('hide');
        } else {
            mQuery(this).addClass('hide');
        }
    });
};
