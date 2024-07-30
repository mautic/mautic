var MauticLang = {};
var MauticInstaller = {
    showWaitMessage: function(event) {
        event.preventDefault();

        if (mQuery('#waitMessage').length) {
            mQuery('#stepNavigation').addClass('hide');
            mQuery('#waitMessage').removeClass('hide');
        }

        mQuery('.btn-next').prop('disabled', true);
        mQuery('.btn-next').html('<i class=\"ri-loader-3-line ri-spin ri-fw\"></i>Please wait...');

        setTimeout(function () {
            mQuery('form').submit();
        }, 10);
    },

    toggleBackupPrefix: function() {
        if (mQuery('#install_doctrine_step_backup_tables_0').prop('checked')) {
        mQuery('#backupPrefix').addClass('hide');
        } else {
        mQuery('#backupPrefix').removeClass('hide');
        }
    }
};