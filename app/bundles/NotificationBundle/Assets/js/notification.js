/** NotificationBundle **/
Mautic.notificationOnLoad = function (container, response) {
    if (response && response.updateSelect) {
        //added notification through a popup
        var newOption = mQuery('<option />').val(response.notificationId);
        newOption.html(response.notificationName);

        var opener = window.opener;
        if(opener) {
            var el = '#' + response.updateSelect;
            var optgroup = el + " optgroup[label=" + response.notificationLang + "]";
            if (opener.mQuery(optgroup).length) {
                // update option when new option equal with option item in group.
                var firstOptionGroups = opener.mQuery(el + ' optgroup');
                var isUpdateOption = false;
                firstOptionGroups.each(function() {
                    var firstOptions = mQuery(this).children();
                    for (var i = 0; i < firstOptions.length; i++) {
                        if (firstOptions[i].value === response.notificationId.toString()) {
                            firstOptions[i].text = response.notificationName;
                            isUpdateOption = true;
                            break;
                        }
                    }
                });

                if (!isUpdateOption) {
                    //the optgroup exist so append to it
                    opener.mQuery(optgroup + " option:last").prev().before(newOption);
                }
            } else {
                //create the optgroup
                var newOptgroup = mQuery('<optgroup label="' + response.notificationLang + '" />');
                newOption.appendTo(newOptgroup);
                opener.mQuery(newOptgroup).appendTo(opener.mQuery(el));
            }

            var chooseOneOption = opener.mQuery(el + ' option:first');

            var optionGroups = opener.mQuery(el + ' optgroup');
            optionGroups.sort(function(a, b) {
                var aLabel = mQuery(a).attr('label');
                var bLabel = mQuery(b).attr('label');

                if (aLabel > bLabel) {
                    return 1;
                } else if (aLabel < bLabel) {
                    return -1;
                } else {
                    return 0;
                }
            });

            optionGroups.each(function() {
                var options = mQuery(this).children();
                options.sort(function(a, b) {
                    if (a.text > b.text) {
                        return 1;
                    } else if (a.text < b.text) {
                        return -1;
                    } else {
                        return 0;
                    }
                });
                mQuery(this).html(options);
            });

            if (opener.mQuery(el).prop('disabled')) {
                opener.mQuery(el).prop('disabled', false);
                chooseOneOption = mQuery('<option value="">' + mauticLang.chosenChooseOne + '</option>');
            }

            opener.mQuery(el).html(chooseOneOption);
            optionGroups.appendTo(opener.mQuery(el));

            newOption.prop('selected', true);

            opener.mQuery(el).trigger("chosen:updated");
        }

        window.close();
    } else if (mQuery(container + ' #list-search').length) {
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

Mautic.loadNewNotificationWindow = function(options) {
    if (options.windowUrl) {
        Mautic.startModalLoadingBar();

        setTimeout(function() {
            var generator = window.open(options.windowUrl, 'newnotificationwindow', 'height=600,width=1100');

            if (!generator || generator.closed || typeof generator.closed == 'undefined') {
                alert(response.popupBlockerMessage);
            } else {
                generator.onload = function () {
                    Mautic.stopModalLoadingBar();
                    Mautic.stopIconSpinPostEvent();
                };
            }
        }, 100);
    }
};