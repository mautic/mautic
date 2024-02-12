/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

'use strict';

/* eslint-env node */
const path = require( 'path' );
const glob = require("glob")
const fs = require( 'fs' );
const { loaders } = require( '@ckeditor/ckeditor5-dev-utils' );
const { CKEditorTranslationsPlugin } = require( '@ckeditor/ckeditor5-dev-translations' );

// Find the webroot path if it is not the same folder as the current dir.
// This is the case for Composer based installations following the best practices.
let webroot = '';
if (!fs.existsSync('app/release_metadata.json')) {
	let files = glob.sync("**/app/release_metadata.json");
	webroot = path.dirname(path.dirname(files[0])) + '/';
}

module.exports = {
	devtool: 'source-map',
	performance: { hints: false },
	cache: {
		type: 'filesystem',
		cacheDirectory: path.resolve(__dirname, 'var/cache/js/webpack'),
	},

	entry: path.resolve( __dirname, webroot + 'app/assets/libraries/ckeditor/src', 'ckeditor.ts' ),

	output: {
		// The name under which the editor will be exported.
		library: 'ClassicEditor',

		path: path.resolve( __dirname, webroot + 'media/libraries/ckeditor' ),
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
	},
	optimization: {
		removeAvailableModules: false,
		removeEmptyChunks: false,
		splitChunks: false,
	}
};
