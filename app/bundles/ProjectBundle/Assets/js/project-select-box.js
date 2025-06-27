class ProjectSelectBox {
    constructor(selectElement) {
        this.$projectSelect = mQuery(selectElement);
        this.init();
    }

    init() {
        this.$projectSelect.on('chosen:no_results', this.attachKeydownListener.bind(this));
    }

    attachKeydownListener(event) {
        const $input = mQuery(event.target).next('.chosen-container').find('.chosen-search-input');

        $input.off('keydown').on('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const newValue = $input.val().trim();

                if (newValue) {
                    // Add the new value to the select element as an option
                    const $newOption = mQuery('<option>').val('project_to_create').text(newValue).prop('selected', true);
                    this.$projectSelect.append($newOption).trigger('chosen:updated');

                    this.createProjects(event.target);
                }
            }
        });
    }

    createProjects(el) {
        const newProjectNames = [];
        const existingProjectIds = [];
        const $projectSelect = mQuery(el);
        mQuery('#' + $projectSelect.attr('id') + ' :selected').each(function (_, selected) {
            const $option = mQuery(selected);
            const selectedId = $option.val();

            if ('project_to_create' === selectedId) {
                newProjectNames.push($option.text());
            } else {
                existingProjectIds.push(selectedId);
            }
        });

        if (!newProjectNames.length) {
            return;
        }

        Mautic.activateLabelLoadingIndicator($projectSelect.attr('id'));

        Mautic.ajaxActionRequest('project:addProjects', { newProjectNames: JSON.stringify(newProjectNames), existingProjectIds: JSON.stringify(existingProjectIds) }, function (response) {
            if (response.projects) {
                mQuery('#' + $projectSelect.attr('id')).html(response.projects).trigger('chosen:updated');
            }

            Mautic.removeLabelLoadingIndicator();
        });
    }
}

/**
 * Handle project batch form submission
 */
Mautic.projectBatchSubmit = function () {
    if (Mautic.batchActionPrecheck()) {
        if (mQuery('#project_batch_add_to').val() || mQuery('#project_batch_remove_from').val()) {
            var ids = Mautic.getCheckedListIds(false, true);

            if (mQuery('#project_batch_ids').length) {
                mQuery('#project_batch_ids').val(ids);
            }

            return true;
        }
    }

    mQuery('#MauticSharedModal').modal('hide');
    return false;
};

/**
 * Handle project batch form submission callback
 */
Mautic.projectBatchSubmitCallback = function (response) {
    mQuery('#MauticSharedModal').modal('hide');
};

// Listen for the 'chosen:no_results' event on all select elements
mQuery(document).on('chosen:no_results', 'select', function (event) {
    const $select = mQuery(event.target);

    // Check if the select element has the desired attribute
    if ($select.data('action') === 'createProject') {
        new ProjectSelectBox($select);
    }
});
