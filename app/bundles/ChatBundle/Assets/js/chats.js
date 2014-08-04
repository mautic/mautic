/* ChatBundle */

Mautic.activateChatListUpdate = function() {
    setInterval(function() {
        Mautic.updateChatList();
    }, 30000);
};

Mautic.updateChatList = function () {
    mQuery.ajax({
        type: "POST",
        url: mauticAjaxUrl + "?action=chat:updateList",
        dataType: "json",
        success: function (response) {
            mQuery('#ChatList').replaceWith(response.newContent);
        },
        error: function (request, textStatus, errorThrown) {
            alert(errorThrown);
        }
    });
};

Mautic.startChatWith = function (userId, fromDate) {
    if (typeof fromDate == 'undefined') {
        fromDate = '';
    }

    mQuery.ajax({
        type: "POST",
        url: mauticAjaxUrl + "?action=chat:startChat",
        data: 'user=' + userId + '&from=' + fromDate,
        dataType: "json",
        success: function (response) {
            if (response.success) {
                mQuery('#ChatWith').html(response.withName);
                mQuery('#LastSeen').html(response.lastSeen);
                mQuery('#ChatConversation').html(response.conversationHtml);
                mQuery('#ChatConversation').scrollTop(mQuery('#ChatConversation')[0].scrollHeight);

                Mautic.activateChatUpdater(response.withId);
                Mautic.activateChatInput(response.withId);
            }
        },
        error: function (request, textStatus, errorThrown) {
            alert(errorThrown);
        }
    });
};

Mautic.activateChatInput = function(userId) {
    //activate enter key
    mQuery('#ChatMessageInput').off('keydown.chat');
    mQuery('#ChatMessageInput').on('keydown.chat', function(e) {
        if (e.which == 10 || e.which == 13) {
            //submit the text
            Mautic.sendChatMessage(userId);
        }

        //remove new message marker
        if (mQuery('.chat-new-divider').length) {
            mQuery('.chat-new-divider').remove();
        }
    });

    mQuery('#ChatMessageInput').off('click.chat');
    mQuery('#ChatMessageInput').on('click.chat', function(e) {
        //remove new message marker
        if (mQuery('.chat-new-divider').length) {
            mQuery('.chat-new-divider').remove();
            Mautic.markMessagesRead(userId);
        }
    });
};

Mautic.getLastChatGroup = function() {
    var group = mQuery('#ChatConversation .chat-group').last().find('.chat-group-firstid');
    return group.length ? group.val() : '';
};

Mautic.markMessagesRead = function(userId) {
    var lastId  = mQuery('#ChatLastMessageId').val();
    mQuery.ajax({
        type: "POST",
        url: mauticAjaxUrl + "?action=chat:markRead",
        data: 'user=' + userId + '&lastId=' + lastId,
        dataType: "json"
    });
};

Mautic.activateChatUpdater = function(userId) {
    setInterval(function(){
        var lastId  = mQuery('#ChatLastMessageId').val();
        var groupId = Mautic.getLastChatGroup();
        mQuery.ajax({
            type: "POST",
            url: mauticAjaxUrl + "?action=chat:getMessages",
            data: 'user=' + userId + '&lastId=' + lastId + '&groupId=' + groupId,
            dataType: "json",
            success: function (response) {
                Mautic.updateChatConversation(response);
            },
            error: function (request, textStatus, errorThrown) {
                alert(errorThrown);
            }
        });
    }, 5000);
};

Mautic.sendChatMessage = function(toId) {
    var msgText = mQuery('#ChatMessageInput').val();
    mQuery('#ChatMessageInput').val('');
    var lastId  = mQuery('#ChatLastMessageId').val();
    var groupId = Mautic.getLastChatGroup();
    if (msgText) {
        var dataObj = {
            user: toId,
            msg: msgText,
            lastId: lastId,
            groupId: groupId
        };
        mQuery.ajax({
            type: "POST",
            url: mauticAjaxUrl + "?action=chat:sendMessage",
            data: dataObj,
            dataType: "json",
            success: function (response) {
                Mautic.updateChatConversation(response);
            },
            error: function (request, textStatus, errorThrown) {
                alert(errorThrown);
            }
        });
    }
};

Mautic.updateChatConversation = function(response)
{
    var dividerAppended = false;
    var contentUpdated  = false;
    if (response.appendToGroup) {
        if (!mQuery('.chat-new-divider').length && response.divider) {
            dividerAppended = true;
            mQuery('#ChatGroup' + response.groupId + ' .media-body').append(response.divider);
        }
        mQuery('#ChatGroup' + response.groupId + ' .media-body').append(response.appendToGroup);
        contentUpdated = true;
    }

    if (response.messages) {
        if (!mQuery('.chat-new-divider').length && response.divider && !dividerAppended) {
            mQuery('#ChatMessages').append(response.divider);
        }
        mQuery('#ChatMessages').append(response.messages);
        contentUpdated = true;
    }

    if (contentUpdated) {
        mQuery('#ChatConversation').scrollTop(mQuery('#ChatConversation')[0].scrollHeight);
    }

    if (response.latestId) {
        mQuery('#ChatLastMessageId').val(response.latestId);
    }
};