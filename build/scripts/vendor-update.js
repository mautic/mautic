/**
 * @file
 * Copy files for JS vendor dependencies from node_modules to the app/assets/js/vendor folder.
 */

const path = require('path');
const { copy, emptyDir } = require('fs-extra');

const coreFolder = path.resolve(__dirname, '../../');
const packageFolder = `${coreFolder}/node_modules`;
const assetsFolder = `${coreFolder}/app/assets/js/vendor`;

(async () => {
    await emptyDir(`${assetsFolder}`);
    await copy(`${packageFolder}/ckeditor4/adapters`, `${assetsFolder}/ckeditor4/adapters`);
    await copy(`${packageFolder}/ckeditor4/lang`, `${assetsFolder}/ckeditor4/lang`);
    await copy(`${packageFolder}/ckeditor4/skins/moono-lisa`, `${assetsFolder}/ckeditor4/skins/moono-lisa`);
    await copy(`${packageFolder}/ckeditor4/vendor`, `${assetsFolder}/ckeditor4/vendor`);
    await copy(`${packageFolder}/ckeditor4/plugins/sourcedialog`, `${assetsFolder}/ckeditor4/plugins/sourcedialog`);
    await copy(`${packageFolder}/ckeditor4/plugins/mentions`, `${assetsFolder}/ckeditor4/plugins/mentions`);
    await copy(`${packageFolder}/ckeditor4/plugins/autocomplete`, `${assetsFolder}/ckeditor4/plugins/autocomplete`);
    await copy(`${packageFolder}/ckeditor4/plugins/textmatch`, `${assetsFolder}/ckeditor4/plugins/textmatch`);
    await copy(`${packageFolder}/ckeditor4/plugins/textwatcher`, `${assetsFolder}/ckeditor4/plugins/textwatcher`);
    await copy(`${packageFolder}/ckeditor4/plugins/tableselection`, `${assetsFolder}/ckeditor4/plugins/tableselection`);
    await copy(`${packageFolder}/ckeditor4/plugins/dialog`, `${assetsFolder}/ckeditor4/plugins/dialog`);
    await copy(`${packageFolder}/ckeditor4/plugins/scayt`, `${assetsFolder}/ckeditor4/plugins/scayt`);

    await copy(`${packageFolder}/ckeditor4/ckeditor.js`, `${assetsFolder}/ckeditor4/ckeditor.js`);
    await copy(`${packageFolder}/ckeditor4/styles.js`, `${assetsFolder}/ckeditor4/styles.js`);
    await copy(`${packageFolder}/ckeditor4/config.js`, `${assetsFolder}/ckeditor4/config.js`);
    await copy(`${packageFolder}/ckeditor4/contents.css`, `${assetsFolder}/ckeditor4/contents.css`);

    await copy(`${packageFolder}/jquery/dist/jquery.min.js`, `${assetsFolder}/jquery/jquery.min.js`);
    await copy(`${packageFolder}/vimeo-froogaloop2/javascript/froogaloop.min.js`, `${assetsFolder}/vimeo-froogaloop2/froogaloop.min.js`);
})();
