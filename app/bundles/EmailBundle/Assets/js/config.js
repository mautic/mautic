Mautic.testMonitoredEmailServerConnection = function(mailbox) {
    var data = {
        host:       mQuery('#config_emailconfig_monitored_email_' + mailbox + '_host').val(),
        port:       mQuery('#config_emailconfig_monitored_email_' + mailbox + '_port').val(),
        encryption: mQuery('#config_emailconfig_monitored_email_' + mailbox + '_encryption').val(),
        user:       mQuery('#config_emailconfig_monitored_email_' + mailbox + '_user').val(),
        password:   mQuery('#config_emailconfig_monitored_email_' + mailbox + '_password').val(),
        mailbox:    mailbox
    };

    var abortCall = false;
    if (!data.host) {
        mQuery('#config_emailconfig_monitored_email_' + mailbox + '_host').parent().addClass('has-error');
        abortCall = true;
    } else {
        mQuery('#config_emailconfig_monitored_email_' + mailbox + '_host').parent().removeClass('has-error');
    }

    if (!data.port) {
        mQuery('#config_emailconfig_monitored_email_' + mailbox + '_port').parent().addClass('has-error');
        abortCall = true;
    } else {
        mQuery('#config_emailconfig_monitored_email_' + mailbox + '_port').parent().removeClass('has-error');
    }

    if (abortCall) {
        return;
    }

    mQuery('#' + mailbox + 'TestButtonContainer .fa-spinner').removeClass('hide');

    Mautic.ajaxActionRequest('email:testMonitoredEmailServerConnection', data, function(response) {
        var theClass = (response.success) ? 'has-success' : 'has-error';
        var theMessage = response.message;
        mQuery('#' + mailbox + 'TestButtonContainer').removeClass('has-success has-error').addClass(theClass);
        mQuery('#' + mailbox + 'TestButtonContainer .help-block').html(theMessage);
        mQuery('#' + mailbox + 'TestButtonContainer .fa-spinner').addClass('hide');

        if (response.folders) {
            if (mailbox == 'general') {
                // Update applicable folders
                mQuery('select[data-imap-folders]').each(
                    function(index) {
                        var thisMailbox = mQuery(this).data('imap-folders');
                        if (mQuery('#config_emailconfig_monitored_email_' + thisMailbox + '_override_settings_0').is(':checked')) {
                            var folder = '#config_emailconfig_monitored_email_' + thisMailbox + '_folder';
                            var curVal = mQuery(folder).val();
                            mQuery(folder).html(response.folders);
                            mQuery(folder).val(curVal);
                            mQuery(folder).trigger('chosen:updated');
                        }
                    }
                );
            } else {
                // Find and update folder lists
                var folder = '#config_emailconfig_monitored_email_' + mailbox + '_folder';
                var curVal = mQuery(folder).val();
                mQuery(folder).html(response.folders);
                mQuery(folder).val(curVal);
                mQuery(folder).trigger('chosen:updated');
            }
        }
    });
};

Mautic.testEmailServerConnection = function() {
    var data = {
        amazon_region:       mQuery('#config_emailconfig_mailer_amazon_region').val(),
        amazon_other_region: mQuery('#config_emailconfig_mailer_amazon_other_region').val(),
        host:                mQuery('#config_emailconfig_mailer_host').val(),
        api_key:             mQuery('#config_emailconfig_mailer_api_key').val(),
        authMode:            mQuery('#config_emailconfig_mailer_auth_mode').val(),
        encryption:          mQuery('#config_emailconfig_mailer_encryption').val(),
        from_email:          mQuery('#config_emailconfig_mailer_from_email').val(),
        from_name:           mQuery('#config_emailconfig_mailer_from_name').val(),
        password:            mQuery('#config_emailconfig_mailer_password').val(),
        port:                mQuery('#config_emailconfig_mailer_port').val(),
        transport:           mQuery('#config_emailconfig_mailer_transport').val(),
        user:                mQuery('#config_emailconfig_mailer_user').val()
    };

    mQuery('#mailerTestButtonContainer .fa-spinner').removeClass('hide');

    Mautic.ajaxActionRequest('email:testEmailServerConnection', data, function(response) {
        var theClass = (response.success) ? 'has-success' : 'has-error';
        var theMessage = response.message;
        mQuery('#mailerTestButtonContainer').removeClass('has-success has-error').addClass(theClass);
        mQuery('#mailerTestButtonContainer .help-block .status-msg').html(theMessage);
        mQuery('#mailerTestButtonContainer .fa-spinner').addClass('hide');
    });
};

Mautic.sendTestEmail = function() {
    mQuery('#mailerTestButtonContainer .fa-spinner').removeClass('hide');

    Mautic.ajaxActionRequest('email:sendTestEmail', {}, function(response) {
        var theClass = (response.success) ? 'has-success' : 'has-error';
        var theMessage = response.message;
        mQuery('#mailerTestButtonContainer').removeClass('has-success has-error').addClass(theClass);
        mQuery('#mailerTestButtonContainer .help-block .status-msg').html(theMessage);
        mQuery('#mailerTestButtonContainer .fa-spinner').addClass('hide');
    });
};

Mautic.disableSendTestEmailButton = function() {
    mQuery('#mailerTestButtonContainer .help-block .status-msg').html('');
    mQuery('#mailerTestButtonContainer .help-block .save-config-msg').removeClass('hide');
    mQuery('#config_emailconfig_mailer_test_send_button').prop('disabled', true).addClass('disabled');

};
