//EmailBundle (Copied from app/bundles/LeadBundle/Assets/js/lead.js)
Mautic.emailBatchSubmit = function() {
    if (Mautic.batchActionPrecheck()) {
        if (mQuery('#email_batch_newCategory').val()) {
            if (mQuery('#email_batch_ids').length) {
                mQuery('#email_batch_ids').val(Mautic.getCheckedListIds(false, true));
            }

            return true;
        }

    }

    mQuery('#MauticSharedModal').modal('hide');

    return false;
};