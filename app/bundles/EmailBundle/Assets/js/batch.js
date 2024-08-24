//EmailBundle (Copied from app/bundles/LeadBundle/Assets/js/lead.js)
Mautic.emailBatchSubmit = function() {
    if (Mautic.batchActionPrecheck("")) {
        if (mQuery('#email_batch_newCategory').val()) {
            const $emailBatchIds = mQuery('#email_batch_ids');
            if ($emailBatchIds.length) {
                $emailBatchIds.val(Mautic.getCheckedListIds(false, true));
            }

            return true;
        }

    }

    return false;
};

function setCategory(id, newCategory) {
    document.evaluate('//td[3]/div/text()', document.querySelector(".list-checkbox[value='" + id + "']").parentElement.parentElement.parentElement.parentElement.querySelector('.d-flex.ai-center.gap-xs')).iterateNext().textContent = newCategory;
}

Mautic.emailBatchSubmitCallback = function( response ) {
    console.log('Response: ' + JSON.stringify(response));
    mQuery('#MauticSharedModal').modal('hide');
    response.affected.forEach( function(id){
        setCategory(id, response.newCategory);
    });
}