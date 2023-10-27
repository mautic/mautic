/** SmsBundle **/
Mautic.smsOnLoad = function (container, response) {
    const smsMessage = mQuery('#sms_message');

    if (smsMessage.length) {
        Mautic.setSmsCharactersCount(smsMessage);
        smsMessage.on('input', () => {
            Mautic.setSmsCharactersCount(smsMessage);
        });
    }

    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'sms');
    }

    if (mQuery('table.sms-list').length) {
        var ids = [];
        mQuery('td.col-stats').each(function () {
            var id = mQuery(this).attr('data-stats');
            ids.push(id);
        });

        // Get all stats numbers in batches of 10
        while (ids.length > 0) {
            let batchIds = ids.splice(0, 10);
            Mautic.ajaxActionRequest(
                'sms:getSmsCountStats',
                {ids: batchIds},
                function (response) {
                    if (response.success && response.stats) {
                        for (var i = 0; i < response.stats.length; i++) {
                            var stat = response.stats[i];
                            if (mQuery('#pending-' + stat.id).length) {
                                if (stat.pending) {
                                    mQuery('#pending-' + stat.id + ' > a').html(stat.pending);
                                    mQuery('#pending-' + stat.id).removeClass('hide');
                                }
                            }
                        }
                    }
                },
                false,
                true
            );
        }
    }

    Mautic.initSmsAtWho();
};

Mautic.setSmsCharactersCount = function (smsMessage) {
    mQuery('#sms_nb_char').text((smsMessage.val().length))
};


Mautic.initSmsAtWho = function () {
    var smsMessage = mQuery('#sms_message, #send_sms_message');
    smsMessage.each(function () {
        var obj = mQuery(this);
        var callbackAttr = obj.attr('data-token-callback');
        if (typeof callbackAttr == 'undefined') {
            obj.attr('data-token-callback', 'sms:getBuilderTokens');
            obj.attr('data-token-activator', '{');
            obj.attr('data-token-visual', 'false');
            Mautic.initAtWho(obj, obj.attr('data-token-callback'));
        }
    })
}

Mautic.selectSmsType = function(smsType) {
    if (smsType == 'list') {
        mQuery('#leadList').removeClass('hide');
        mQuery('#publishStatus').addClass('hide');
        mQuery('.page-header h3').text(mauticLang.newListSms);
    } else {
        mQuery('#publishStatus').removeClass('hide');
        mQuery('#leadList').addClass('hide');
        mQuery('.page-header h3').text(mauticLang.newTemplateSms);
    }

    mQuery('#sms_smsType').val(smsType);

    mQuery('body').removeClass('noscroll');

    mQuery('.sms-type-modal').remove();
    mQuery('.sms-type-modal-backdrop').remove();
};

Mautic.standardSmsUrl = function(options) {
    if (!options) {
        return;
    }

    var url = options.windowUrl;
    if (url) {
        var editEmailKey = '/sms/edit/smsId';
        if (url.indexOf(editEmailKey) > -1) {
            options.windowUrl = url.replace('smsId', mQuery('#campaignevent_properties_sms').val());
        }
    }

    return options;
};

Mautic.disabledSmsAction = function(opener) {
    if (typeof opener == 'undefined') {
        opener = window;
    }

    var sms = opener.mQuery('#campaignevent_properties_sms').val();

    var disabled = sms === '' || sms === null;

    opener.mQuery('#campaignevent_properties_editSmsButton').prop('disabled', disabled);
};