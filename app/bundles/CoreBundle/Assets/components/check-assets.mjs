import { execFileSync } from 'node:child_process';
import { readdirSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';
import { render } from './tests/render.mjs';

const autoload = process.env.MAUTIC_VENDOR_AUTOLOAD ?? resolve('../../../../../vendor/autoload.php');
const php = String.raw`
require $argv[1];
foreach (array_slice($argv, 2) as $file) {
    $minifier = str_ends_with($file, '.js') ? new \MatthiasMullie\Minify\JS($file) : new \MatthiasMullie\Minify\CSS($file);
    $result = $minifier->minify();
    if ('' === $result) { throw new \RuntimeException('Empty asset: '.$file); }
    echo basename($file).' '.strlen($result)." bytes\n";
}
`;
const names = readdirSync('../css').filter((name) => name.startsWith('99.') && name.endsWith('.css')).map((name) => name.slice(3, -4));
for (const name of names) render(name, { label: 'Test', children: 'Test' });
const assets = names.flatMap((name) => [resolve(`../js/99.${name}.js`), resolve(`../css/99.${name}.css`)]).filter(existsSync);
process.stdout.write(execFileSync('php', ['-r', php, autoload, ...assets], { encoding: 'utf8' }));
