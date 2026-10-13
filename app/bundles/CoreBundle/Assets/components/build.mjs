import { build } from 'esbuild';
import { readdir } from 'node:fs/promises';

for (const entry of await readdir('src')) {
  if (!entry.endsWith('.ts') || entry.endsWith('.d.ts') || entry === 'lifecycle.ts') continue;
  await build({
    entryPoints: [`src/${entry}`],
    outfile: `../js/99.${entry.replace(/\.ts$/, '.js')}`,
    bundle: true,
    format: 'iife',
    target: 'es2019',
    minify: true,
    legalComments: 'eof'
  });
}
