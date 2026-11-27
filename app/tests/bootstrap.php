<?php

declare(strict_types=1);

use DG\BypassFinals;
use Mautic\CoreBundle\Loader\ParameterLoader;

require __DIR__ . '/../../vendor/autoload.php';

BypassFinals::enable(bypassReadOnly: false);

// leave vendor untouched, rewriting it breaks Doctrine DBAL query building;
// skip Rector/PHPStan rule fixtures too, or stripping "final" corrupts their expected output
BypassFinals::denyPaths(['*/vendor/*', '*/utils/rector/tests/*', '*/utils/phpstan/tests/*']);

// AppKernel locks MAUTIC_DB_SERVER_VERSION to "8.4" (MySQL platform) when no local config exists on
// its first boot, which breaks table introspection on MariaDB 11.4 (uca1400 collations). Create the
// stub that MauticMysqlTestCase writes anyway up front, so the platform is always auto-detected.
$localConfigFile = ParameterLoader::getLocalConfigFile(dirname(__DIR__), false);
if (!file_exists($localConfigFile)) {
    file_put_contents($localConfigFile, '<?php $parameters = [];');
}
