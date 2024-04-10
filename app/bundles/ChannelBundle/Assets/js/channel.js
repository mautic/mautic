Mautic.messagesOnLoad = function(container) {
    mQuery(container + ' .sortable-panel-wrapper .modal').each(function() {
      // Move modals outside of the wrapper
      mQuery(this).closest('.panel').append(mQuery(this));
    });
};

Mautic.toggleChannelFormDisplay = function (el, channel) {
    Mautic.toggleTabPublished(el);

    if (mQuery(el).val() === "1" && mQuery(el).prop('checked')) {
        mQuery(el).closest('.tab-pane').find('.message_channel_properties_' + channel).removeClass('hide')
    } else {
        mQuery(el).closest('.tab-pane').find('.message_channel_properties_' + channel).addClass('hide');
    }
};

Mautic.cancelQueuedMessageEvent = function (channelId) {
    Mautic.ajaxActionRequest('channel:cancelQueuedMessageEvent',
        {
            channelId: channelId
        }, function (response) {
            if (response.success) {
                mQuery('#queued-message-'+channelId).addClass('disabled');
                mQuery('#queued-status-'+channelId).html(Mautic.translate('mautic.message.queue.status.cancelled'));
            }
        }, false
    );
};

Mautic.setMarketingMessageSendToDncStatus = function (messageId) {
    Mautic.setSendToDncStatus(
        messageId,
        'marketing_message_send_to_dnc_status',
        'channel:getMarketingMessageSendToDncStatus'
    )
};

Mautic.setMarketingMessageEmailChannelSendToDncStatus = function (emailId) {
    Mautic.setSendToDncStatus(
        emailId,
        'marketing_message_email_channel_send_to_dnc_status',
        'email:getEmailSendToDncStatus'
    )
};

Mautic.setSendToDncStatus = function (id, selector, action) {
    const statusElement = mQuery('#'+selector);
    if (id && statusElement.length > 0) {
        Mautic.ajaxActionRequest(action, {id: id}, function(response) {
            if (typeof response.sendToDncStatus != "undefined") {
                statusElement.removeClass('hide')
                statusElement.find('span.dnc-status-text')
                    .removeClass('label-danger label-primary')
                    .addClass(response.sendToDncStatus ? 'label-danger' : 'label-primary')
                    .text(response.sendToDncText);
            } else {
                statusElement.addClass('hide');
            }
        }, false, false, "GET");
    } else {
        statusElement.addClass('hide');
    }
}
