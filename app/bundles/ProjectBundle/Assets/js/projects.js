
/**
 * Handle project batch form submission
 */
Mautic.projectBatchSubmit = function () {
    if (Mautic.batchActionPrecheck()) {
        if (mQuery('#project_batch_add_to').val() || mQuery('#project_batch_remove_from').val()) {
            var ids = Mautic.getCheckedListIds(false, true);

            if (mQuery('#project_batch_ids').length) {
                mQuery('#project_batch_ids').val(ids);
            }

            return true;
        }
    }

    mQuery('#MauticSharedModal').modal('hide');
    return false;
};

/**
 * Handle project batch form submission callback
 */
Mautic.projectBatchSubmitCallback = function (response) {
    mQuery('#MauticSharedModal').modal('hide');
};
