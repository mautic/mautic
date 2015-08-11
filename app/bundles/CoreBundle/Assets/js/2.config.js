/**
 * Tests an email server connection
 */
Mautic.testEmailServerConnection = function(sendEmail) {
    var data = {
        transport:  mQuery('#config_coreconfig_mailer_transport').val(),
        host:       mQuery('#config_coreconfig_mailer_host').val(),
        port:       mQuery('#config_coreconfig_mailer_port').val(),
        encryption: mQuery('#config_coreconfig_mailer_encryption').val(),
        authMode:   mQuery('#config_coreconfig_mailer_auth_mode').val(),
        user:       mQuery('#config_coreconfig_mailer_user').val(),
        password:   mQuery('#config_coreconfig_mailer_password').val(),
        from_name:  mQuery('#config_coreconfig_mailer_from_name').val(),
        from_email: mQuery('#config_coreconfig_mailer_from_email').val(),
        send_test:  (typeof sendEmail !== 'undefined') ? sendEmail : false
    };

    mQuery('#mailerTestButtonContainer .fa-spinner').removeClass('hide');

    Mautic.ajaxActionRequest('testEmailServerConnection', data, function(response) {
        var theClass = (response.success) ? 'has-success' : 'has-error';
        var theMessage = response.message;
        mQuery('#mailerTestButtonContainer').removeClass('has-success has-error').addClass(theClass);
        mQuery('#mailerTestButtonContainer .help-block').html(theMessage);
        mQuery('#mailerTestButtonContainer .fa-spinner').addClass('hide');
    });
};

Mautic.testMonitoredEmailServerConnection = function() {
    var data = {
        host:       mQuery('#config_coreconfig_bounce_host').val(),
        port:       mQuery('#config_coreconfig_bounce_port').val(),
        ssl:        (mQuery('#config_coreconfig_bounce_ssl_1').prop('checked') ? 1 : 0),
        user:       mQuery('#config_coreconfig_bounce_user').val(),
        password:   mQuery('#config_coreconfig_bounce_password').val(),
        path:       mQuery('#config_coreconfig_bounce_path').val()
    };

    mQuery('#bounceTestButtonContainer .fa-spinner').removeClass('hide');

    Mautic.ajaxActionRequest('testMonitoredEmailServerConnection', data, function(response) {
        var theClass = (response.success) ? 'has-success' : 'has-error';
        var theMessage = response.message;
        mQuery('#monitoredEmailTestButtonContainer').removeClass('has-success has-error').addClass(theClass);
        mQuery('#monitoredEmailTestButtonContainer .help-block').html(theMessage);
        mQuery('#monitoredEmailTestButtonContainer .fa-spinner').addClass('hide');
    });
};
