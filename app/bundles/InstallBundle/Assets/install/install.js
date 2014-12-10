var MauticLang = {};
var MauticInstaller = {

    showWaitMessage: function(event) {
        event.preventDefault();

        if (mQuery('#waitMessage').length) {
            mQuery('#stepNavigation').addClass('hide');
            mQuery('#waitMessage').removeClass('hide');
        }

        mQuery('.btn-next').prop('disabled', true);
        mQuery('.btn-next').html('<i class=\"fa fa-spin fa-spinner fa-fw\"></i>Please wait...');

        setTimeout(function () {
            mQuery('form').submit();
        }, 10);
    },

    toggleTransportDetails: function (mailer) {
        if (mailer == 'smtp') {
            mQuery('#smtpSettings').removeClass('hide');
            if (mQuery('#install_email_step_mailer_auth_mode').val()) {
                mQuery('#authDetails').removeClass('hide');
            } else {
                mQuery('#authDetails').addClass('hide');
            }
        } else {
            mQuery('#smtpSettings').addClass('hide');

            if (mailer == 'mail' || mailer == 'sendmail') {
                mQuery('#authDetails').addClass('hide');
            } else {
                mQuery('#authDetails').removeClass('hide');
            }
        }
    },

    toggleAuthDetails: function (auth) {
        if (!auth) {
            mQuery('#authDetails').addClass('hide');
        } else {
            mQuery('#authDetails').removeClass('hide');
        }
    },

    toggleBackupPrefix: function() {
        if (mQuery('#install_doctrine_step_backup_tables_0').prop('checked')) {
        mQuery('#backupPrefix').addClass('hide');
        } else {
        mQuery('#backupPrefix').removeClass('hide');
        }
    }
};