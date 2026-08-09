<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$configuration = new Configuration();

// tests of plugins are dev code, but they are covered by the "MauticPlugin\" prod autoload
$pluginTestPaths = glob(__DIR__.'/plugins/*/Tests') ?: [];

return $configuration
    ->addPathsToScan($pluginTestPaths, true)

    // 3rd party JS packages, not part of PHP autoload
    ->addPathRegexToExclude('~/node_modules/~')

    // Symfony DI configurator functions are declared in files loaded by composer "files" autoload
    ->ignoreUnknownFunctionsRegex('~^Symfony\\\\Component\\\\DependencyInjection\\\\Loader\\\\Configurator\\\\~')

    // all runtime dependencies are declared in app/composer.json (mautic/core-lib),
    // which this composer.json requires as a path repository - so every vendor class
    // used in plugins/ is reported as shadow here. Scope the ignore to plugins only,
    // so shadow deps stay detected in utils/ and elsewhere.
    ->ignoreErrorsOnPaths([
        __DIR__.'/plugins',
        // rule test fixtures are sample classes fed to the rules, not real deps
        __DIR__.'/utils/phpstan/tests/Rule/Fixture',
        __DIR__.'/utils/rector/tests',
    ], [ErrorType::SHADOW_DEPENDENCY])

    // composer plugins and the core package itself have no class usage in scanned paths
    ->ignoreErrorsOnPackages([
        'composer/installers',
        'cweagans/composer-patches',
        'mautic/core-lib',
    ], [ErrorType::UNUSED_DEPENDENCY]);
