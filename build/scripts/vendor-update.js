/**
 * @file
 * Copy files for JS vendor dependencies from node_modules to the app/assets/js/vendor folder.
 */

const path = require('path');
const { copy, emptyDir } = require('fs-extra');

const coreFolder = path.resolve(__dirname, '../../');
const packageFolder = `${coreFolder}/node_modules`;
const mediaFolder = `${coreFolder}/media/js/vendor`;

(async () => {
    await emptyDir(`${mediaFolder}`);
    await copy(`${packageFolder}/ckeditor4/adapters`, `${mediaFolder}/ckeditor4/adapters`);
    await copy(`${packageFolder}/ckeditor4/lang`, `${mediaFolder}/ckeditor4/lang`);
    await copy(`${packageFolder}/ckeditor4/skins/moono-lisa`, `${mediaFolder}/ckeditor4/skins/moono-lisa`);
    await copy(`${packageFolder}/ckeditor4/vendor`, `${mediaFolder}/ckeditor4/vendor`);
    await copy(`${packageFolder}/ckeditor4/plugins/sourcedialog`, `${mediaFolder}/ckeditor4/plugins/sourcedialog`);
    await copy(`${packageFolder}/ckeditor4/plugins/mentions`, `${mediaFolder}/ckeditor4/plugins/mentions`);
    await copy(`${packageFolder}/ckeditor4/plugins/autocomplete`, `${mediaFolder}/ckeditor4/plugins/autocomplete`);
    await copy(`${packageFolder}/ckeditor4/plugins/textmatch`, `${mediaFolder}/ckeditor4/plugins/textmatch`);
    await copy(`${packageFolder}/ckeditor4/plugins/textwatcher`, `${mediaFolder}/ckeditor4/plugins/textwatcher`);
    await copy(`${packageFolder}/ckeditor4/plugins/tableselection`, `${mediaFolder}/ckeditor4/plugins/tableselection`);
    await copy(`${packageFolder}/ckeditor4/plugins/dialog`, `${mediaFolder}/ckeditor4/plugins/dialog`);
    await copy(`${packageFolder}/ckeditor4/plugins/scayt`, `${mediaFolder}/ckeditor4/plugins/scayt`);

    await copy(`${packageFolder}/ckeditor4/ckeditor.js`, `${mediaFolder}/ckeditor4/ckeditor.js`);
    await copy(`${packageFolder}/ckeditor4/styles.js`, `${mediaFolder}/ckeditor4/styles.js`);
    await copy(`${packageFolder}/ckeditor4/config.js`, `${mediaFolder}/ckeditor4/config.js`);
    await copy(`${packageFolder}/ckeditor4/contents.css`, `${mediaFolder}/ckeditor4/contents.css`);

    await copy(`${packageFolder}/jquery/dist/jquery.min.js`, `${mediaFolder}/jquery/jquery.min.js`);
    await copy(`${packageFolder}/vimeo-froogaloop2/javascript/froogaloop.min.js`, `${mediaFolder}/vimeo-froogaloop2/froogaloop.min.js`);
})();
