<?php

declare(strict_types=1);

use Mautic\CoreBundle\Test\EnvLoader;

require __DIR__.'/../vendor/autoload.php';

/*
 * Bootstrap for parallel test runs (paratest).
 *
 * paratest runs each worker in a separate process and assigns it a TEST_TOKEN
 * (1..N). To keep the workers from clobbering each other we give every worker
 * its own database and its own cache/log directory, all suffixed by the token.
 *
 * When TEST_TOKEN is absent (plain phpunit run) this file is a no-op beyond the
 * usual autoload + env loading, so behaviour is unchanged.
 */

// Populate base env from .env(.test): DB_NAME=mautictest, DB_HOST, etc.
EnvLoader::load();

$token = getenv('TEST_TOKEN');

if (false !== $token && '' !== $token) {
    $baseDbName = $_SERVER['DB_NAME'] ?? getenv('DB_NAME') ?: 'mautictest';

    $overrides = [
        // Each worker owns its database: mautictest_1, mautictest_2, ...
        // doctrine:database:create makes it on the worker's first test.
        'DB_NAME'        => $baseDbName.'_'.$token,
        // AppTestKernel::getCacheDir()/getLogDir() read these env vars.
        'TEST_CACHE_DIR' => 'var/cache/test_'.$token,
        'TEST_LOG_DIR'   => 'var/logs/test_'.$token,
        // ParameterLoader::getLocalConfigFile() reads this: each worker backs up and
        // rewrites its own config/local.php instead of racing on the shared one.
        'MAUTIC_LOCAL_CONFIG_FILE' => dirname(__DIR__).'/config/local_test_'.$token.'.php',
    ];

    foreach ($overrides as $key => $value) {
        putenv($key.'='.$value);   // read via getenv() (AppTestKernel)
        $_ENV[$key]    = $value;   // read via Symfony %env()% processor
        $_SERVER[$key] = $value;
    }

    // config_test.php calls EnvLoader::load() again while building the container.
    // Symfony Dotenv reclaims any var it owns (listed in SYMFONY_DOTENV_VARS) on that
    // second load, which would reset DB_NAME back to the un-suffixed .env value. Drop
    // DB_NAME from that ownership list so the second load leaves our value untouched.
    $ownedVars = $_SERVER['SYMFONY_DOTENV_VARS'] ?? $_ENV['SYMFONY_DOTENV_VARS'] ?? getenv('SYMFONY_DOTENV_VARS') ?: '';
    $keptVars  = array_filter(explode(',', $ownedVars), static fn (string $v): bool => '' !== $v && 'DB_NAME' !== $v);
    $newList   = implode(',', $keptVars);

    putenv('SYMFONY_DOTENV_VARS='.$newList);
    $_ENV['SYMFONY_DOTENV_VARS']    = $newList;
    $_SERVER['SYMFONY_DOTENV_VARS'] = $newList;
}
