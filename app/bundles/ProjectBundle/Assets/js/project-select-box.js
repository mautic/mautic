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
        mQuery('#' + $projectSelect.attr('id') + ' :selected').each(function(i, selected) {
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

        Mautic.ajaxActionRequest('project:addProjects', {newProjectNames: JSON.stringify(newProjectNames), existingProjectIds: JSON.stringify(existingProjectIds)}, function(response) {
            if (response.projects) {
                mQuery('#' + $projectSelect.attr('id')).html(response.projects).trigger('chosen:updated');
            }

            Mautic.removeLabelLoadingIndicator();
        });
    }
}

// Listen for the 'chosen:no_results' event on all select elements
mQuery(document).on('chosen:no_results', 'select', function (event) {
    const $select = mQuery(event.target);

    // Check if the select element has the desired attribute
    if ($select.data('action') === 'createProject') {
        new ProjectSelectBox($select);
    }
});

// Handle entity selection modal opening for project details
mQuery(document).on('change', '#project-entity-selector, #entity-type-selector', function(event) {
    const $select = mQuery(this);
    const $selectedOption = $select.find('option:selected');
    
    if ($selectedOption.val() && $selectedOption.data('href')) {
        // Get the URL and header from data attributes
        const url = $selectedOption.data('href');
        const header = $selectedOption.data('header');
        
        // Use Mautic's loadAjaxModal function
        Mautic.loadAjaxModal('#MauticSharedModal', url, 'GET', header);
        
        // Reset the select to placeholder after opening modal
        $select.val('');
        
        // Update chosen if it's a chosen select
        if ($select.hasClass('chosen-select')) {
            $select.trigger('chosen:updated');
        }
    }
});
