<?php

// Portable hard purge of var/cache (every env) before assets are generated,
// so stale Doctrine metadata cannot poison the mapping.

$cacheDir = __DIR__.'/../var/cache';

if (!is_dir($cacheDir)) {
    return;
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);

foreach ($iterator as $file) {
    $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
}
