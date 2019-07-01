Mautic.notificationIndexLoad = function (translations) { 
    mQuery(document).ready(function() { 
        // Hide the New button
        mQuery("a[href$='account/notifications/new']").hide();

        // Mark a notification as read.
        mQuery('.notificationClearBtn').on('click', function() { 
            var tr = mQuery(this).parent().parent();
            var children = tr.children('#isRead');
            children.text(translations['mautic.core.yes']);
            Mautic.clearNotification(mQuery(this).data('notification-id'));
            console.log(mQuery(this).data('notification-id'));
        });
    });
};
