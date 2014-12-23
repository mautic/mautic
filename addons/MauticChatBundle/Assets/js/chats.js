/* ChatBundle */

Mautic.activateChatListUpdate = function() {
    Mautic.setModeratedInterval('chatListUpdaterInterval', 'updateChatList', 5000);
};

Mautic.updateChatList = function (killTimer) {
    if (!mQuery('#ChatUsers').length) {
        Mautic.clearModeratedInterval('chatListUpdaterInterval');
    } else {
        mQuery.ajax({
            type: "POST",
            url: mauticAjaxUrl + "?action=addon:mauticChat:updateList",
            dataType: "json",
            success: function (response) {
                mQuery('#OffCanvasMainContent').html(response.newContent);

                response.target = '#OffCanvasMainContent';
                Mautic.processPageContent(response);

                if (killTimer) {
                    Mautic.clearModeratedInterval('chatListUpdaterInterval');
                } else {
                    Mautic.moderatedIntervalCallbackIsComplete('chatListUpdaterInterval');
                }
            },
            error: function (request, textStatus, errorThrown) {
                Mautic.processAjaxError(request, textStatus, errorThrown);
                Mautic.clearModeratedInterval('chatListUpdaterInterval');
            }
        });
    }
};

Mautic.startUserChat = function (userId, fromDate) {
    if (typeof fromDate == 'undefined') {
        fromDate = '';
    }
    mQuery.ajax({
        type: "POST",
        url: mauticAjaxUrl + "?action=addon:mauticChat:startUserChat",
        data: 'chatId=' + userId + '&from=' + fromDate,
        dataType: "json",
        success: function (response) {
            if (response.success) {
                mQuery('#OffCanvasRightHeader h4').html(response.withName);

                Mautic.updateChatConversation(response);

                Mautic.activateChatUpdater(response.withId, 'user');
                Mautic.activateChatInput(response.withId, 'user');

                //activate links, etc
                response.target = ".offcanvas-right";
                Mautic.processPageContent(response);
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        }
    });
};

Mautic.startChannelChat = function (channelId, fromDate) {
    if (typeof fromDate == 'undefined') {
        fromDate = '';
    }
    mQuery.ajax({
        type: "POST",
        url: mauticAjaxUrl + "?action=addon:mauticChat:startChannelChat",
        data: 'chatId=' + channelId + '&from=' + fromDate,
        dataType: "json",
        success: function (response) {
            if (response.success) {
                mQuery('#OffCanvasRightHeader h4').html(response.channelName);

                Mautic.updateChatConversation(response);
                Mautic.activateChatUpdater(response.withId, 'channel');
                Mautic.activateChatInput(response.withId, 'channel');

                //activate links, etc
                response.target = "#OffCanvasRightContent";
                Mautic.processPageContent(response);
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        }
    });
};

Mautic.activateChatInput = function(itemId, chatType) {
    //activate enter key
    mQuery('#ChatMessageInput').off('keydown.chat');
    mQuery('#ChatMessageInput').on('keydown.chat', function(e) {
        if (e.which == 10 || e.which == 13) {
            //submit the text
            Mautic.sendChatMessage(itemId, chatType);
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
            Mautic.markMessagesRead(itemId, chatType);
        }
    });
};

Mautic.getLastChatGroup = function() {
    var group = mQuery('#ChatMessages .chat-group').last().find('.chat-group-firstid');
    return group.length ? group.val() : '';
};

Mautic.markMessagesRead = function(itemId, chatType) {
    var lastId  = mQuery('#ChatLastMessageId').val();
    mQuery.ajax({
        type: "POST",
        url: mauticAjaxUrl + "?action=addon:mauticChat:markRead",
        data: 'chatId=' + itemId + '&chatType=' + chatType + '&lastId=' + lastId,
        dataType: "json"
    });
};

Mautic.activateChatUpdater = function(itemId, chatType) {
    Mautic.setModeratedInterval('chatUpdaterInterval', 'chatUpdater', 5000, [itemId, chatType]);
};

Mautic.chatUpdater = function(itemId, chatType) {
    var lastId  = mQuery('#ChatLastMessageId').val();
    var groupId = Mautic.getLastChatGroup();

    //only update if not in a form or single chat
    if (mQuery('#ChatUsers').length) {
        mQuery.ajax({
            type: "POST",
            url: mauticAjaxUrl + "?action=addon:mauticChat:getMessages",
            data: 'chatId=' + itemId + '&chatType=' + chatType + '&lastId=' + lastId + '&groupId=' + groupId,
            dataType: "json",
            success: function (response) {
                Mautic.updateChatConversation(response, chatType);
                Mautic.moderatedIntervalCallbackIsComplete('chatUpdaterInterval');

            },
            error: function (request, textStatus, errorThrown) {
                Mautic.processAjaxError(request, textStatus, errorThrown);
                Mautic.moderatedIntervalCallbackIsComplete('chatUpdaterInterval');
            }
        });
    } else {
        //clear the interval
        Mautic.clearModeratedInterval('chatUpdaterInterval');
    }
}

Mautic.sendChatMessage = function(toId, chatType) {
    var msgText = mQuery('#ChatMessageInput').val();
    mQuery('#ChatMessageInput').val('');
    var lastId  = mQuery('#ChatLastMessageId').val();
    var groupId = Mautic.getLastChatGroup();

    if (msgText) {
        var dataObj = {
            chatId: toId,
            msg: msgText,
            lastId: lastId,
            groupId: groupId,
            chatType: chatType
        };
        mQuery.ajax({
            type: "POST",
            url: mauticAjaxUrl + "?action=addon:mauticChat:sendMessage",
            data: dataObj,
            dataType: "json",
            success: function (response) {
                Mautic.updateChatConversation(response, chatType);
            },
            error: function (request, textStatus, errorThrown) {
                Mautic.processAjaxError(request, textStatus, errorThrown);
            }
        });
    }
};

Mautic.updateChatConversation = function(response, chatType) {
    var dividerAppended = false;
    var contentUpdated  = false;

    if (response.conversationHtml) {
        mQuery('#OffCanvasRightContent').html(response.conversationHtml);
        contentUpdated = true;
    }

    if (response.firstId && mQuery('#ChatMessage' + response.firstId).length) {
        return;
    }

    var useId = (chatType == 'user') ? 'ChatWithUserId' : 'ChatChannelId';

    if (mQuery('#'+useId).length && mQuery('#'+useId).val() == response.withId) {
        if (!mQuery('.chat-new-divider').length && response.divider) {
            if (response.lastReadId && response.lastReadId != response.latestId && mQuery('#ChatMessage' + response.lastReadId).length) {
                dividerAppended = true;
                mQuery(response.divider).insertAfter('#ChatMessage' + response.lastReadId);
            }
        }

        if (response.appendToGroup) {
            if (!dividerAppended && !mQuery('.chat-new-divider').length && response.divider) {
                dividerAppended = true;
                mQuery('#ChatGroup' + response.groupId + ' .media-body').append(response.divider);
            }
            mQuery('#ChatGroup' + response.groupId + ' .media-body').append(response.appendToGroup);
            contentUpdated = true;
        }

        if (response.messages) {
            if (!dividerAppended && !mQuery('.chat-new-divider').length && response.divider) {
                mQuery('#ChatMessages').append(response.divider);
            }
            mQuery('#ChatMessages').append(response.messages);
            contentUpdated = true;
        }
    }

    if (contentUpdated) {
        //Scroll to bottom of chat (latest messages)
        Mautic.scrollToChatBottom();
    }

    if (response.latestId) {
        var currentLastId = mQuery('#ChatLastMessageId').val();
        if (response.latestId > currentLastId) {
            //only update the latest ID if the given is higher than what's set incase JS gets a head of itself
            mQuery('#ChatLastMessageId').val(response.latestId);
        }
    }
};

Mautic.addChatChannel = function() {
    mQuery.ajax({
        type: "POST",
        url: mauticAjaxUrl + "?action=addon:mauticChat:addChannel",
        dataType: "json",
        success: function (response) {

        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        }
    });
};

Mautic.chatChannelOnLoad = function(container, response) {
    if (response.chatHtml) {
        mQuery('#OffCanvasMainContent').html(response.chatHtml);
    }
};

Mautic.scrollToChatBottom = function() {
    mQuery("#OffCanvasRightContent").animate({ scrollTop: mQuery("#OffCanvasRightContent")[0].scrollHeight}, 1000);
};