/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';

	config.skin = 'bootstrapck';
    config.removePlugins = 'floating-tools, symbol, language';
    config.enterMode = CKEDITOR.ENTER_DIV;
    config.filebrowserImageBrowseUrl = mauticBasePath + '/assets/js/ckeditor/filemanager/index.html?type=Images';
    config.filebrowserImageUploadUrl = mauticBasePath + '/assets/js/ckeditor/filemanager/connectors/php/filemanager.php?command=QuickUpload&type;=Images';

	config.toolbar =
	[
		{ name: 'basicstyles', items : [ 'Bold','Italic' ] },
		{ name: 'paragraph', items : [ 'NumberedList','BulletedList' ] },
		{ name: 'clipboard', items : [ 'Cut', 'Copy', 'Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
		{ name: 'insert', items : [ 'Image','Table' ] }
	];

};