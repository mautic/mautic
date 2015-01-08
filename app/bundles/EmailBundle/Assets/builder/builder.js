mQuery(document).ready( function() {
    CKEDITOR.disableAutoInline = true;
    mQuery("div[contenteditable='true']").each(function (index) {
        var content_id = mQuery(this).attr('id');
        CKEDITOR.inline(content_id, {
            toolbar: 'advanced'
        });
    });
});