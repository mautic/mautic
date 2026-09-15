<?php

// Finds every Mautic class that extends a Symfony class which, under the installed
// Symfony 8, is either final or gone. Parses `use` statements to resolve the parent,
// then reflects only the vendor class - loading the Mautic class itself would fatal.
require_once __DIR__.'/../../vendor/autoload.php';

$roots = [__DIR__.'/../../app/bundles', __DIR__.'/../../plugins'];
$problems = [];
$checked = 0;

foreach ($roots as $root) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    foreach ($it as $file) {
        if (!$file->isFile() || 'php' !== $file->getExtension()) {
            continue;
        }
        $src = file_get_contents($file->getPathname());
        if (!preg_match('/^\s*(?:final\s+|abstract\s+|readonly\s+)*class\s+(\w+)\s+extends\s+([\\\\\w]+)/m', $src, $m)) {
            continue;
        }
        [$all, $class, $parent] = $m;

        // resolve the parent through use statements
        if (!str_starts_with($parent, '\\')) {
            $short = explode('\\', $parent)[0];
            if (preg_match('/^use\s+([\\\\\w]*\\\\'.preg_quote($short, '/').')(?:\s+as\s+\w+)?\s*;/m', $src, $u)) {
                $parent = $u[1].substr($parent, strlen($short));
            }
        }
        $parent = ltrim($parent, '\\');

        if (!str_starts_with($parent, 'Symfony\\') && !str_starts_with($parent, 'Doctrine\\')) {
            continue;
        }
        ++$checked;

        if (!class_exists($parent) && !interface_exists($parent)) {
            $problems[] = ['GONE', $parent, $class, $file->getPathname()];
            continue;
        }
        $rc = new ReflectionClass($parent);
        if ($rc->isFinal()) {
            $problems[] = ['FINAL', $parent, $class, $file->getPathname()];
        }
    }
}

printf("vendor parents checked: %d\n", $checked);
printf("problems: %d\n\n", count($problems));

usort($problems, fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
foreach ($problems as [$kind, $parent, $class, $path]) {
    printf("  [%s] %s\n        extended by %s\n        %s\n", $kind, $parent, $class,
        str_replace(__DIR__.'/../../', '', $path));
}
