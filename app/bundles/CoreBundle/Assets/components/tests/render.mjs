import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { compile } from 'sass';

const core = resolve('..', '..');
const php = String.raw`
require $argv[4];
$loader = new \Twig\Loader\FilesystemLoader();
$loader->addPath($argv[1].'/Resources/views', 'MauticCore');
$twig = new \Twig\Environment($loader, ['autoescape' => 'html', 'strict_variables' => true]);
$messages = parse_ini_file($argv[1].'/Translations/en_US/messages.ini');
$twig->addFilter(new \Twig\TwigFilter('trans', static function ($key, $parameters = []) use ($messages) { return strtr($messages[$key] ?? $key, $parameters); }));
echo $twig->render('@MauticCore/Components/'.$argv[2].'.html.twig', json_decode($argv[3], true));
`;
const css = compile(resolve(core, 'Assets/css/app/scss/components/_hold-to-confirm.scss')).css;

export function render(name, options = {}) {
    return execFileSync('php', ['-r', php, core, name, JSON.stringify(options), process.env.MAUTIC_VENDOR_AUTOLOAD ?? resolve(core, '../../../vendor/autoload.php')], {encoding: 'utf8'});
}

export async function load(page, name, options = {}, extra = '', minified = false) {
    await page.setContent(`<style>:root {--focus: #0f62fe; --support-success: #24a148; --layer: #fff;} .btn {display:inline-flex;align-items:center;height:40px;border-radius:0;} .btn[size="sm"] {height:32px;} .btn[size="lg"] {height:48px;} .btn-tertiary span {height:40px;justify-content:space-between;} ${css}</style>${render(name, options)}${extra}`);
    const scripts = ['2.hold-to-confirm-beam.js', '3.hold-to-confirm.js'].map(file => resolve(core, 'Assets/js', file));
    if (minified) {
        const source = execFileSync('php', ['-r', String.raw`require $argv[1]; $minifier = new \MatthiasMullie\Minify\JS(); foreach (array_slice($argv, 2) as $file) { $minifier->add($file); } echo $minifier->minify();`, process.env.MAUTIC_VENDOR_AUTOLOAD ?? resolve(core, '../../../vendor/autoload.php'), ...scripts], {encoding: 'utf8'});
        await page.addScriptTag({content: source});
    } else {
        for (const path of scripts) await page.addScriptTag({path});
    }
}
