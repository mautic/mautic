mQuery(document).ready( function() {
    CKEDITOR.disableAutoInline = true;

    mQuery("div[contenteditable='true']").each(function (index) {
        var content_id = mQuery(this).attr('id');
        var that       = this;

        var editorEvents = Mautic.getGlobalEditorEvents();

        // Remove inserted <p /> tag if empty to allow the CSS3 placeholder to display
        editorEvents['blur'] = function( event ) {
            mQuery('.token-suggestions').remove();

            var data = event.editor.getData();
            if (!data) {
                mQuery(that).html('');
            }
        };
        editorEvents['instanceReady'] = function( event ) {
            var data = event.editor.getData();
            if (!data) {
                mQuery(that).html('');
            }
        };

        CKEDITOR.inline(content_id, {
            extraPlugins: 'tokens,sourcedialog',
            toolbar: 'advanced',
            // Inline mode seems to ignore this but leaving anyway
            allowedContent: true,
            // Allow any attributes and prevent conversion of height/width attributes to styles
            on: editorEvents
        });

        mQuery(this).data('token-callback', 'email:getBuilderTokens');
        mQuery(this).data('token-activator', '{');
    });
});