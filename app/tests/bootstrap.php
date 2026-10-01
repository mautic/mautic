<?php

declare(strict_types=1);

use DG\BypassFinals;

require __DIR__ . '/../../vendor/autoload.php';

BypassFinals::enable(bypassReadOnly: false);

// leave vendor untouched, rewriting it breaks Doctrine DBAL query building
BypassFinals::denyPaths(['*/vendor/*']);
