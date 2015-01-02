/* ChatBundle */

Mautic.chatOnLoad = function() {
    //make visible users/channels sortable

    mQuery('#ChatUsers').sortable({
        items: 'li.sortable',
        start: function() {
            Mautic.chatPauseUpdateChatList = true;
        },
        stop: function (i) {
            Mautic.reorderVisibleChatList(mQuery('#ChatUsers').sortable('serialize'), 'users');
        }
    });

    mQuery('#ChatChannels').sortable({
        items: 'li.sortable',
        start: function() {
            Mautic.chatPauseUpdateChatList = true;
        },
        stop: function (i) {
            Mautic.reorderVisibleChatList(mQuery('#ChatChannels').sortable('serialize'), 'channels');
        }
    });
},

Mautic.reorderVisibleChatList = function(order, chatType)
{
    if (typeof Mautic.chatReorderInProgress != 'undefined') {
        setTimeout(function() {
            Mautic.reorderVisibleChatList(order, chatType);
        }, 1000);
    } else {
        Mautic.chatReorderInProgress = true;
        mQuery.ajax({
            type: "POST",
            url: mauticAjaxUrl + "?action=addon:mauticChat:reorderVisibleChatList",
            data: order + '&chatType=' + chatType,
            complete: function () {
                delete Mautic.chatPauseUpdateChatList;
                delete Mautic.chatReorderInProgress;
            }
        });
    }
}

Mautic.activateChatListUpdate = function() {
    Mautic.setModeratedInterval('chatListUpdaterInterval', 'updateChatList', 5000);

    Mautic.chatOnLoad();
};

Mautic.updateChatList = function (killTimer) {
    if (!mQuery('#ChatUsers').length) {
        Mautic.clearModeratedInterval('chatListUpdaterInterval');
    } else {
        if (typeof Mautic.chatPauseUpdateChatList != 'undefined') {
            //sorting pending so wait till next round to update
            Mautic.moderatedIntervalCallbackIsComplete('chatListUpdaterInterval');
        } else {
            mQuery.ajax({
                type: "POST",
                url: mauticAjaxUrl + "?action=addon:mauticChat:updateList&tmpl=list",
                dataType: "json",
                success: function (response) {
                    if (response.canvasContent) {
                        if (typeof Mautic.chatPauseUpdateChatList == 'undefined') {

                            mQuery('#ChatCanvasContent').html(response.canvasContent);

                            response.target = '#ChatCanvasContent';
                            Mautic.processPageContent(response);

                            Mautic.chatOnLoad();
                        }

                        if (killTimer) {
                            Mautic.clearModeratedInterval('chatListUpdaterInterval');
                        } else {
                            Mautic.moderatedIntervalCallbackIsComplete('chatListUpdaterInterval');
                        }
                    }
                },
                error: function (request, textStatus, errorThrown) {
                    Mautic.processAjaxError(request, textStatus, errorThrown);
                }
            });
        }
    }
};

Mautic.startUserChat = function (userId, fromDate) {
    if (typeof fromDate == 'undefined') {
        fromDate = '';
    }

    Mautic.startCanvasLoadingBar();

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
            }
            Mautic.stopCanvasLoadingBar();
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

    Mautic.startCanvasLoadingBar();
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
            }
            Mautic.stopCanvasLoadingBar();
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
    Mautic.chatPauseUpdateChatList = true;
    mQuery.ajax({
        type: "POST",
        url: mauticAjaxUrl + "?action=addon:mauticChat:markRead",
        data: 'chatId=' + itemId + '&chatType=' + chatType + '&lastId=' + lastId,
        dataType: "json",
        complete: function() {
            delete Mautic.chatPauseUpdateChatList;
        }
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
        Mautic.chatPauseUpdateChatList = true;

        Mautic.startCanvasLoadingBar();

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
                Mautic.stopCanvasLoadingBar();
            },
            error: function (request, textStatus, errorThrown) {
                Mautic.processAjaxError(request, textStatus, errorThrown);
            },
            complete: function() {
                delete Mautic.chatPauseUpdateChatList;
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
        //activate links, etc
        response.target = "#OffCanvasRight";
        Mautic.processPageContent(response);

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
    Mautic.startCanvasLoadingBar();
    mQuery.ajax({
        type: "POST",
        url: mauticAjaxUrl + "?action=addon:mauticChat:addChannel",
        dataType: "json",
        success: function (response) {
            Mautic.stopCanvasLoadingBar();
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

Mautic.toggleChatSetting = function(chatType, setting, id, isChecked) {
    if (typeof Mautic.chatSettingUpdateInProgress != 'undefined') {
        //set a timeout
        setTimeout(function() { Mautic.toggleChatSetting(chatType, setting, id, isChecked); }, 1000);
        return;
    }
    Mautic.startModalLoadingBar('#MauticSharedModal');

    Mautic.chatSettingUpdateInProgress = true;
    mQuery.ajax({
        type: "POST",
        url: mauticAjaxUrl + "?action=addon:mauticChat:toggleChatSetting",
        data: "chatType=" + chatType + "&id=" + id + "&setting=" + setting + "&enabled=" + isChecked,
        dataType: "json",
        success: function (response) {
            Mautic.updateChatList();

            mQuery.each(response.settings,  function( key, value ) {
                if (key !== setting) {
                    mQuery('#' + chatType + '_' + key + id).prop('checked', value);
                }
            });
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        },
        complete: function() {
            delete Mautic.chatSettingUpdateInProgress;
            Mautic.stopModalLoadingBar('#MauticSharedModal');
        }
    });
};

Mautic.filterByChatAttribute = function(chatType, attr, isChecked, baseUrl)
{
    if (typeof Mautic.chatFilterUpdateInProgress != 'undefined') {
        //set a timeout
        setTimeout(function() { Mautic.filterByChatAttribute(chatType, attr, isChecked, baseUrl); }, 1000);
        return;
    }

    Mautic.startModalLoadingBar('#MauticSharedModal');

    Mautic.chatFilterUpdateInProgress = true;

    if (typeof baseUrl == 'undefined') {
        baseUrl = window.location.pathname;
    }

    var route = baseUrl + "?chatType=" + chatType + "&filter=" + attr + "&enabled=" + isChecked;
    var target = (chatType == 'channels') ? '.chat-channel-list' : '.chat-user-list'

    Mautic.loadContent(route, '', 'POST', target, false, 'clearChatFilterUpdateInProgress');
};

Mautic.clearChatFilterUpdateInProgress = function()
{
    delete Mautic.chatFilterUpdateInProgress;
    Mautic.stopModalLoadingBar('#MauticSharedModal');
};

Mautic.setChatOnlineStatus = function(onlineStatus)
{
    mQuery.ajax({
        type: "POST",
        url: mauticAjaxUrl + "?action=addon:mauticChat:setChatOnlineStatus",
        data: 'status=' + onlineStatus
    });
};