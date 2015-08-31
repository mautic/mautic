CKEDITOR.plugins.add( 'filemanager', {
    init: function( editor ) {
        var selectedMauticFileUrl;

        editor.addCommand('OpenFilemanager', { exec:
            function(e) {
                var num = CKEDITOR.tools.addFunction(
                    function(url) {
                        selectedMauticFileUrl = url;
                        editor.execCommand( 'OpenUrlCopyDialog');
                    }
                );

                e.popup(
                    e.config.filebrowserBrowseUrl + '?CKEditor=' + e.name + '&CKEditorFuncNum=' + num + '&langCode=' + e.langCode
                );
            }
        });

        editor.addCommand('OpenUrlCopyDialog', new CKEDITOR.dialogCommand( 'UrlCopyDialog' ));

        CKEDITOR.dialog.add( 'UrlCopyDialog', function( editor ) {
            return {
                title: 'Selected File',
                minWidth: 400,
                minHeight: 100,
                contents: [
                    {
                        id: 'copyUrl',
                        label: 'Copy Url',
                        elements: [
                            {
                                type: 'text',
                                id: 'url',
                                label: 'Copy the URL of the selected file:',
                                onShow: function() {
                                    this.getInputElement().setValue(selectedMauticFileUrl);
                                },
                                onLoad: function() {
                                    this.getInputElement().setAttribute( 'readOnly', true );
                                },
                                onClick: function() {
                                    var id = this.getInputElement().getId();
                                    document.getElementById(id).setSelectionRange(0, document.getElementById(id).value.length);
                                }
                            }
                        ]
                    }
                ],
                buttons: [
                    CKEDITOR.dialog.okButton
                ]
            };
        });

        editor.ui.addButton('Filemanager',
        {
            label: 'Filemanager',
            command: 'OpenFilemanager',
            icon: this.path + 'images/icon.png'
        });
    }
});