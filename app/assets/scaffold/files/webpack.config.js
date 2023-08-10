/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

'use strict';

/* eslint-env node */
const path = require( 'path' );
const { loaders } = require( '@ckeditor/ckeditor5-dev-utils' );
const { CKEditorTranslationsPlugin } = require( '@ckeditor/ckeditor5-dev-translations' );

module.exports = {
	devtool: 'source-map',
	performance: { hints: false },

	entry: path.resolve( __dirname, 'media/js/ckeditor/src', 'ckeditor.ts' ),

	output: {
		// The name under which the editor will be exported.
		library: 'ClassicEditor',

		path: path.resolve( __dirname, 'media/js/ckeditor/build' ),
		filename: 'ckeditor.js',
		libraryTarget: 'umd',
		libraryExport: 'default'
	},

	plugins: [
		new CKEditorTranslationsPlugin( {
			language: 'en',
			additionalLanguages: 'all'
		} )
	],

	module: {
		rules: [
			loaders.getIconsLoader( { matchExtensionOnly: true } ),
			loaders.getStylesLoader( {
				themePath: require.resolve( '@ckeditor/ckeditor5-theme-lark' ),
				minify: true
			} ),
			loaders.getTypeScriptLoader()
		]
	},

	resolve: {
		extensions: [ '.ts', '.js', '.json' ]
	}
};
