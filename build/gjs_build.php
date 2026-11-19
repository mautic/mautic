<?php

$root = dirname(__DIR__);

passthru('composer gjs-build --working-dir='.escapeshellarg($root).' 2>&1', $exitCode);

// Non-fatal: missing Node must not abort composer install. Prebuilt assets ship in release packages.
if (0 !== $exitCode) {
    fwrite(STDERR, PHP_EOL.'WARN: GrapesJS builder assets failed to build. The builder will not load until you run: composer gjs-build'.PHP_EOL);
}

// Always succeed so the composer script chain continues.
exit(0);
