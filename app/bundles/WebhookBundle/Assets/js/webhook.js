Mautic.sendHookTest = function() {

    var url = mQuery('#webhook_webhook_url').val();

    var data = 'action=webhook:sendHookTest&url=' + url;

    var spinner = mQuery('#spinner');

    // show the spinner
    spinner.removeClass('hide');

    mQuery.ajax({
        url: mauticAjaxUrl,
        data: data,
        type: 'POST',
        dataType: "json",
        success: function(response) {
            if (response.success) {
                mQuery('#tester').html(response.html);
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        },
        complete: function(response) {
            spinner.addClass('hide');
        }
    })
};