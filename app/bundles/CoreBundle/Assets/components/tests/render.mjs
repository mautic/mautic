import { execFileSync } from 'node:child_process';
import { readFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';

const core = resolve('..');
const php = String.raw`
require $argv[4];
$loader = new \Twig\Loader\FilesystemLoader();
$loader->addPath($argv[1].'/Resources/views', 'MauticCore');
$twig = new \Twig\Environment($loader, ['autoescape' => 'html', 'strict_variables' => true]);
$messages = file_exists($argv[1].'/Translations/en_US/messages.ini') ? parse_ini_file($argv[1].'/Translations/en_US/messages.ini') : [];
$twig->addFilter(new \Twig\TwigFilter('trans', static function ($key, $parameters = []) use ($messages) { return strtr($messages[$key] ?? $key, $parameters); }));
echo $twig->render('@MauticCore/Components/'.$argv[2].'.html.twig', json_decode($argv[3], true));
`;

export function render(name, options = {}) {
  return execFileSync('php', ['-r', php, resolve(core, '..'), name, JSON.stringify(options), process.env.MAUTIC_VENDOR_AUTOLOAD ?? resolve(core, '../../../../vendor/autoload.php')], { encoding: 'utf8' });
}

export async function load(page, name, options = {}, extra = '') {
  await page.setContent(`<style>${readFileSync(resolve(core, `css/99.${name}.css`), 'utf8')}</style>${render(name, options)}${extra}`);
  await page.addScriptTag({ path: resolve(core, 'js/98.component-runtime.js') });
  const script = resolve(core, `js/99.${name}.js`);
  if (existsSync(script)) await page.addScriptTag({ path: script });
}
