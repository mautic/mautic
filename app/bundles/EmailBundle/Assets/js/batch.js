//EmailBundle (Copied from app/bundles/LeadBundle/Assets/js/lead.js)
Mautic.emailBatchSubmit = function() {
    if (Mautic.batchActionPrecheck()) {
        if (mQuery('#email_batch_newCategory').val()) {
            const $emailBatchIds = mQuery('#email_batch_ids');
            if ($emailBatchIds.length) {
                $emailBatchIds.val(Mautic.getCheckedListIds(false, true));
            }

            return true;
        }

    }

    mQuery('#MauticSharedModal').modal('hide');

    return false;
};