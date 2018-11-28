//CampaignBundle
/**
 * Setup the campaign view
 *
 * @param container
 */
Mautic.campaignOnLoad = function (container, response) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'campaign');
    }

    if (mQuery('#CampaignEventPanel').length) {
        // setup button clicks
        mQuery('#CampaignEventPanelGroups button').on('click', function() {
            var eventType = mQuery(this).data('type');
            Mautic.campaignBuilderUpdateEventList([eventType], false, 'lists', true);
        });

        mQuery('#CampaignEventPanelLists button').on('click', function() {
            Mautic.campaignBuilderUpdateEventList(Mautic.campaignBuilderAnchorClickedAllowedEvents, true, 'groups', true);
        });

        // set hover and double click functions for the event buttons
        if (!(mQuery('.preview').length)) {
            mQuery('#CampaignCanvas .list-campaign-event, #CampaignCanvas .list-campaign-source').off('.eventbuttons')
                .on('mouseover.eventbuttons', function() {
                    mQuery(this).find('.campaign-event-buttons').removeClass('hide');
                })
                .on('mouseout.eventbuttons', function() {
                    mQuery(this).find('.campaign-event-buttons').addClass('hide');
                })
                .on('dblclick.eventbuttons', function(event) {
                    event.preventDefault();
                    mQuery(this).find('.btn-edit').first().click();
                });
        } else {
            mQuery("#CampaignCanvas div.list-campaign-event").each(function () {
                var thisId = mQuery(this).attr('id');
                var option  = mQuery('#'+thisId+' option[value="' + mQuery(this).val() + '"]');
            });
        }

        // setup chosen
        mQuery('.campaign-event-selector').on('chosen:showing_dropdown', function (event) {
            // Disable canvas scrolling
            mQuery('.builder-content').css('overflow', 'hidden');

            var thisSelect = mQuery(event.target).attr('id');
            Mautic.campaignBuilderUpdateEventListTooltips(thisSelect, false);

            mQuery('#'+thisSelect+'_chosen .chosen-search input').on('keydown.toolip', function () {
                // Destroy tooltips that are filtered out
                Mautic.campaignBuilderUpdateEventListTooltips(thisSelect, true);
            }).on('keyup.tooltip', function() {
                // Recreate tooltips for those left
                Mautic.campaignBuilderUpdateEventListTooltips(thisSelect, false);
            });
        });

        mQuery('.campaign-event-selector').on('chosen:hiding_dropdown', function (event) {
            // Re-enable canvas scrolling
            mQuery('.builder-content').css('overflow', 'auto');

            var thisSelect = mQuery(event.target).attr('id');
            Mautic.campaignBuilderUpdateEventListTooltips(thisSelect, true);

            mQuery('#'+thisSelect+'_chosen .chosen-search input').off('keyup.toolip')
                .off('keydown.tooltip');
        });

        mQuery('.campaign-event-selector').on('change', function() {
            // Hide events list

            if (!mQuery('#CampaignEvent_newsource').length) {
                // Hide the CampaignEventPanel if visible
                mQuery('#CampaignEventPanel').addClass('hide');
            }

            // Get the option clicked
            var thisId = mQuery(this).attr('id');
            var option  = mQuery('#'+thisId+' option[value="' + mQuery(this).val() + '"]');

            if (option.attr('data-href') && Mautic.campaignBuilderAnchorNameClicked) {
                var updatedUrl = option.attr('data-href').replace(/anchor=(.*?)$/, "anchor=" + Mautic.campaignBuilderAnchorNameClicked + "&anchorEventType=" + Mautic.campaignBuilderAnchorEventTypeClicked);
                // Replace the anchor in the URL with that clicked
                option.attr('data-href', updatedUrl);
            }

            // Deactivate chosen to kill tooltips
            mQuery('#'+thisId).trigger('chosen:close');

            // Display the modal with form
            Mautic.ajaxifyModal(option);

            // Reset the dropdown
            mQuery(this).val('');
            mQuery(this).trigger('chosen:updated');
        });

        mQuery('#CampaignCanvas').on('click', function(event) {
            if (!mQuery(event.target).parents('#CampaignCanvas').length && !mQuery('#CampaignEvent_newsource').length) {
                // Hide the CampaignEventPanel if visible
                mQuery('#CampaignEventPanel').addClass('hide');
            }
        });

        Mautic.prepareCampaignCanvas();

        // Open the builder directly when saved from the builder
        if (response && response.inBuilder) {
            Mautic.launchCampaignEditor();
            Mautic.processBuilderErrors(response);
        }

    }
};

/**
 * Update chosen tooltips
 *
 * @param theSelect
 * @param destroy
 */
Mautic.campaignBuilderUpdateEventListTooltips = function(theSelect, destroy) {
    mQuery('#'+theSelect+' option').each(function () {
        if (mQuery(this).attr('id')) {
            // Initiate a tooltip on each option since chosen doesn't copy over the data attributes
            var chosenOption = '#' + theSelect + '_chosen .option_' + mQuery(this).attr('id');

            if (destroy) {
                mQuery(chosenOption).tooltip('destroy');
            } else {
                mQuery(chosenOption).tooltip({html: true, container: 'body', placement: 'left'});
            }
        }
    });
}

/**
 * Delete the builder instance so it's regenerated when reopening the campaign event builder
 */
Mautic.campaignOnUnload = function(container) {
    delete Mautic.campaignBuilderInstance;
    delete Mautic.campaignBuilderLabels;
}

/**
 * Setup the campaign event view
 *
 * @param container
 * @param response
 */
Mautic.campaignEventOnLoad = function (container, response) {
    if (mQuery('#campaignevent_triggerHour').length) {
        Mautic.campaignEventShowHideIntervalSettings();
        Mautic.campaignEventUpdateIntervalHours();
        mQuery('#campaignevent_triggerHour').on('change', Mautic.campaignEventUpdateIntervalHours);
        mQuery('#campaignevent_triggerRestrictedStartHour').on('change', Mautic.campaignEventUpdateIntervalHours);
        mQuery('#campaignevent_triggerRestrictedStopHour').on('change', Mautic.campaignEventUpdateIntervalHours);
        mQuery('#campaignevent_triggerIntervalUnit').on('change', Mautic.campaignEventShowHideIntervalSettings);
        mQuery('#campaignevent_triggerRestrictedDaysOfWeek_0').on('change', Mautic.campaignEventSelectDOW);
        mQuery('#campaignevent_triggerRestrictedDaysOfWeek_1').on('change', Mautic.campaignEventSelectDOW);
        mQuery('#campaignevent_triggerRestrictedDaysOfWeek_2').on('change', Mautic.campaignEventSelectDOW);
        mQuery('#campaignevent_triggerRestrictedDaysOfWeek_3').on('change', Mautic.campaignEventSelectDOW);
        mQuery('#campaignevent_triggerRestrictedDaysOfWeek_4').on('change', Mautic.campaignEventSelectDOW);
        mQuery('#campaignevent_triggerRestrictedDaysOfWeek_7').on('change', Mautic.campaignEventSelectDOW);
    }

    if (!response.hasOwnProperty('eventId')) {
        // There's nothing for us to do, so bail
        return;
    }

    // New action created so append it to the form
    var domEventId = 'CampaignEvent_' + response.eventId;
    var eventId = '#' + domEventId;

    Mautic.campaignBuilderLabels[domEventId] = (response.label) ? response.label : '';

    if (!response.success && Mautic.campaignBuilderConnectionRequiresUpdate) {
        // Modal exited - check to see if a connection needs to be removed
        Mautic.campaignBuilderInstance.detach(Mautic.campaignBuilderLastConnection);
    }
    Mautic.campaignBuilderConnectionRequiresUpdate = false;
    Mautic.campaignBuilderUpdateLabel(domEventId);
    Mautic.campaignBuilderCanvasEvents[response.event.id] = response.event;

    if (response.deleted) {
        Mautic.campaignBuilderInstance.remove(document.getElementById(domEventId));
        delete Mautic.campaignBuilderEventPositions[domEventId];
        delete Mautic.campaignBuilderCanvasEvents[response.event.id];
    } else if (response.updateHtml) {
        mQuery(eventId + " .campaign-event-content").replaceWith(response.updateHtml);
    } else if (response.eventHtml) {
        var newHtml = response.eventHtml;

        //append content
        var x = parseInt(mQuery('#droppedX').val());
        var y = parseInt(mQuery('#droppedY').val());

        Mautic.campaignBuilderEventPositions[domEventId] = {
            'left': x,
            'top': y
        };

        mQuery(newHtml).appendTo('#CampaignCanvas');

        mQuery(eventId).css({'left': x + 'px', 'top': y + 'px'});

        Mautic.campaignBuilderRegisterAnchors(Mautic.getAnchorsForEvent(response.event), eventId);
        Mautic.campaignBuilderInstance.draggable(domEventId, Mautic.campaignDragOptions);

        //activate new stuff
        mQuery(eventId + " a[data-toggle='ajax']").click(function (event) {
            event.preventDefault();
            return Mautic.ajaxifyLink(this, event);
        });

        //initialize ajax'd modals
        mQuery(eventId + " a[data-toggle='ajaxmodal']").on('click.ajaxmodal', function (event) {
            event.preventDefault();

            Mautic.ajaxifyModal(this, event);
        });

        mQuery(eventId).off('.eventbuttons')
            .on('mouseover.eventbuttons', function() {
                mQuery(this).find('.campaign-event-buttons').removeClass('hide');
            })
            .on('mouseout.eventbuttons', function() {
                mQuery(this).find('.campaign-event-buttons').addClass('hide');
            })
            .on('dblclick.eventbuttons', function(event) {
                event.preventDefault();
                mQuery(this).find('.btn-edit').first().click();
            });

        //initialize tooltips
        mQuery(eventId + " *[data-toggle='tooltip']").tooltip({html: true});

        // Connect into last anchor clicked
        Mautic.campaignBuilderInstance.connect({
            uuids: [
                Mautic.campaignBuilderAnchorClicked,
                domEventId+'_top'
            ]
        });
    }

    Mautic.campaignBuilderInstance.repaintEverything();
};

/**
 * Update the trigger hour based on the interval unit selected
 */
Mautic.campaignEventUpdateIntervalHours = function () {
    var hour = mQuery('#campaignevent_triggerHour').val();
    var start = mQuery('#campaignevent_triggerRestrictedStartHour').val();
    var stop = mQuery('#campaignevent_triggerRestrictedStopHour').val();

    if (hour) {
        mQuery('#campaignevent_triggerRestrictedStartHour').val('');
        mQuery('#campaignevent_triggerRestrictedStopHour').val('');
        mQuery('#campaignevent_triggerRestrictedStartHour').prop('disabled', true);
        mQuery('#campaignevent_triggerRestrictedStopHour').prop('disabled', true);
    } else if (start || stop) {
        mQuery('#campaignevent_triggerHour').val('');
        mQuery('#campaignevent_triggerHour').prop('disabled', true);
    } else {
        mQuery('#campaignevent_triggerHour').val('');
        mQuery('#campaignevent_triggerRestrictedStartHour').val('');
        mQuery('#campaignevent_triggerRestrictedStopHour').val('');
        mQuery('#campaignevent_triggerHour').prop('disabled', false);
        mQuery('#campaignevent_triggerRestrictedStartHour').prop('disabled', false);
        mQuery('#campaignevent_triggerRestrictedStopHour').prop('disabled', false);
    }
};

/**
 * Show/hide interval settings
 */
Mautic.campaignEventShowHideIntervalSettings = function() {
    var unit = mQuery('#campaignevent_triggerIntervalUnit').val();
    if (unit === 'i' || unit === 'h') {
        mQuery('#interval_settings').addClass('hide');
    } else {
        mQuery('#interval_settings').removeClass('hide');
    }
};

/**
 * Update DOW for weekday selection
 */
Mautic.campaignEventSelectDOW = function() {
    if (mQuery('#campaignevent_triggerRestrictedDaysOfWeek_7').prop('checked')) {
        mQuery('#campaignevent_triggerRestrictedDaysOfWeek_0').prop('checked', true);
        mQuery('#campaignevent_triggerRestrictedDaysOfWeek_1').prop('checked', true);
        mQuery('#campaignevent_triggerRestrictedDaysOfWeek_2').prop('checked', true);
        mQuery('#campaignevent_triggerRestrictedDaysOfWeek_3').prop('checked', true);
        mQuery('#campaignevent_triggerRestrictedDaysOfWeek_4').prop('checked', true);
    }

    mQuery('#campaignevent_triggerRestrictedDaysOfWeek_7').prop('checked', false);
};

/**
 * Determine anchors to set up for the given event.
 *
 * This inspects the `connectionRestrictions` property
 * within the event's settings that were passed when
 * registering the event in your bundle's CampaignEventListener.
 *
 * @param event
 */
Mautic.getAnchorsForEvent = function (event) {
    var restrictions = Mautic.campaignBuilderConnectionRestrictions[event.type].target;

    // If all connections are restricted, only anchor the top
    if (
        restrictions.decision.length === 1 && restrictions.decision[0] === "none" &&
        restrictions.action.length === 1 && restrictions.action[0] === "none" &&
        restrictions.condition.length === 1 && restrictions.condition[0] === "none"
    ) {
        return ['top'];
    }

    if (event.eventType === 'decision' || event.eventType === 'condition') {
        return ['top', 'yes', 'no'];
    }

    return ['top', 'bottom'];
};

/**
 * Setup the campaign source view
 *
 * @param container
 * @param response
 */
Mautic.campaignSourceOnLoad = function (container, response) {
    //new action created so append it to the form
    var domEventId = 'CampaignEvent_' + response.sourceType;
    var eventId = '#' + domEventId;

    if (response.deleted) {
        Mautic.campaignBuilderInstance.remove(document.getElementById(domEventId));
        delete Mautic.campaignBuilderEventPositions[domEventId];

        mQuery('#campaignLeadSource_' + response.sourceType).prop('disabled', false);
        mQuery('#SourceList').trigger('chosen:updated');

        // Check to see if all sources have been deleted
        if (!mQuery('.list-campaign-source:not(#CampaignEvent_newsource_hide)').length) {
            mQuery('#CampaignEvent_newsource_hide').attr('id', 'CampaignEvent_newsource');
            Mautic.campaignBuilderPrepareNewSource();
        }

    } else if (response.updateHtml) {

        mQuery(eventId + " .campaign-event-content").html(response.updateHtml);
    } else if (response.sourceHtml) {
        mQuery('#campaignLeadSource_' + response.sourceType).prop('disabled', true);
        mQuery('#SourceList').trigger('chosen:updated');

        var newHtml = response.sourceHtml;

        if (mQuery('#CampaignEvent_newsource').length) {
            var x = mQuery('#CampaignEvent_newsource').position().left;
            var y = mQuery('#CampaignEvent_newsource').position().top;

            mQuery('#CampaignEvent_newsource').attr('id', 'CampaignEvent_newsource_hide');
            mQuery('#CampaignEventPanel').addClass('hide');
            var autoConnect = false;
        } else {
            //append content
            var x = parseInt(mQuery('#droppedX').val());
            var y = parseInt(mQuery('#droppedY').val());
            var autoConnect = true;
        }

        mQuery(newHtml).appendTo('#CampaignCanvas');
        Mautic.campaignBuilderEventPositions[domEventId] = {
            'left': x,
            'top': y
        };
        mQuery(eventId).css({'left': x + 'px', 'top': y + 'px'});

        Mautic.campaignBuilderRegisterAnchors(['leadSource', 'leadSourceLeft', 'leadSourceRight'], eventId);
        Mautic.campaignBuilderInstance.draggable(domEventId, Mautic.campaignDragOptions);

        //activate new stuff
        mQuery(eventId + " a[data-toggle='ajax']").click(function (event) {
            event.preventDefault();
            return Mautic.ajaxifyLink(this, event);
        });

        //initialize ajax'd modals
        mQuery(eventId + " a[data-toggle='ajaxmodal']").on('click.ajaxmodal', function (event) {
            event.preventDefault();

            Mautic.ajaxifyModal(this, event);
        });

        mQuery(eventId).off('.eventbuttons')
            .on('mouseover.eventbuttons', function() {
                mQuery(this).find('.campaign-event-buttons').removeClass('hide');
            })
            .on('mouseout.eventbuttons', function() {
                mQuery(this).find('.campaign-event-buttons').addClass('hide');
            })
            .on('dblclick.eventbuttons', function(event) {
                event.preventDefault();
                mQuery(this).find('.btn-edit').first().click();
            });

        //initialize tooltipslist-campaign-event
        mQuery(eventId + " *[data-toggle='tooltip']").tooltip({html: true});
        if (autoConnect) {
            // Connect into last anchor clicked
            if (Mautic.campaignBuilderAnchorClicked.search('left') !== -1) {
                var source = domEventId + '_leadsourceright';
                var target = Mautic.campaignBuilderAnchorClicked;
            } else {
                var source = Mautic.campaignBuilderAnchorClicked;
                var target = domEventId + '_leadsourceleft';
            }

            Mautic.campaignBuilderInstance.connect({
                uuids: [
                    source,
                    target
                ]
            });
        }

        // If there are no other events, auto click add action
        if (!mQuery('.list-campaign-event').length) {
            mQuery('.jtk-endpoint_anchor_leadsource.'+domEventId).trigger('click');
        }
    }

    Mautic.campaignBuilderInstance.repaintEverything();
};

/**
 * Update a connectors label
 *
 * @param domEventId
 */
Mautic.campaignBuilderUpdateLabel = function (domEventId) {
    var theLabel = typeof Mautic.campaignBuilderLabels[domEventId] == 'undefined' ? '' : Mautic.campaignBuilderLabels[domEventId];
    var currentConnections = Mautic.campaignBuilderInstance.select({
        target: domEventId
    });

    if (currentConnections.length > 0) {
        currentConnections.each(function(conn) {

            //remove current label
            var overlays = conn.getOverlays();
            if (overlays.length > 0) {
                for (var i = 0; i <= overlays.length; i++ ) {
                    if ( typeof overlays[i] != 'undefined' && overlays[i].type == 'Label') {
                        conn.removeOverlay(overlays[i].id);
                    }
                }
            }

            if (theLabel) {
                conn.addOverlay(["Label", {
                    label: theLabel,
                    location: 0.65,
                    cssClass: "jtk-label",
                    id: conn.sourceId + "_" + conn.targetId + "_connectionLabel"
                }]);
            }
        });
    }
};

/**
 * Launch campaign builder modal
 */
Mautic.launchCampaignEditor = function() {
    Mautic.stopIconSpinPostEvent();
    mQuery('body').css('overflow-y', 'hidden');

    mQuery('.builder').addClass('builder-active').removeClass('hide');

    // Center new source
    if (mQuery('#CampaignEvent_newsource').length) {
        Mautic.campaignBuilderPrepareNewSource();
    }

    if (Mautic.campaignBuilderCanvasSettings) {
        Mautic.campaignBuilderInstance.setSuspendDrawing(true);
        Mautic.campaignBuilderReconnectEndpoints();
        Mautic.campaignBuilderInstance.setSuspendDrawing(false, true);
    }
    Mautic.campaignBuilderInstance.repaintEverything();
};

/**
 * Launch campaign preview view
 */
Mautic.launchCampaignPreview = function() {
    Mautic.stopIconSpinPostEvent();

    if (Mautic.campaignBuilderCanvasSettings) {
        Mautic.campaignBuilderInstance.setSuspendDrawing(true);
        Mautic.campaignBuilderReconnectEndpoints();
        Mautic.campaignBuilderInstance.setSuspendDrawing(false, true);
    }
    Mautic.campaignBuilderInstance.repaintEverything();
};

/**
 *
 * @type {{source: {leadsource: {source: Array, action: [*], condition: [*], decision: [*]}, leadsourceleft: {source: [*], action: Array, condition: Array, decision: Array}, leadsourceright: {source: [*], action: Array, condition: Array, decision: Array}}, action: {top: {source: [*], action: Array, condition: [*], decision: [*]}, bottom: {source: Array, action: Array, condition: [*], decision: [*]}}, condition: {top: {source: [*], action: [*], condition: [*], decision: [*]}, yes: {source: Array, action: [*], condition: [*], decision: [*]}, no: {source: Array, action: [*], condition: [*], decision: [*]}}, decision: {top: {action: [*], source: [*], condition: [*], decision: Array}, yes: {source: Array, action: [*], condition: [*], decision: Array}, no: {source: Array, action: [*], condition: [*], decision: Array}}}}
 */
Mautic.campaignBuilderConnectionsMap = {
    // source
    'source': {
        // source anchors
        'leadsource': {
            // target
            'source': [],
            'action': ['top'], // target anchors
            'condition': ['top'],
            'decision': ['top'],
        },
        'leadsourceleft': {
            'source': ['leadsourceright'],
            'action': [],
            'condition': [],
            'decision': []
        },
        'leadsourceright': {
            'source': ['leadsourceleft'],
            'action': [],
            'condition': [],
            'decision': []
        }
    },
    'action': {
        'top': {
            'source': ['leadsource'],
            'action': ['bottom'],
            'condition': ['yes', 'no'],
            'decision': ['yes', 'no']
        },
        'bottom': {
            'source': [],
            'action': ['top'],
            'condition': ['top'],
            'decision': ['top']
        }
    },
    'condition': {
        'top': {
            'source': ['leadsource'],
            'action': ['bottom'],
            'condition': ['yes', 'no'],
            'decision': ['yes', 'no']
        },
        'yes': {
            'source': [],
            'action': ['top'],
            'condition': ['top'],
            'decision': ['top']
        },
        'no': {
            'source': [],
            'action': ['top'],
            'condition': ['top'],
            'decision': ['top']
        }
    },
    'decision': {
        'top': {
            'action': ['bottom'],
            'source': ['leadsource'],
            'condition': ['yes', 'no'],
            'decision': [],
        },
        'yes': {
            'source': [],
            'action': ['top'],
            'condition': ['top'],
            'decision': [],
        },
        'no': {
            'source': [],
            'action': ['top'],
            'condition': ['top'],
            'decision': [],
        }
    }
};

Mautic.campaignBuilderAnchorDefaultColor = '#d5d4d4';

Mautic.campaignEndpointDefinitions = {
    'top': {
        anchors: [0.5, 0, 0, -1, 0, 0],
        isTarget: true
    },
    'bottom': {
        anchors: [0.5, 1, 0, 1, 0, 0],
        isTarget: false
    },
    'yes': {
        anchors: [0, 1, 0, 1, 30, 0],
        connectorColor: '#00b49c',
        isTarget: false
    },
    'no': {
        anchors: [1, 1, 0, 1, -30, 0],
        connectorColor: '#f86b4f',
        isTarget: false
    },
    'leadSource': {
        anchors: [0.5, 1, 0, 1, 0, 0],
        isTarget: false
    },
    'leadSourceLeft': {
        anchors: [0, 0.5, -1, 0, -1, 0],
        connectorColor: '#fdb933',
        isTarget: true,
        connectorStyle: 'Straight'
    },
    'leadSourceRight': {
        anchors: [1, 0.5, 1, 0, 1, 0],
        connectorColor: '#fdb933',
        isTarget: false,
        connectorStyle: 'Straight'
    }
};

/**
 * Push callbacks to these events
 *
 * @type {{connection: Array, connectionDetached: Array, connectionMoved: Array, beforeDrop: Array}}
 */
Mautic.campaignConnectionCallbacks = {
    // sourceEndpoint, targetEndpoint, connection
    'beforeDetach': [],
    // sourceEndpoint, targetEndpoint, endpoint, source, sourceId
    'beforeDrag': [],
    // sourceEndpoint, targetEndpoint, endpoint, source, sourceId
    'beforeStartDetach': [],
    // sourceEndpoint, targetEndpoint, endpoint, source, sourceId, connection
    'beforeDrop': [],
    //sourceEndpoint, endpoint, event
    'onHover': [],
    // no arguments
    'beforeAnchorsRegistered': [],
    'afterAnchorsRegistered': [],
    'beforeEndpointsRegistered': [],
    'beforeEndpointsReconnected': [],
    'afterEndpointsReconnected': []
};

Mautic.campaignBuilderAnchorClicked = false;
Mautic.campaignBuilderEventPositions = {};

Mautic.prepareCampaignCanvas = function() {
    if (typeof Mautic.campaignBuilderInstance == 'undefined') {
        Mautic.campaignBuilderInstance = jsPlumb.getInstance({
            Container: document.querySelector("#CampaignCanvas")
        });

        Mautic.campaignEndpoints = {};

        var startingPosition;
        Mautic.campaignDragOptions = {
            start: function (params) {
                //double clicking activates the stop function so add a catch to prevent unnecessary ajax calls
                startingPosition =
                    {
                        top: params.el.offsetTop,
                        left: params.el.offsetLeft,
                    };

            },
            stop: function (params) {
                var endingPosition =
                    {
                        top: params.finalPos[0],
                        left: params.finalPos[1]
                    };

                if (startingPosition.left !== endingPosition.left || startingPosition.top !== endingPosition.top) {
                    //update coordinates
                    Mautic.campaignBuilderEventPositions[mQuery(params.el).attr('id')] = {
                        'left': parseInt(endingPosition.left),
                        'top': parseInt(endingPosition.top)
                    };

                    var campaignId = mQuery('#campaignId').val();
                    var query = "action=campaign:updateCoordinates&campaignId=" + campaignId + "&droppedX=" + endingPosition.top + "&droppedY=" + endingPosition.left + "&eventId=" + mQuery(params.el).attr('id');
                    mQuery.ajax({
                        url: mauticAjaxUrl,
                        type: "POST",
                        data: query,
                        dataType: "json",
                        error: function (request, textStatus, errorThrown) {
                            Mautic.processAjaxError(request, textStatus, errorThrown);
                        }
                    });
                }
            },
            containment:true
        };

        Mautic.campaignBuilderEventDimensions = {
            'width': 200,
            'height': 45,
            'anchor': 10,
            'wiggleWidth': 30,
            'wiggleHeight': 50
        };

        // Store labels
        Mautic.campaignBuilderLabels = {};

        // Update the labels on connection/disconnection
        Mautic.campaignBuilderInstance.bind("connection", function (info, originalEvent) {
            // Mark the connection so it can be removed if the form is cancelled
            Mautic.campaignBuilderConnectionRequiresUpdate = false;
            Mautic.campaignBuilderLastConnection           = info.connection;

            // If there is a switch between active/inactive anchors, reload the form
            var epDetails          = Mautic.campaignBuilderGetEndpointDetails(info.sourceEndpoint);
            var targetElementId    = info.targetEndpoint.elementId;

            var previousConnection = mQuery('#'+targetElementId).attr('data-connected');
            var editButton         = mQuery('#'+targetElementId).find('a.btn-edit');
            var editUrl            = editButton.attr('href');

            if (editUrl) {
                var anchorQueryParams = 'anchor=' + epDetails.anchorName + "&anchorEventType=" + epDetails.eventType;
                if (editUrl.search('anchor=') !== -1) {
                    editUrl.replace(/anchor=(.*?)$/, anchorQueryParams);
                } else {
                    var delimiter = (editUrl.indexOf('?') === -1) ? '?' : '&';
                    editUrl = editUrl + delimiter + anchorQueryParams;
                }
                editButton.attr('data-href', editUrl);

                if (previousConnection && previousConnection != epDetails.anchorName && (previousConnection == 'no' || epDetails.anchorName == 'no')) {
                    editButton.attr('data-prevent-dismiss', true);
                    Mautic.campaignBuilderConnectionRequiresUpdate = true;

                    editButton.trigger('click');
                }
            }

            mQuery('#'+targetElementId).attr('data-connected', epDetails.anchorName);

            Mautic.campaignBuilderUpdateLabel(info.connection.targetId);
            info.targetEndpoint.setPaintStyle(
                {
                    fill: info.connection.getPaintStyle().stroke
                }
            );
            info.sourceEndpoint.setPaintStyle(
                {
                    fill: info.connection.getPaintStyle().stroke
                }
            );
        });

        Mautic.campaignBuilderInstance.bind("connectionDetached", function (info, originalEvent) {
            Mautic.campaignBuilderUpdateLabel(info.connection.targetId);

            info.targetEndpoint.setPaintStyle(
                {
                    fill: "#d5d4d4"
                }
            );

            var currentConnections = info.sourceEndpoint.connections.length;
            // JavaScript counts index which still accounts for old connection
            currentConnections -= 1;
            if (!currentConnections) {
                info.sourceEndpoint.setPaintStyle(
                    {
                        fill: "#d5d4d4"
                    }
                );
            }
        });

        Mautic.campaignBuilderInstance.bind("connectionMoved", function (info, originalEvent) {
            Mautic.campaignBuilderUpdateLabel(info.connection.originalTargetId);

            info.originalTargetEndpoint.setPaintStyle(
                {
                    fill: "#d5d4d4"
                }
            );

            Mautic.campaignBuilderUpdateLabel(info.connection.newTargetId);

            info.newTargetEndpoint.setPaintStyle(
                {
                    fill: info.newSourceEndpoint.getPaintStyle().fill
                }
            );
        });

        mQuery('.builder-content').scroll(function () {
            Mautic.campaignBuilderInstance.repaintEverything();
        });

        mQuery.each(Mautic.campaignConnectionCallbacks.beforeEndpointsRegistered, function (index, callback) {
            callback();
        });
        mQuery.each(Mautic.campaignEndpointDefinitions, function (ep, definition) {
            Mautic.campaignBuilderRegisterEndpoint(ep, definition);
        });

        //manually loop through each so a UUID can be set for reconnecting connections
        mQuery.each(Mautic.campaignConnectionCallbacks.beforeAnchorsRegistered, function (index, callback) {
            callback();
        });

        mQuery("#CampaignCanvas div[data-event-id]").each(function () {
            var event = Mautic.campaignBuilderCanvasEvents[mQuery(this).data('eventId')];

            Mautic.campaignBuilderRegisterAnchors(Mautic.getAnchorsForEvent(event), this);
        });

        mQuery("#CampaignCanvas div.list-campaign-event.list-campaign-source").not('#CampaignEvent_newsource').not('#CampaignEvent_newsource_hide').each(function () {
            Mautic.campaignBuilderRegisterAnchors(['bottom'], this);
        });

        mQuery("#CampaignCanvas div.list-campaign-leadsource").not('#CampaignEvent_newsource').not('#CampaignEvent_newsource_hide').each(function () {
            Mautic.campaignBuilderRegisterAnchors(['leadSource', 'leadSourceLeft', 'leadSourceRight'], this);
        });

        mQuery.each(Mautic.campaignConnectionCallbacks.afterAnchorsRegistered, function (index, callback) {
            callback();
        });

        if (mQuery('.preview').length) {
            Mautic.launchCampaignPreview();
        } else {
            //enable drag and drop
            Mautic.campaignBuilderInstance.draggable(
                document.querySelectorAll("#CampaignCanvas .draggable"),
                Mautic.campaignDragOptions
            );
        }
    }
};

/**
 * Validate a connection before it can be made
 *
 * @param params
 * @returns {boolean}
 */
Mautic.campaignBeforeDropCallback = function(params) {
    var sourceEndpoint = Mautic.campaignBuilderGetEndpointDetails(params.connection.endpoints[0]);
    var targetEndpoint = Mautic.campaignBuilderGetEndpointDetails(params.dropEndpoint);

    var callbackAllowed = null;
    mQuery.each(Mautic.campaignConnectionCallbacks.beforeDrop, function(index, callback) {
        var result = callback(sourceEndpoint, targetEndpoint, params);
        if (null !== result) {
            callbackAllowed = result;

            return false;
        }
    });
    if (null !== callbackAllowed) {
        return callbackAllowed;
    }

    if (!Mautic.campaignBuilderValidateConnection(sourceEndpoint, targetEndpoint.eventType, targetEndpoint.event)){

        return false;
    }

    if (mQuery.inArray(targetEndpoint.anchorName, ['top', 'leadsourceleft', 'leadsourceright'])) {
        //ensure two events aren't looping
        var sourceConnections = Mautic.campaignBuilderInstance.select({
            source: params.targetId
        });

        var loopDetected = false;
        sourceConnections.each(function (conn) {

            if (conn.sourceId == targetEndpoint.elementId && conn.targetId == sourceEndpoint.elementId) {
                loopDetected = true;

                return false;
            }
        });
    }

    //ensure that the connections are not looping back into the same event
    if (params.sourceId == params.targetId) {

        return false;
    }

    // ensure the map allows this connection
    var allowedConnections = Mautic.campaignBuilderConnectionsMap[sourceEndpoint.eventType][sourceEndpoint.anchorName][targetEndpoint.eventType];

    var allowed = mQuery.inArray(targetEndpoint.anchorName, allowedConnections) !== -1;

    if (allowed) {
        //ensure that a top only has one connection at a time unless connected from a source
        if (params.dropEndpoint.connections.length > 0) {

            // Replace the connection
            mQuery.each(params.dropEndpoint.connections, function(key, conn) {
                Mautic.campaignBuilderInstance.detach(conn);
            });
        }
    }

    return allowed;

};

/**
 * Process beforeDetach event callbacks
 *
 * @param connection
 * @returns {*}
 */
Mautic.campaignBeforeDetachCallback = function(connection) {
    var sourceEndpoint = Mautic.campaignBuilderGetEndpointDetails(connection.sourceId);
    var targetEndpoint = Mautic.campaignBuilderGetEndpointDetails(connection.targetId);

    var callbackAllowed = null;
    mQuery.each(Mautic.campaignConnectionCallbacks.beforeDetach, function (index, callback) {
        var result = callback(sourceEndpoint, targetEndpoint, connection);
        if (null !== result) {
            callbackAllowed = result;

            return false;
        }
    });

    if (null !== callbackAllowed) {
        return callbackAllowed;
    }

    return true;
};

/**
 * Process beforeDetach event callbacks
 *
 * @param connection
 */
Mautic.campaignBeforeDragCallback = function(endpoint, source, sourceId) {
    var sourceEndpoint = Mautic.campaignBuilderGetEndpointDetails(sourceId);
    var targetEndpoint = Mautic.campaignBuilderGetEndpointDetails(endpoint);

    var callbackAllowed = null;
    mQuery.each(Mautic.campaignConnectionCallbacks.beforeDrag, function (index, callback) {
        var result = callback(sourceEndpoint, targetEndpoint, endpoint, source, sourceId);
        if (null !== result) {
            callbackAllowed = result;

            return false;
        }
    });

    if (null !== callbackAllowed) {
        return callbackAllowed;
    }

    return true;
};

/**
 * Process beforeDetach event callbacks
 *
 * @param endpoint
 * @param source
 * @param sourceId
 * @param connection
 * @returns {*}
 */
Mautic.campaignBeforeStartDetachCallback = function(endpoint, source, sourceId, connection) {
    var sourceEndpoint = Mautic.campaignBuilderGetEndpointDetails(sourceId);
    var targetEndpoint = Mautic.campaignBuilderGetEndpointDetails(endpoint);

    var callbackAllowed = null;
    mQuery.each(Mautic.campaignConnectionCallbacks.beforeStartDetach, function (index, callback) {
        var result = callback(sourceEndpoint, targetEndpoint, endpoint, source, sourceId, connection);
        if (null !== result) {
            callbackAllowed = result;

            return false;
        }
    });

    if (null !== callbackAllowed) {
        return callbackAllowed;
    }

    return true;
};

/**
 * Process beforeDetach event callbacks
 *
 * @param connection
 */
Mautic.campaignHoverCallback = function(sourceEndpoint, endpoint, event) {
    var callbackAllowed = null;
    mQuery.each(Mautic.campaignConnectionCallbacks.onHover, function (index, callback) {
        var result = callback(sourceEndpoint, endpoint, event);
        if (null !== result) {
            callbackAllowed = result;

            return false;
        }
    });

    if (null !== callbackAllowed) {
        return callbackAllowed;
    }

    return true;
};

/**
 * Enable/Disable timeframe settings if the toggle for immediate trigger is changed
 */
Mautic.campaignToggleTimeframes = function() {
    if (mQuery('#campaignevent_triggerMode_2').length) {
        var immediateChecked = mQuery('#campaignevent_triggerMode_0').prop('checked');
        var intervalChecked = mQuery('#campaignevent_triggerMode_1').prop('checked');
        var dateChecked = mQuery('#campaignevent_triggerMode_2').prop('checked');
    } else {
        var immediateChecked = false;
        var intervalChecked = mQuery('#campaignevent_triggerMode_0').prop('checked');
        var dateChecked = mQuery('#campaignevent_triggerMode_1').prop('checked');
    }

    if (mQuery('#campaignevent_triggerInterval').length) {
        if (immediateChecked) {
            mQuery('#triggerInterval').addClass('hide');
            mQuery('#triggerDate').addClass('hide');
        } else if (intervalChecked) {
            mQuery('#triggerInterval').removeClass('hide');
            mQuery('#triggerDate').addClass('hide');
        } else if (dateChecked) {
            mQuery('#triggerInterval').addClass('hide');
            mQuery('#triggerDate').removeClass('hide');
        }
    }
};

/**
 * Close campaign builder
 */
Mautic.closeCampaignBuilder = function() {
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

    var overlay = mQuery('<div id="builder-overlay" class="modal-backdrop fade in"><div style="position: absolute; top:' + spinnerTop + 'px; left:' + spinnerLeft + 'px" class=".builder-spinner"><i class="fa fa-spinner fa-spin fa-5x"></i></div></div>').css(builderCss).appendTo('.builder-content');
    mQuery('.btn-close-builder').prop('disabled', true);

    Mautic.removeButtonLoadingIndicator(mQuery('.btn-apply-builder'));
    mQuery('#builder-errors').hide('fast').text('');

    Mautic.updateConnections(function(err, response) {
        mQuery('body').css('overflow-y', '');

        if (!err) {
            mQuery('#builder-overlay').remove();
            mQuery('body').css('overflow-y', '');
            if (response.success) {
                mQuery('.builder').addClass('hide').removeClass('builder-active');
            }
            mQuery('.btn-close-builder').prop('disabled', false);
        }
    });
};

Mautic.saveCampaignFromBuilder = function() {
    Mautic.activateButtonLoadingIndicator(mQuery('.btn-apply-builder'));
    Mautic.updateConnections(function(err) {
        if (!err) {
            var applyBtn = mQuery('.btn-apply');
            Mautic.inBuilderSubmissionOn(applyBtn.closest('form'));
            applyBtn.trigger('click');
            Mautic.inBuilderSubmissionOff();
        }
    });
};

Mautic.updateConnections = function(callback) {
    var nodes = [];

    mQuery("#CampaignCanvas .list-campaign-event").each(function (idx, elem) {
        nodes.push({
            id:        mQuery(elem).attr('id').replace('CampaignEvent_', ''),
            positionX: parseInt(mQuery(elem).css('left'), 10),
            positionY: parseInt(mQuery(elem).css('top'), 10)
        });
    });

    mQuery("#CampaignCanvas .list-campaign-source").not('#CampaignEvent_newsource').not('#CampaignEvent_newsource_hide').each(function (idx, elem) {
        nodes.push({
            id:        mQuery(elem).attr('id').replace('CampaignEvent_', ''),
            positionX: parseInt(mQuery(elem).css('left'), 10),
            positionY: parseInt(mQuery(elem).css('top'), 10)
        });
    });

    var connections = [];
    mQuery.each(Mautic.campaignBuilderInstance.getConnections(), function (idx, connection) {
        connections.push({
            sourceId:     connection.sourceId.replace('CampaignEvent_', ''),
            targetId:     connection.targetId.replace('CampaignEvent_', ''),
            anchors:      mQuery.map(connection.endpoints, function (endpoint) {
                var anchor = Mautic.campaignBuilderGetEndpointDetails(endpoint);
                return {
                    'endpoint': anchor.anchorName,
                    'eventId':  anchor.eventId
                };
            })
        });
    });

    var chart          = {};
    chart.nodes        = nodes;
    chart.connections  = connections;
    var canvasSettings = {canvasSettings: chart};

    var campaignId     = mQuery('#campaignId').val();
    var query          = "action=campaign:updateConnections&campaignId=" + campaignId;

    mQuery.ajax({
        url: mauticAjaxUrl + '?' + query,
        type: "POST",
        data: canvasSettings,
        dataType: "json",
        success: function (response) {
            if (typeof callback === 'function') callback(false, response);
        },
        error: function (response, textStatus, errorThrown) {
            Mautic.processAjaxError(response, textStatus, errorThrown);
            if (typeof callback === 'function') callback(true, response);
        }
    });
};

/**
 * Submit the campaign event form
 * @param e
 */
Mautic.submitCampaignEvent = function(e) {
    e.preventDefault();

    mQuery('#campaignevent_canvasSettings_droppedX').val(mQuery('#droppedX').val());
    mQuery('#campaignevent_canvasSettings_droppedY').val(mQuery('#droppedY').val());

    mQuery('form[name="campaignevent"]').submit();
};

/**
 * Submit source form
 * @param e
 */
Mautic.submitCampaignSource = function(e) {
    e.preventDefault();

    mQuery('#campaign_leadsource_droppedX').val(mQuery('#droppedX').val());
    mQuery('#campaign_leadsource_droppedY').val(mQuery('#droppedY').val());

    mQuery('form[name="campaign_leadsource"]').submit();
};

/**
 * Reconnect jsplumb connections
 */
Mautic.campaignBuilderReconnectEndpoints = function () {

    mQuery.each(Mautic.campaignConnectionCallbacks.beforeEndpointsReconnected, function (index, callback) {
        callback();
    });

    if (typeof Mautic.campaignBuilderCanvasSettings == 'undefined') {
        return;
    }

    if (typeof Mautic.campaignBuilderCanvasSettings.nodes !== 'undefined') {
        // Reposition events
        var sourceFound = false;
        mQuery.each(Mautic.campaignBuilderCanvasSettings.nodes, function (key, node) {
            if (typeof Mautic.campaignBuilderCanvasSources[node.id] !== 'undefined') {
                sourceFound = true;
            }

            mQuery('#CampaignEvent_' + node.id).css({
                position: 'absolute',
                left: node.positionX + 'px',
                top: node.positionY + 'px'
            });

            Mautic.campaignBuilderEventPositions['CampaignEvent_' + node.id] = {
                left: parseInt(node.positionX),
                top: parseInt(node.positionY)
            };
        });
    }

    if (typeof Mautic.campaignBuilderCanvasSettings.connections !== 'undefined') {

        // Recreate jsPlumb connections and labels
        mQuery.each(Mautic.campaignBuilderCanvasSettings.connections, function (key, connection) {
            if (typeof Mautic.campaignBuilderCanvasEvents[connection.targetId] !== 'undefined') {
                var targetEvent = Mautic.campaignBuilderCanvasEvents[connection.targetId];
            } else if (typeof Mautic.campaignBuilderCanvasSources[connection.targetId] !== 'undefined') {
                var targetEvent = Mautic.campaignBuilderCanvasSources[connection.targetId];
            }

            if (targetEvent && targetEvent.label) {
                Mautic.campaignBuilderLabels["CampaignEvent_" + connection.targetId] = targetEvent.label;
            }

            Mautic.campaignBuilderInstance.connect({
                uuids: [
                    "CampaignEvent_" + connection.sourceId + '_' + connection.anchors.source,
                    "CampaignEvent_" + connection.targetId + '_' + connection.anchors.target
                ]
            });
        });
    }

    if (!sourceFound) {
        var topOffset = 25;
        mQuery.each(Mautic.campaignBuilderCanvasSources, function (type, source) {
            mQuery('#CampaignEvent_' + type).css({
                position: 'absolute',
                left: '20px',
                top: topOffset + 'px'
            });
        });

        topOffset += 45;
    }

    mQuery.each(Mautic.campaignConnectionCallbacks.afterEndpointsReconnected, function (index, callback) {
        callback();
    });

    delete Mautic.campaignBuilderCanvasSettings;
};

/**
 * Register an endpoint with JsPlumb
 *
 * @param name
 * @param params
 */
Mautic.campaignBuilderRegisterEndpoint = function (name, params) {
    var isTarget, isSource, color, connectorColor, connectorStyle;
    if (params.color) {
        color = params.color;
    } else {
        color = Mautic.campaignBuilderAnchorDefaultColor;
    }

    if (params.connectorColor) {
        connectorColor = params.connectorColor;
    } else {
        connectorColor = color;
    }

    if (params.connectorStyle) {
        connectorStyle = params.connectorStyle;
    } else {
        connectorStyle = ["Bezier", {curviness: 25}];
    }

    isTarget = params.isTarget;
    isSource = true;
    if (isTarget === null) {
        // Both are allowed
        isTarget = true;
    } else {
        if (typeof isTarget == 'undefined') {
            isTarget = false;
        }
        if (isTarget) {
            isSource = false;
        }
    }

    Mautic.campaignEndpoints[name] = {
        endpoint: ["Dot", { radius: 10 }],
        paintStyle: {
            fill: color
        },
        endpointStyle: {
          fill: color
        },
        connectorStyle: {
            stroke: connectorColor,
            strokeWidth: 1
        },
        connector: connectorStyle,
        connectorOverlays: [["Arrow", {width: 8, length: 8, location: 0.5}]],
        maxConnections: -1,
        isTarget: isTarget,
        isSource: isSource,
        beforeDrop: Mautic.campaignBeforeDropCallback,
        beforeDetach: Mautic.campaignBeforeDetachCallback,
        beforeStartDetach: Mautic.campaignBeforeStartDetachCallback,
        beforeDrag: Mautic.campaignBeforeDragCallback
    }
};

/**
 * Register an anchor with JsPlumb
 *
 * @param names
 * @param el
 */
Mautic.campaignBuilderRegisterAnchors = function(names, el) {
    var id = mQuery(el).attr('id');

    mQuery(names).each(function(key, anchorName) {
        var theAnchor = Mautic.campaignEndpointDefinitions[anchorName]['anchors'];
        theAnchor[6] = anchorName.toLowerCase() + ' ' + id;
        var ep = Mautic.campaignBuilderInstance.addEndpoint(
            id,
            {
                anchor: theAnchor,
                uuid: id + "_" + anchorName.toLowerCase()
            },
            Mautic.campaignEndpoints[anchorName]
        );

        ep.bind("mouseover", function (endpoint, event) {
            var epDetails = Mautic.campaignBuilderGetEndpointDetails(endpoint);

            if (!Mautic.campaignHoverCallback(epDetails, endpoint, event)) {
                return;
            }

            if (epDetails.anchorName == 'top') {
                // Don't add a plus sign to a top anchor
                return;
            }

            // If this is a leadsourceleft or leadsourceright anchor - ensure there are source options available before allowing to add a new
            if (epDetails.anchorName == 'leadsourceleft' || epDetails.anchorName == 'leadsourceright') {
                if (mQuery('#SourceList option:enabled').length === 1) {
                    // Accounts for empty option

                    return;
                }
            }

            // Set color style
            endpoint.setPaintStyle(
                {
                    fill: endpoint.connectorStyle.stroke
                }
            );

            var dot = mQuery(endpoint.canvas);
            dot.addClass('jtk-clickable_anchor');

            if (!dot.find('svg text').length) {
                // Add a plus sign to SVG
                var svg = dot.find('svg')[0];

                var textElement = document.createElementNS("http://www.w3.org/2000/svg", 'text');
                textElement.setAttributeNS(null, 'x', '50%');
                textElement.setAttributeNS(null, 'y', '50%');
                textElement.setAttributeNS(null, 'text-anchor', 'middle');
                textElement.setAttributeNS(null, 'stroke-width', '2px');
                textElement.setAttributeNS(null, 'stroke', '#ffffff');
                textElement.setAttributeNS(null, 'dy', '.3em');

                var textNode = document.createTextNode('+');
                textElement.appendChild(textNode);
                svg.appendChild(textElement);
            }
        });

        ep.bind("mouseout", function (endpoint) {
            var dot = mQuery(endpoint.canvas);
            dot.removeClass('jtk-clickable_anchor');

            if (!endpoint.connections.length) {
                // Set color style
                endpoint.setPaintStyle(
                    {
                        fill: Mautic.campaignBuilderAnchorDefaultColor
                    }
                );

            }
        });

        ep.bind("click", function (endpoint, event) {
            if (mQuery('#CampaignEvent_newsource').length) {
                // Can't do anything until a new source is added

                return;
            }

            var epDetails = Mautic.campaignBuilderGetEndpointDetails(endpoint);
            if (epDetails.anchorName == 'top') {
                // Don't do anything for top anchors

                return;
            }

            // If this is a leadsourceleft or leadsourceright anchor - ensure there are source options available before allowing to add a new
            if (epDetails.anchorName == 'leadsourceleft' || epDetails.anchorName == 'leadsourceright') {
                if (mQuery('#SourceList option:enabled').length === 1) {
                    // Accounts for empty option

                    return;
                }
            }

            // Note the anchor so it can be auto attached after the event is created
            var epDetails = Mautic.campaignBuilderGetEndpointDetails(endpoint);
            var clickedAnchorName = epDetails.anchorName;
            Mautic.campaignBuilderAnchorClicked = endpoint.elementId+'_'+clickedAnchorName;
            Mautic.campaignBuilderAnchorNameClicked = clickedAnchorName;
            Mautic.campaignBuilderAnchorEventTypeClicked = epDetails.eventType;

            // Get the position of the event
            var elPos = Mautic.campaignBuilderGetEventPosition(endpoint.element);
            var spotFound = false,
                putLeft = elPos.left,
                putTop = elPos.top,
                direction = '', // xl, xr, yu, yd
                fullWidth = Mautic.campaignBuilderEventDimensions.width + Mautic.campaignBuilderEventDimensions.anchor,
                wiggleWidth = fullWidth + Mautic.campaignBuilderEventDimensions.wiggleWidth,
                fullHeight = Mautic.campaignBuilderEventDimensions.height + Mautic.campaignBuilderEventDimensions.anchor,
                wiggleHeight = fullHeight + Mautic.campaignBuilderEventDimensions.wiggleHeight,
                debug = false;

            if (debug) {
                console.log(Mautic.campaignBuilderEventPositions);
                console.log(clickedAnchorName+' - starting with: x = '+ putLeft+', y = '+putTop);
            }

            switch (clickedAnchorName) {
                case 'leadsourceleft':
                    direction = 'xl';
                    putLeft -= wiggleWidth;
                    break;
                case 'leadsourceright':
                    direction = 'xr';
                    putLeft += wiggleWidth;
                    break;
                case 'bottom':
                    direction = 'yd';
                    putTop += wiggleHeight;
                    break;
                case 'yes':
                case 'leadsource':
                    // Place slightly to the left of the anchor
                    putLeft -= Mautic.campaignBuilderEventDimensions.width/2;
                    putTop  += wiggleHeight;
                    direction = 'xl';
                    break;
                case 'no':
                    // Place slightly to the left of the anchor
                    putLeft += Mautic.campaignBuilderEventDimensions.width/2;
                    putTop  += wiggleHeight;
                    direction = 'xr';
                    break;
                case 'top':
                    directon = 'yu';
                    putTop -= wiggleHeight;
                    break;
            }

            if (debug) {
                console.log('Going direction: '+direction);
                console.log('Start test with: x = '+ putLeft+', y = '+putTop);

            }

            var counter = 0;
            var windowWidth = mQuery(window).width();
            while (!spotFound) {
                // Find out if spot is taken
                var isOccupied = false;
                mQuery.each(Mautic.campaignBuilderEventPositions, function (id, pos) {
                    var l = Math.max(putLeft, pos.left);
                    var r = Math.min(putLeft + fullWidth, pos.left + fullWidth);
                    var b = Math.max(putTop, pos.top);
                    var t = Math.min(putTop + fullHeight, pos.top + fullHeight);
                    var h = t-b;
                    var w = r-l;
                    if (debug) {
                        console.log('Checking ' + id);
                        console.log(putLeft, putTop, l,r,b,t,h,w);
                    }

                    if (h > 0 && w > 0) {
                        if (debug) {
                            console.log('Slot occupied by '+id);
                        }

                        isOccupied = true;

                        // xl, xr, yu, yd
                        switch (direction) {
                            case 'xl':
                                putLeft -= (w + Mautic.campaignBuilderEventDimensions.wiggleWidth);
                                if (putLeft <= 0) {
                                    putLeft = 0;
                                    // Ran out of room so go down
                                    direction = 'yd';
                                    putTop += fullHeight + Mautic.campaignBuilderEventDimensions.wiggleHeight;
                                }
                                break;
                            case 'xr':
                                if (putLeft + w + Mautic.campaignBuilderEventDimensions.wiggleWidth > windowWidth) {
                                    // Hit right canvas so start going down by default
                                    direction = 'yd';
                                    putLeft -= Mautic.campaignBuilderEventDimensions.wiggleWidth;
                                    putTop += fullHeight + Mautic.campaignBuilderEventDimensions.wiggleHeight;
                                } else {
                                    putLeft += (w + Mautic.campaignBuilderEventDimensions.wiggleWidth);
                                }
                                break;
                            case 'yu':
                                putTop -= (h - Mautic.campaignBuilderEventDimensions.wiggleHeight);
                                if (putTop <= 0) {
                                    putTop = 0;
                                    // Ran out of room going up so try the right
                                    direction = 'xr';
                                }
                                break;
                            case 'yd':
                                putTop += (h + Mautic.campaignBuilderEventDimensions.wiggleHeight);
                                break;
                        }

                        return false
                    }
                });

                if (!isOccupied) {
                    if (debug) {
                       console.log('It fits!');
                    }

                    spotFound = true;
                }

                counter++;
                if (counter >= 100) {
                    putTop = 10;
                    putLeft = 10;
                    if (debug) {
                        console.log('Too many loops');
                    }
                    spotFound = true;
                }
            }

            if (debug) {
                console.log('To be placed at: x = '+ putLeft+', y = '+putTop);
            }

            if (putLeft <= 0) {
                putLeft = 10;
            }
            if (putTop <= 0) {
                putTop = 10;
            }

            // Mark the spot
            mQuery('#droppedX').val(putLeft);
            mQuery('#droppedY').val(putTop);

            // Update the event selector
            var allowedEvents = [];
            mQuery.each(Mautic.campaignBuilderConnectionsMap[epDetails.eventType][epDetails.anchorName], function (group, eventTypes) {
                if (eventTypes.length) {
                    allowedEvents[allowedEvents.length] = group.charAt(0).toUpperCase() + group.substr(1);
                }
            });
            Mautic.campaignBuilderAnchorClickedAllowedEvents = allowedEvents;

            if (!(mQuery('.preview').length)) {
                var el = (mQuery(event.target).hasClass('jtk-endpoint')) ? event.target : mQuery(event.target).parents('.jtk-endpoint')[0];
                Mautic.campaignBuilderAnchorClickedPosition = Mautic.campaignBuilderGetEventPosition(el);
                Mautic.campaignBuilderUpdateEventList(allowedEvents, false, 'groups');
            }

            // Disable the list items not allowed
            mQuery('.campaign-event-selector:not(#SourceList) option').prop('disabled', false);
            if ('source' == epDetails.eventType) {
                var checkSelects = ['action', 'decision', 'condition'];
            } else {
                var primaryType       = (epDetails.eventType === 'decision') ? 'action': 'decision';
                var checkSelects = [primaryType, 'condition'];
            }

            mQuery.each(checkSelects, function(key, targetType) {
                var selectId = '#' + targetType.charAt(0).toUpperCase() + targetType.slice(1) + 'List';
                mQuery(selectId + ' option').each(function () {
                    var optionVal = mQuery(this).val();
                    if (optionVal) {
                        if (!Mautic.campaignBuilderValidateConnection(epDetails, targetType, optionVal)) {
                            mQuery(this).prop('disabled', true);
                        }
                    }
                });
                mQuery(selectId).trigger('chosen:updated');
            });
        });
    });
};

/**
 * Extract information about an event/endpoint from element
 *
 * @param el
 * @returns {{left: Number, top: Number}}
 */
Mautic.campaignBuilderGetEventPosition = function(el) {
    return {
        'left': parseInt(mQuery(el).css('left')),
        'top': parseInt(mQuery(el).css('top'))
    }
};

/**
 * Update the event select list
 *
 * @param groups
 * @param hidden
 * @param view
 * @param active
 * @param forcePosition
 */
Mautic.campaignBuilderUpdateEventList = function (groups, hidden, view, active, forcePosition) {
    var groupsEnabled = 0;
    var inGroupsView = ('groups' == view);

    if (groups.length === 1 && mQuery.inArray('Source', groups) !== -1 && !hidden) {
        // Force groups mode
        inGroupsView = false;
    }
    mQuery.each(['Source', 'Action', 'Decision', 'Condition'], function (key, theGroup) {
        if (mQuery.inArray(theGroup, groups) !== -1) {
            if (inGroupsView) {
                mQuery('#' + theGroup + 'GroupSelector').removeClass('hide');

                if ('source' != theGroup) {
                    groupsEnabled++;
                }
            } else {
                mQuery('#' + theGroup + 'GroupList').removeClass('hide');
            }
        } else {
            if (inGroupsView) {
                mQuery('#' + theGroup + 'GroupSelector').addClass('hide');
            } else {
                mQuery('#' + theGroup + 'GroupList').addClass('hide');
            }
        }
    });

    if (inGroupsView) {
        mQuery.each(groups, function (key, theGroup) {
            mQuery('#'+theGroup+'GroupSelector').removeClass(
                function (index, css) {
                    return (css.match(/col-(\S+)/g) || []).join(' ');
                }
            ).addClass('col-md-' + (12 / groupsEnabled));
        });

        var newWidth = (500 / 3) * groupsEnabled;
        if (newWidth >= mQuery(window).width()) {
            newWidth = mQuery(window).width() - 10;
        }

        var leftPos = (forcePosition) ? forcePosition.left : Mautic.campaignBuilderAnchorClickedPosition.left - (newWidth / 2 - 10);
        var topPos  = (forcePosition) ? forcePosition.top : Mautic.campaignBuilderAnchorClickedPosition.top + 25;
        mQuery('#CampaignEventPanel').css({
                left: (leftPos >=0 ) ? leftPos : 10,
                top: topPos,
                width: newWidth,
                height: 280
            });

        mQuery('#CampaignEventPanel').removeClass('hide');
        mQuery('#CampaignEventPanelGroups').removeClass('hide');
        mQuery('#CampaignEventPanelLists').addClass('hide');
    } else {
        var leftPos = (forcePosition) ? forcePosition.left : Mautic.campaignBuilderAnchorClickedPosition.left - 125;
        var topPos  = (forcePosition) ? forcePosition.top : Mautic.campaignBuilderAnchorClickedPosition.top + 25;
        mQuery('#CampaignEventPanel').css({
            left: (leftPos >= 0) ? leftPos : 10,
            top: topPos,
            width: 300,
            height: 80,
        });

        mQuery('#CampaignEventPanelGroups').addClass('hide');
        mQuery('#CampaignEventPanelLists').removeClass('hide');
        mQuery('#CampaignEventPanel').removeClass('hide');

        if (groups.length === 1) {
            setTimeout(function () {
                // Activate chosen
                mQuery('#CampaignEventPanelLists #' + groups[0] + 'List').trigger('chosen:open');
            }, 10);
        }
    }
};

/**
 *
 * @param endpoint
 * @param nameOnly
 * @returns {{endpointName: *, elementId: *}}
 */
Mautic.campaignBuilderGetEndpointDetails = function(endpoint) {
    var anchorName, eventId;

    if (typeof endpoint === 'string') {
        eventId = endpoint;
    } else {
        var parts = endpoint.anchor.cssClass.split(' ');
        if (parts.length > 1) {
            anchorName = parts[0];
            eventId = parts[1];
        } else {
            anchorName = parts[0];
            eventId = endpoint.elementId
        }
    }

    return {
        'anchorName': anchorName,
        'eventId': eventId.replace('CampaignEvent_', ''),
        'elementId' : eventId,
        'eventType': mQuery('#'+eventId).data('type'),
        'event': mQuery('#'+eventId).data('event')
    };
};

/**
 * Display new source when required
 */
Mautic.campaignBuilderPrepareNewSource = function () {
    var newSourcePos = {
        left: mQuery(window).width()/2 - 100,
        top: 50
    };

    mQuery('#CampaignEvent_newsource').css(newSourcePos);

    Mautic.campaignBuilderUpdateEventList(['Source'], false, 'list', false, {
        left: newSourcePos.left - 50,
        top: newSourcePos.top + 35
    });
    mQuery('#SourceList').trigger('chosen:open');
};

/**
 *
 * @param epDetails
 * @param optionVal
 * @returns {boolean}
 */
Mautic.campaignBuilderValidateConnection = function (epDetails, targetType, targetEvent) {
    var valid = true;
    var sourceType  = epDetails.eventType;
    var sourceEvent = 'source' === sourceType ? sourceType : epDetails.event;

    if (typeof Mautic.campaignBuilderConnectionRestrictions[targetEvent] !== 'undefined') {
        if ('source' === sourceEvent) {
            // If there are any restrictions, then don't allow it to be the target of the campaign source
            mQuery.each(Mautic.campaignBuilderConnectionRestrictions[targetEvent]['source'], function(eventType, events) {
                if (events.length) {
                    valid = false;

                    // break the loop
                    return false;
                }
            });

            return valid;
        }

        if (
            typeof Mautic.campaignBuilderConnectionRestrictions[targetEvent]['source'][sourceType] !== 'undefined' &&
            Mautic.campaignBuilderConnectionRestrictions[targetEvent]['source'][sourceType].length &&
            mQuery.inArray(sourceEvent, Mautic.campaignBuilderConnectionRestrictions[targetEvent]['source'][sourceType]) === -1
        ) {
            // If the source event is not included in the source list of the target event, then don't allow it
            valid = false;
        }
    }

    if (
        typeof Mautic.campaignBuilderConnectionRestrictions[sourceEvent] !== 'undefined' &&
        typeof Mautic.campaignBuilderConnectionRestrictions[sourceEvent]['target'][targetType] !== 'undefined' &&
        Mautic.campaignBuilderConnectionRestrictions[sourceEvent]['target'][targetType].length
    ) {
        // If the target event is defined in the target list of the source event, then allow it; otherwise don't allow it
        valid = (mQuery.inArray(targetEvent, Mautic.campaignBuilderConnectionRestrictions[sourceEvent]['target'][targetType]) !== -1);
    }

    if (
        typeof Mautic.campaignBuilderConnectionRestrictions['anchor'][sourceType] !== 'undefined' &&
        typeof Mautic.campaignBuilderConnectionRestrictions['anchor'][sourceType][targetEvent] !== 'undefined'
    ) {
        mQuery(Mautic.campaignBuilderConnectionRestrictions['anchor'][sourceType][targetEvent]).each(
            function(key, anchor) {
                switch (anchor) {
                    case 'inaction':
                        anchor = 'no';
                        break;
                    case 'action':
                        anchor = 'yes';
                        break;
                }

                if (anchor == epDetails.anchorName) {
                    valid = false;

                    // Break form the loop
                    return false;
                }
            }
        );
    }

    return valid;
};

/**
 *
 * @param eventId
 * @param contactId
 */
Mautic.updateScheduledCampaignEvent = function(eventId, contactId) {
    // Convert scheduled date/time to an input
    mQuery('#timeline-campaign-event-'+eventId+' .btn-edit').prop('disabled', true).addClass('disabled');

    var converting = false;
    var eventWrapper = '#timeline-campaign-event-'+eventId;
    var eventSpan = '.timeline-campaign-event-date-'+eventId;
    var eventText = '#timeline-campaign-event-text-'+eventId;
    var originalDate = mQuery(eventWrapper+' '+eventSpan).first().text();
    var revertInput = function(input) {
        converting = true;
        mQuery(input).datetimepicker('destroy');
        mQuery(eventSpan).text(originalDate);
        mQuery(eventWrapper+' .btn').prop('disabled', false).removeClass('disabled');
    };

    var date = mQuery(eventSpan).attr('data-date');
    var input = mQuery('<input type="text" id="timeline-reschedule"/>')
        .css('height', '20px')
        .css('color', '#000000')
        .val(date)
        .on('keyup', function(e) {
            var code = e.keyCode || e.which;
            if (code == 13) {
                e.preventDefault();
                converting = true
                mQuery(input).prop('readonly', true);
                mQuery(input).datetimepicker('destroy');
                Mautic.ajaxActionRequest('campaign:updateScheduledCampaignEvent',
                    {
                        eventId: eventId,
                        contactId: contactId,
                        date: mQuery(this).val(),
                        originalDate: date
                    }, function (response) {
                        mQuery(eventSpan).text(response.formattedDate);
                        mQuery(eventSpan).attr('data-data', response.date);
                        mQuery(eventWrapper+' .btn').prop('disabled', false).removeClass('disabled');

                        if (response.success) {
                            mQuery(eventText).removeClass('text-warning').addClass('text-info');
                            mQuery(eventSpan).css('textDecoration', 'inherit');
                            mQuery('.fa.timeline-campaign-event-cancelled-'+eventId).remove();
                            mQuery('.timeline-campaign-event-scheduled-'+eventId).removeClass('hide');
                            mQuery('.timeline-campaign-event-cancelled-'+eventId).addClass('hide');
                        }
                    }, false
                );
            } else if (code == 27) {
                e.preventDefault();
                revertInput(input);
            }
        })
        .on('blur', function (e) {
            if (!converting) {
                revertInput(input);
            }
        });
    mQuery('#timeline-campaign-event-'+eventId+' '+eventSpan).html(input);
    Mautic.activateDateTimeInputs('#timeline-reschedule');
};

/**
 *
 * @param eventId
 * @param contactId
 */
Mautic.cancelScheduledCampaignEvent = function(eventId, contactId) {
    mQuery('#timeline-campaign-event-'+eventId+' .btn').prop('disabled', true).addClass('disabled');
    var eventWrapper = '#timeline-campaign-event-'+eventId;
    var eventSpan = '.timeline-campaign-event-date-' + eventId;
    var eventText = '#timeline-campaign-event-text-' + eventId;
    Mautic.ajaxActionRequest('campaign:cancelScheduledCampaignEvent',
        {
            eventId: eventId,
            contactId: contactId,
        }, function (response) {
            if (response.success) {
                mQuery(eventText).removeClass('text-info').addClass('text-warning');
                mQuery(eventWrapper+' .btn-edit').prop('disabled', false).removeClass('disabled');
                mQuery('.timeline-campaign-event-scheduled-'+eventId).addClass('hide');
                mQuery('.timeline-campaign-event-cancelled-'+eventId).removeClass('hide');
            } else {
                mQuery(eventWrapper+' .btn').prop('disabled', false).removeClass('disabled');
            }
        }, false
    );
};

/**
 * Update the "Jump to Event" select list to be available events.
 */
Mautic.updateJumpToEventOptions = function() {
    var jumpToEventSelectNode = mQuery("#campaignevent_properties_jumpToEvent");

    jumpToEventSelectNode.children().remove();

    for (var eventId in Mautic.campaignBuilderCanvasEvents) {
        var event = Mautic.campaignBuilderCanvasEvents[eventId];

        if (event.type !== 'campaign.jump_to_event' && event.eventType !== 'decision') {
            var opt = mQuery("<option />")
                .attr("value", event.id)
                .text(event.name)

            if (event.id == jumpToEventSelectNode.data("selected")) {
                opt.attr("selected", "selected");
            }

            jumpToEventSelectNode.append(opt);
        }
    }

    jumpToEventSelectNode.trigger("chosen:updated");
};

Mautic.highlightJumpTarget = function(event, el) {
    var element = mQuery(el);
    var parentEventElement = element.parent().parent();
    var highlightedAlready = parentEventElement.data('highlighted');
    var jumpTargetID = '#CampaignEvent_' + element.data('jumpTarget');
    var jumpTarget = mQuery(jumpTargetID);
    var overlay = mQuery('#EventJumpOverlay');

    if (highlightedAlready) {
        parentEventElement.data('highlighted', false);
        overlay.hide();
        parentEventElement.css("z-index", 1010);
        jumpTarget.css("z-index", 1010);
    } else {
        parentEventElement.data('highlighted', true);
        overlay.show();
        parentEventElement.css("z-index", 2010);
        jumpTarget.css("z-index", 2010);
    }
};



