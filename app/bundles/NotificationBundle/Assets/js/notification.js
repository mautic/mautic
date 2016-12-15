/** NotificationBundle **/
Mautic.notificationOnLoad = function (container, response) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'notification');
    }
};

Mautic.selectNotificationType = function(notificationType) {
    if (notificationType == 'list') {
        mQuery('#leadList').removeClass('hide');
        mQuery('#publishStatus').addClass('hide');
        mQuery('.page-header h3').text(mauticLang.newListNotification);
    } else {
        mQuery('#publishStatus').removeClass('hide');
        mQuery('#leadList').addClass('hide');
        mQuery('.page-header h3').text(mauticLang.newTemplateNotification);
    }

    mQuery('#notification_notificationType').val(notificationType);

    mQuery('body').removeClass('noscroll');

    mQuery('.notification-type-modal').remove();
    mQuery('.notification-type-modal-backdrop').remove();
};

Mautic.standardNotificationUrl = function(options) {
    if (!options) {
        return;
    }

    var url = options.windowUrl;
    if (url) {
        var editEmailKey = '/notifications/edit/notificationId';
        var previewEmailKey = '/notifications/preview/notificationId';
        if (url.indexOf(editEmailKey) > -1 ||
            url.indexOf(previewEmailKey) > -1) {
            options.windowUrl = url.replace('notificationId', mQuery('#campaignevent_properties_notification').val());
        }
    }

    return options;
};

Mautic.disabledNotificationAction = function(opener) {
    if (typeof opener == 'undefined') {
        opener = window;
    }

    var notification = opener.mQuery('#campaignevent_properties_notification').val();

    var disabled = notification === '' || notification === null;

    opener.mQuery('#campaignevent_properties_editNotificationButton').prop('disabled', disabled);
};