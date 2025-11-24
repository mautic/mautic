Mautic.changeIconToSpinner = function(iconElement) {
    if (iconElement && iconElement.length) {
        iconElement.removeClass('fa-times').addClass('fa-spinner fa-spin');
    }
};

Mautic.campaignEventDeleteModal = {
    modal: null,
    confirmButton: null,
    finalConfirmButton: null,
    cancelButton: null,
    redirectSelect: null,
    redirectLabel: null,
    warningDiv: null,

    /**
     * Initialize the event delete modal functionality
     */
    init: function() {
        this.modal = mQuery('#campaignEventDeleteModal');
        this.confirmButton = mQuery('#campaignEventDeleteConfirm');
        this.finalConfirmButton = mQuery('#campaignEventDeleteFinalConfirm');
        this.cancelButton = mQuery('#campaignEventDeleteCancel');
        this.redirectSelect = mQuery('#campaignEventDeleteRedirect');
        this.redirectLabel = mQuery('#campaignEventDeleteRedirectLabel');
        this.warningDiv = mQuery('#campaignEventDeleteWarning');

        // Configure modal options for better UX
        this.modal.modal({
            show: false,       // Don't show on initialization
            backdrop: true,    // Allow clicking outside to close
            keyboard: true     // Allow ESC key to close
        });

        // Make sure any existing event handlers are removed
        this.finalConfirmButton.off('click');
        this.confirmButton.off('click');
        this.cancelButton.off('click');
        this.modal.off('hidden.bs.modal');

        // Handle the initial "Redirect Event" button click
        this.confirmButton.on('click', () => {
            // If the button is disabled (as in the case of the last event), do nothing
            if (this.confirmButton.prop('disabled')) {
                return;
            }

            const selectedEventId = this.redirectSelect.val();

            // Since we're now enforcing redirection is always required, validate selection
            if (!selectedEventId) {
                this.redirectSelect.addClass('error');
                this.warningDiv.removeClass('alert-warning').addClass('alert-danger').show();
                this.warningDiv.find('.warning-message').html(
                    Mautic.translate('mautic.campaign.event.delete.select_redirect')
                );

                // Hide confirmation/cancel buttons in error state
                this.finalConfirmButton.hide();
                this.cancelButton.hide();
                return;
            }

            this.redirectSelect.removeClass('error');
            this.warningDiv.removeClass('alert-danger').addClass('alert-warning');

            // Disable the "Redirect Event" button while showing confirmation
            this.confirmButton.prop('disabled', true);

            // Show warning message with confirmation/cancel buttons
            const warningMessage = Mautic.translate('mautic.campaign.event.delete.confirmation');

            this.warningDiv.find('.warning-message').text(warningMessage);
            this.warningDiv.show();
        });

        // Handle the "Cancel" button inside warning message
        this.cancelButton.on('click', () => {
            this.warningDiv.hide();

            // Re-enable the "Redirect Event" button when canceling the confirmation
            this.confirmButton.prop('disabled', false);
        });

        // Handle the final "Confirm" button click
        this.finalConfirmButton.off('click').on('click', () => {
            const eventId = mQuery('#campaignEventDeleteTarget').val();
            const redirectTo = this.redirectSelect.val();
            const deleteUrl = mQuery('#campaignEventDeleteUrl').val();

            // Disable all buttons during request
            this.modal.find('button').prop('disabled', true);

            this.deleteEventWithRedirect(eventId, deleteUrl, redirectTo);
        });

        // Reset the modal when it's closed (via any method - X button, clicking outside, ESC key)
        this.modal.on('hidden.bs.modal', () => {
            // Reset form elements
            this.redirectSelect.removeClass('error');
            this.warningDiv.hide().removeClass('alert-danger').addClass('alert-warning');

            // Always ensure the "Redirect Event" button is re-enabled and properly styled
            // This handles the case when modal is closed during confirmation state
            this.confirmButton.removeClass('btn-default').addClass('btn-danger').prop('disabled', false);

            // Re-enable all buttons in the modal for next time
            this.modal.find('button').prop('disabled', false);

            // Make sure action buttons are visible for next time
            this.finalConfirmButton.show();
            this.cancelButton.show();

            // Clear any selected value in the dropdown
            this.redirectSelect.val('');

            // Re-enable any buttons on the builder canvas that may have been disabled
            mQuery('.btns-builder').find('button').prop('disabled', false);

            const sourceButton = this.modal.data('sourceButton');
            if (sourceButton && sourceButton.length) {
                // Reset the icon on the specific button that was clicked
                sourceButton.find('i').removeClass('fa-spinner fa-spin').addClass('fa-times');
            } else {
                // Fallback: Reset all delete button icons
                const $deleteIcons = mQuery("#CampaignCanvas .list-campaign-event a[data-toggle='ajax-delete'] i");
                $deleteIcons.each(function() {
                    const $icon = mQuery(this);
                    if ($icon.hasClass('fa-spinner')) {
                        $icon.removeClass('fa-spinner fa-spin').addClass('fa-times');
                    }
                });
            }

            // Clean up all stored data
            mQuery('#campaignEventDeleteTarget').val('');
            mQuery('#campaignEventDeleteUrl').val('');
            mQuery('#campaignEventDeleteCampaignId').val('');
            this.modal.removeData('sourceButton');

            // Give the browser a moment to process everything before allowing new modal operations
            setTimeout(() => {
                // Re-initialize all delete button event handlers
                if (typeof Mautic.campaignBuilderInstance !== 'undefined') {
                    mQuery("#CampaignCanvas .list-campaign-event a[data-toggle='ajax-delete']").each(function() {
                        // First remove any existing click handlers to avoid duplicates and then re-add.
                        mQuery(this).off('click.ajax').on('click.ajax', Mautic.handleEventDeleteClick);
                    });
                }
            }, 100);
        });
    },

    /**
     * Populate the event dropdown with available events from the campaign
     *
     * @param {string} currentEventId - The ID of the event being deleted
     */
    populateEventOptions: function(currentEventId) {
        // Clear existing options including the default "Do not Redirect" option
        this.redirectSelect.empty();

        // Get available events using the shared function from campaign.js
        const availableEvents = Mautic.getCampaignBuilderEventOptions(currentEventId);

        if (availableEvents.length === 0) {
            // Show an error message - can't delete the last event
            this.redirectLabel.html(`<span class='text-danger'>${Mautic.translate('mautic.campaign.event.delete.unable_delete_last')}</span>`);
            this.redirectSelect.removeClass('required');
            this.confirmButton.removeClass('btn-danger').addClass('btn-default').text(Mautic.translate('mautic.campaign.event.delete'));
            this.confirmButton.prop('disabled', true);

            // Add error message
            this.warningDiv.removeClass('alert-warning').addClass('alert-danger').show();
            this.warningDiv.find('.warning-message').html(
                Mautic.translate('mautic.campaign.event.delete.last_event')
            );

            // The confirm/cancel buttons are directly inside the warningDiv, not in a container
            this.finalConfirmButton.hide();
            this.cancelButton.hide();

            // Hide the redirect select since we're blocking the deletion
            this.redirectSelect.closest('.form-group').addClass('hide');

            return;
        }

        // Make sure buttons are visible for other cases
        this.finalConfirmButton.show();
        this.cancelButton.show();

        // Since we now enforce that redirect selection is always mandatory
        // and we're checking for total events first, if we get here we know there are multiple events
        this.redirectLabel.html(`${Mautic.translate('mautic.campaign.event.delete.redirect_contacts')} <span class='text-danger'>*</span>`);
        this.confirmButton.removeClass('btn-default').addClass('btn-danger').text(Mautic.translate('mautic.campaign.event.delete.redirect_event'));
        this.confirmButton.prop('disabled', false);

        // Show the select field
        this.redirectSelect.closest('.form-group').removeClass('hide');

        // Add all available events to the select
        mQuery.each(availableEvents, (index, event) => {
            const optionLabel = '(' + event.eventType.charAt(0).toUpperCase() + event.eventType.slice(1).toLowerCase() + ') ' + event.name;
            mQuery("<option />", { value: event.id, text: optionLabel }).appendTo(this.redirectSelect);
        });

        // Select the first option by default
        if (this.redirectSelect.find('option').length > 0) {
            this.redirectSelect.find('option').first().prop('selected', true);
        }

        // Refresh the select if it's using chosen
        if (this.redirectSelect.data('chosen')) {
            this.redirectSelect.trigger('chosen:updated');
        }

        // Hide any previous error messages
        this.warningDiv.hide();
    },

    /**
     * Check if an event has a delay configured
     *
     * @param {string} eventId - The event ID to check
     * @returns {boolean} - True if the event has a delay
     */
    hasDelay: function(eventId) {
        if (typeof Mautic.campaignBuilderCanvasEvents === 'undefined'
            || !Mautic.campaignBuilderCanvasEvents[eventId]) {
            return false;
        }

        const event = Mautic.campaignBuilderCanvasEvents[eventId];
        return !(event.triggerMode && event.triggerMode === 'immediate');
    },

    /**
     * Check if an event is used as a redirect target by any other event
     *
     * @param {string} eventId - The event ID to check
     * @returns {boolean} - True if the event is used as a redirect target
     */
    isRedirectTarget: function(eventId) {
        // Check for deleted events within the campaign builder that haven't been saved yet.
        if (Mautic.campaignBuilderCampaignElements.deletedEvents) {
            for (let i = 0; i < Mautic.campaignBuilderCampaignElements.deletedEvents.length; i++) {
                if (Mautic.campaignBuilderCampaignElements.deletedEvents[i].redirectEvent === eventId) {
                    return true;
                }
            }
        }

        // If none found, load changes from the persisted Event entity.
        if (typeof Mautic.campaignBuilderCanvasEvents !== 'undefined' &&
            Mautic.campaignBuilderCanvasEvents[eventId] &&
            typeof Mautic.campaignBuilderCanvasEvents[eventId].isRedirectTarget !== 'undefined') {
            return Mautic.campaignBuilderCanvasEvents[eventId].isRedirectTarget;
        }

        return false;
    },

    /**
     * Handle deletion of newly added unsaved events (ID starts with "new")
     *
     * @param {string} eventId - The event ID to delete
     */
    handleNewEventDeletion: function(eventId) {
        // Remove the event from the canvas
        if (typeof Mautic.campaignBuilderInstance !== 'undefined') {
            Mautic.campaignBuilderInstance.remove(document.getElementById('CampaignEvent_' + eventId));
        }

        // Update all campaign builder data structures to mark the event as deleted
        if (Mautic.campaignBuilderCanvasEvents[eventId]) {
            // Mark the event as deleted
            Mautic.campaignBuilderCanvasEvents[eventId].deleted = true;
        }

        // Update any references in the campaign builder elements
        if (typeof Mautic.campaignBuilderCampaignElements.modifiedEvents !== 'undefined') {
            delete Mautic.campaignBuilderCampaignElements.modifiedEvents[eventId];
        }

        // Delete from the campaign event positions
        delete Mautic.campaignBuilderEventPositions['CampaignEvent_' + eventId];

        // Reset the spinner icon on the delete button
        const deleteButton = mQuery('#CampaignEvent_' + eventId).find('a[data-toggle="ajax-delete"] i');
        if (deleteButton.length) {
            deleteButton.removeClass('fa-spinner fa-spin').addClass('fa-times');
        }
    },

    /**
     * Check if an event should bypass the modal (can be directly deleted)
     *
     * @param {string} eventId - The event ID to check
     * @returns {boolean} - True if modal should be bypassed
     */
    shouldBypassModal: function(eventId) {
        if (typeof Mautic.campaignBuilderCanvasEvents === 'undefined'
            || !Mautic.campaignBuilderCanvasEvents[eventId]) {
            return false;
        }

        const event = Mautic.campaignBuilderCanvasEvents[eventId];

        // Never bypass for decision events or if this event is used as a redirect target
        if (event.eventType === 'decision' || this.isRedirectTarget(eventId)) {
            return false;
        }

        // Bypass if event has no delay
        return !this.hasDelay(eventId);
    },

    /**
     * Open the delete modal for a specific event
     *
     * @param {string} eventId - The ID of the event to delete
     * @param {string} deleteUrl - The URL to send the delete request to
     * @param {string} campaignId - The ID of the campaign
     */
    openForEvent: function(eventId, deleteUrl, campaignId) {
        // Check if this is a newly added unsaved event (ID starts with "new")
        if (eventId.startsWith('new')) {
            this.handleNewEventDeletion(eventId);
            return;
        }

        // Check if we should bypass the modal using the preloaded information
        const shouldBypass = this.shouldBypassModal(eventId);
        if (shouldBypass) {
            // Directly delete the event without showing modal
            this.deleteEventDirectly(eventId, deleteUrl);
        } else {
            // Show the modal for redirect confirmation
            this.showModalForEventDelete(eventId, deleteUrl, campaignId);
        }
    },

    /**
     * Show the modal for event deletion.
     */
    showModalForEventDelete: function(eventId, deleteUrl, campaignId) {
        // Ensure any previous state is cleared
        this.warningDiv.hide().removeClass('alert-danger').addClass('alert-warning');
        this.modal.find('button').prop('disabled', false);

        // Set hidden field values
        mQuery('#campaignEventDeleteTarget').val(eventId);
        mQuery('#campaignEventDeleteUrl').val(deleteUrl);
        mQuery('#campaignEventDeleteCampaignId').val(campaignId);

        // Populate the event options dropdown
        this.populateEventOptions(eventId);

        // Configure modal options to ensure proper handling
        const modalOptions = {
            backdrop: true,  // Clicking outside will close the modal
            keyboard: true   // ESC key will close the modal
        };

        // Store reference to the original button for cleanup
        const deleteButton = mQuery('#CampaignEvent_' + eventId).find('a[data-toggle="ajax-delete"]');
        this.modal.data('sourceButton', deleteButton).modal(modalOptions).modal('show');
    },

    /**
     * Delete an event directly without showing modal (for events that can bypass modal)
     *
     * @param {string} eventId - The event ID to delete
     * @param {string} deleteUrl - The delete URL
     */
    deleteEventDirectly: function(eventId, deleteUrl) {
        // Start loading icon on the delete button
        const deleteButton = mQuery('#CampaignEvent_' + eventId).find('a[data-toggle="ajax-delete"]');
        const iconElement = deleteButton.find('i');
        Mautic.startIconSpinOnEvent(deleteButton);

        // Use the shared delete function with no redirect
        Mautic.campaignBuilderDeleteEvent(
            eventId,
            null, // No redirect for direct deletions
            deleteUrl,
            // Success callback
            (response) => {
                Mautic.stopIconSpinPostEvent();
                if (!response.success) {
                    alert(response.message || Mautic.translate('mautic.campaign.event.delete.error'));
                    // Reset the delete button icon on error
                    if (iconElement && iconElement.length) {
                        iconElement.removeClass('fa-spinner fa-spin').addClass('fa-times');
                    }
                }
            },
            // Error callback
            (response) => {
                Mautic.stopIconSpinPostEvent();
                alert(Mautic.translate('mautic.campaign.event.delete.generic_error'));
                // Reset the delete button icon on error
                if (iconElement && iconElement.length) {
                    iconElement.removeClass('fa-spinner fa-spin').addClass('fa-times');
                }
            }
        );
    },

    /**
     * Delete an event with redirect (called from modal)
     *
     * @param {string} eventId - The event ID to delete
     * @param {string} deleteUrl - The delete URL
     * @param {string} redirectEventId - The redirect event ID
     */
    deleteEventWithRedirect: function(eventId, deleteUrl, redirectEventId) {
        // Start loading icon on the delete button
        const deleteButton = mQuery('#CampaignEvent_' + eventId).find('a[data-toggle="ajax-delete"]');
        Mautic.startIconSpinOnEvent(deleteButton);

        // Use the shared delete function with redirect
        Mautic.campaignBuilderDeleteEvent(
            eventId,
            redirectEventId,
            deleteUrl,
            // Success callback
            (response) => {
                Mautic.stopIconSpinPostEvent();
                if (response.success) {
                    // Close the modal on success
                    this.modal.modal('hide');
                } else {
                    // Show error message in modal
                    this.warningDiv.removeClass('alert-warning').addClass('alert-danger');
                    this.warningDiv.find('.warning-message').text(response.message || Mautic.translate('mautic.campaign.event.delete.error'));
                    // Re-enable buttons
                    this.modal.find('button').prop('disabled', false);
                }
            },
            // Error callback
            (response) => {
                Mautic.stopIconSpinPostEvent();
                // Show generic error message in modal
                this.warningDiv.removeClass('alert-warning').addClass('alert-danger');
                this.warningDiv.find('.warning-message').text(Mautic.translate('mautic.campaign.event.delete.generic_error'));
                // Re-enable buttons
                this.modal.find('button').prop('disabled', false);
            }
        );
    }
};

/**
 * Gets available campaign events for a dropdown list
 *
 * @param {string} currentEventId - The current event ID to exclude from the list
 * @returns {Array} Array of available events objects with id, name and eventType
 */
Mautic.getCampaignBuilderEventOptions = function(currentEventId) {
    const availableEvents = [];

    if (typeof Mautic.campaignBuilderCanvasEvents !== 'undefined') {
        for (const eventId in Mautic.campaignBuilderCanvasEvents) {
            const event = Mautic.campaignBuilderCanvasEvents[eventId];

            if (eventId !== currentEventId &&
                !event.deleted &&
                !eventId.startsWith('new') &&
                event.eventType !== 'decision') {
                availableEvents.push({
                    id: eventId,
                    name: event.name,
                    eventType: event.eventType
                });
            }
        }
    }

    return availableEvents;
};

/**
 * Reinitialize delete handlers for newly added events
 * This should be called after campaign save or when new events are added
 */
Mautic.reinitializeCampaignEventDeleteHandlers = function() {

    // Initialize the modal if needed
    if (typeof Mautic.campaignEventDeleteModal !== 'undefined') {
        Mautic.campaignEventDeleteModal.init();
    }

    // Set up delete handlers for all event delete buttons
    mQuery("#CampaignCanvas .list-campaign-event a[data-toggle='ajax-delete']").each(function() {
        // First remove any existing click handlers to avoid duplicates and then re-add.
        mQuery(this).off('click.ajax').on('click.ajax', Mautic.handleEventDeleteClick);
    });
};

/**
 * Ensure all event handlers are properly initialized in the campaign builder
 * This can be called directly when needed to fix event binding issues
 */
Mautic.ensureCampaignEventHandlers = function() {
    if (mQuery('#CampaignCanvas').length) {
        // Make sure event delete handlers are initialized
        Mautic.reinitializeCampaignEventDeleteHandlers();

        // Refresh jsPlumb connections
        if (typeof Mautic.campaignBuilderInstance !== 'undefined') {
            Mautic.campaignBuilderInstance.repaintEverything();
        }
    }
};

// Initialize the modal functionality when the DOM is ready
mQuery(document).ready(function() {
    Mautic.campaignEventDeleteModal.init();

    // Set up a callback for when the campaign is saved
    mQuery(document).on('mauticCampaignBuilderCanvasLoaded', function() {
        Mautic.ensureCampaignEventHandlers();
    });
});

/**
 * Handles the campaign event delete click
 *
 * @param {Event} event The click event
 */
Mautic.handleEventDeleteClick = function(event) {
    event.preventDefault();

    // Change the icon to a spinner
    const $deleteLink = mQuery(this);
    const $iconElement = $deleteLink.find('i');
    Mautic.changeIconToSpinner($iconElement);

    // Get the event ID and URL
    const parentId = $deleteLink.closest('.list-campaign-event').attr('id');
    const eventId = parentId.replace('CampaignEvent_', '');
    const deleteUrl = $deleteLink.attr('href');
    const campaignId = mQuery('#campaignId').val();

    // Open the delete modal
    Mautic.campaignEventDeleteModal.openForEvent(eventId, deleteUrl, campaignId);
};
