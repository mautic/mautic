<?php

declare(strict_types=1);

// Rector and PHPStan rule tests run pure, without the kernel. They must NOT enable BypassFinals:
// Rector 2.8.2+ fopen()s the fixture directory to flock it, which the BypassFinals stream wrapper cannot open.
require __DIR__ . '/../../vendor/autoload.php';
