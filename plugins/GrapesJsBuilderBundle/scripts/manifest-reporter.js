const { Reporter } = require('@parcel/plugin');
const path = require('path');

// Emits dist/manifest.json mapping logical names (builder.css, builder.js) to
// Parcel's actual output filenames, which are content/bundle hashed since Parcel 2.16.
// Runs on every successful build in both `parcel build` and `parcel watch`.
module.exports = new Reporter({
  async report({ event, options }) {
    if (event.type !== 'buildSuccess') {
      return;
    }

    const manifest = {};
    let distDir;

    for (const bundle of event.bundleGraph.getBundles()) {
      const filePath = bundle.filePath;
      if (!filePath) {
        continue;
      }
      const file = path.basename(filePath);
      const match = file.match(/^builder(?:\.[a-f0-9]+)?\.(css|js)$/);
      if (match) {
        manifest[`builder.${match[1]}`] = file;
        distDir = path.dirname(filePath);
      }
    }

    if (distDir) {
      await options.outputFS.writeFile(
        path.join(distDir, 'manifest.json'),
        JSON.stringify(manifest, null, 2),
      );
    }
  },
});
