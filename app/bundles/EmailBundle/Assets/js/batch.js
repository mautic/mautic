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
    const tr = document.querySelector("#row_email_" + id);
    const div = tr.querySelector("div.d-flex.ai-center.gap-xs");
    const span = div.querySelector("span");

    div.textContent = newCategory.name;
    span.style = "background: #" + newCategory.color + ";"

    div.prepend(span);
}

Mautic.emailBatchSubmitCallback = function( response ) {
    mQuery('#MauticSharedModal').modal('hide');
    console.log("Received: " + JSON.stringify(response));
    response.affected.forEach( function(id){
        setCategory(id, response.newCategory);
    });
}