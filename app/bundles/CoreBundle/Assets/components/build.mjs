import { build } from 'esbuild';

const runtimeGlobal = {
  name: 'mautic-runtime',
  setup(esbuildBuild) {
    esbuildBuild.onResolve({ filter: /^@mautic\/components$/ }, () => ({ path: 'runtime', namespace: 'mautic-runtime' }));
    esbuildBuild.onLoad({ filter: /.*/, namespace: 'mautic-runtime' }, () => ({
      contents: 'const runtime = window.MauticComponents; export const { animate, hover, press, visualElementStore, emit, mountComponent, springScale, drawStroke } = runtime;',
      loader: 'js'
    }));
  }
};

const shared = { absWorkingDir: import.meta.dirname, bundle: true, format: 'iife', target: 'es2019', minify: true, legalComments: 'eof' };

await build({ ...shared, entryPoints: ['src/runtime-global.ts'], outfile: '../js/98.component-runtime.js', bundle: true });

for (const name of ['animated-checkbox']) {
  await build({ ...shared, entryPoints: [`src/${name}.ts`], outfile: `../js/99.${name}.js`, plugins: [runtimeGlobal] });
}
