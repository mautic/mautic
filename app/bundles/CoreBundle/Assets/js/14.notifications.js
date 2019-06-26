Mautic.notificationIndexLoad = function (translations) { 
    mQuery(document).ready(function() { 
        // Hide the New button
        mQuery("a[href$='account/notifications/new']").hide();

        // Mark a notification as read.
        mQuery('#notificationTable a')
            .each(function(index) { 
                mQuery(this).on('click', function() { 
                    var tr = mQuery(this).parent().parent();
                    var tdId = tr.children('#notificationId');
                    var id = tdId.text(); 
                    var children = tr.children('#isRead');
                    children.text(translations['mautic.core.yes']);
                    Mautic.clearNotification(id);
                });
            });
    });
};
