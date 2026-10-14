'use strict';

const path = require('path');
const { loaders } = require('@ckeditor/ckeditor5-dev-utils');

const typeScriptLoader = loaders.getTypeScriptLoader();
typeScriptLoader.use[0].options.target = 'es2019';

module.exports = {
	mode: 'production',
	devtool: false,
	performance: { hints: false },

	entry: path.resolve(__dirname, 'app/bundles/CoreBundle/Assets/js/src/animated-checkbox.ts'),

	output: {
		path: path.resolve(__dirname, 'app/bundles/CoreBundle/Assets/js'),
		filename: '99.animated-checkbox.js',
	},

	module: {
		rules: [typeScriptLoader],
	},

	resolve: {
		extensions: ['.ts', '.js'],
	},
};
