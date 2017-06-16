Mautic.disabledFocusActions = function(opener) {
    if (typeof opener == 'undefined') {
        opener = window;
    }
    var email = opener.mQuery('#campaignevent_properties_focus').val();

    var disabled = email === '' || email === null;

    opener.mQuery('#campaignevent_properties_editFocusButton').prop('disabled', disabled);
    opener.mQuery('#campaignevent_properties_previewFocusButton').prop('disabled', disabled);
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