//EmailBundle (Copied from app/bundles/LeadBundle/Assets/js/lead.js)
Mautic.emailBatchSubmit = function() {
    if (Mautic.batchActionPrecheck()) {
        if (mQuery('#email_batch_remove').val() || mQuery('#email_batch_add').val() || mQuery('#email_batch_dnc_reason').length || mQuery('#email_batch_stage_addstage').length || mQuery('#email_batch_owner_addowner').length || mQuery('#contact_channels_ids').length || mQuery('#batch_tag_tags_add_tags').val() || mQuery('#batch_tag_tags_remove_tags').val()) {
            var ids = Mautic.getCheckedListIds(false, true);

            if (mQuery('#email_batch_ids').length) {
                mQuery('#email_batch_ids').val(ids);
            } else if (mQuery('#email_batch_dnc_reason').length) {
                mQuery('#email_batch_dnc_ids').val(ids);
            } else if (mQuery('#email_batch_stage_addstage').length) {
                mQuery('#email_batch_stage_ids').val(ids);
            } else if (mQuery('#email_batch_owner_addowner').length) {
                mQuery('#email_batch_owner_ids').val(ids);
            } else if (mQuery('#contact_channels_ids').length) {
                mQuery('#contact_channels_ids').val(ids);
            } else if (mQuery('#batch_tag_ids').length) {
                mQuery('#batch_tag_ids').val(ids);
            }

            return true;
        }

    }

    mQuery('#MauticSharedModal').modal('hide');

    return false;
};