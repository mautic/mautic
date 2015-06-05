/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
    // Define changes to default configuration here. For example:
    // config.language = 'fr';
    // config.uiColor = '#AADC6E';

    config.skin = 'bootstrapck';
    config.extraPlugins  = 'sourcedialog';
    config.removePlugins = 'flash,forms,iframe';

    config.enterMode = CKEDITOR.ENTER_P;
    config.filebrowserImageBrowseUrl = mauticBasePath + '/' + mauticAssetPrefix + 'app/bundles/CoreBundle/Assets/js/libraries/ckeditor/filemanager/index.html?type=Images';

    config.toolbar =
        [
            { name: 'basicstyles', items : [ 'Bold','Italic' ] },
            { name: 'paragraph', items : [ 'NumberedList','BulletedList' ] },
            { name: 'clipboard', items : [ 'Cut', 'Copy', 'Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
            { name: 'tools', items : [ 'Sourcedialog' ] }
        ];

    config.toolbar_basic =
        [
            { name: 'basicstyles', items : [ 'Bold','Italic' ] },
            { name: 'paragraph', items : [ 'NumberedList','BulletedList' ] },
            { name: 'clipboard', items : [ 'Cut', 'Copy', 'Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
            { name: 'links', items : [ 'Link','Unlink','Anchor' ] },
            { name: 'tools', items : [ 'Sourcedialog' ] }

        ];

    config.toolbar_advanced_2rows =
        [
            { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat' ] },
            { name: 'clipboard', items : [ 'Cut', 'Copy', 'Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
            { name: 'insert', items : [ 'Image','Table' ] },
            { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ], items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl' ] },
            { name: 'links', items : [ 'Link','Unlink','Anchor' ] },
            '/',
            { name: 'styles', items : [ 'Styles','Format','Font','FontSize' ] },
            { name: 'colors', items : [ 'TextColor','BGColor' ] },
            { name: 'tools', items : [ 'Sourcedialog' ] }
        ];

    config.toolbar_advanced =
        [
            { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat' ] },
            { name: 'clipboard', items : [ 'Cut', 'Copy', 'Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
            { name: 'insert', items : [ 'Image','Table' ] },
            '/',
            { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ], items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl' ] },
            { name: 'links', items : [ 'Link','Unlink','Anchor' ] },
            { name: 'tools', items : [ 'Sourcedialog' ] },
            '/',
            { name: 'styles', items : [ 'Styles','Format','Font','FontSize' ] },
            { name: 'colors', items : [ 'TextColor','BGColor' ] }
        ];

    config.toolbar_fullpage =
        [
            { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat' ] },
            { name: 'clipboard', items : [ 'Cut', 'Copy', 'Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
            { name: 'insert', items : [ 'Image','Table' ] },
            { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ], items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl' ] },
            { name: 'links', items : [ 'Link','Unlink','Anchor' ] },
            '/',
            { name: 'styles', items : [ 'Styles','Format','Font','FontSize' ] },
            { name: 'colors', items : [ 'TextColor','BGColor' ] },
            { name: 'tools', items : [ 'Sourcedialog', 'DocProps', 'Maximize' ] }
        ];

    config.toolbar_basic_fullpage =
        [
            { name: 'basicstyles', items : [ 'Bold','Italic' ] },
            { name: 'paragraph', items : [ 'NumberedList','BulletedList' ] },
            { name: 'clipboard', items : [ 'Cut', 'Copy', 'Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
            { name: 'links', items : [ 'Link','Unlink','Anchor' ] },
            { name: 'tools', items : [ 'Sourcedialog', 'DocProps', 'Maximize' ] }
        ];
};