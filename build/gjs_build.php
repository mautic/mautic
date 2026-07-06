<?php

// The build is intentionally non-fatal: it runs a separate npm project (Parcel) that needs a
// Node toolchain, so a missing/incompatible Node must not abort `composer install`. On failure we
// warn and point the user to `composer gjs-build`, mirroring the resilience of the other asset
// generation steps. End-user release/update packages ship the prebuilt assets, so this only
// matters for source installs.

$root = dirname(__DIR__);

passthru('composer gjs-build --working-dir='.escapeshellarg($root).' 2>&1', $exitCode);

if (0 !== $exitCode) {
    fwrite(STDERR, PHP_EOL.'WARN: GrapesJS builder assets failed to build. The builder will not load until you run: composer gjs-build'.PHP_EOL);
}

// Always succeed so the composer script chain continues.
exit(0);
