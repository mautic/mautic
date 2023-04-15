<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/autoload.php';

if (!class_exists(Dotenv::class)) {
    throw new LogicException('Please run "composer require symfony/dotenv" to load the ".env" files configuring the application.');
}

// Load cached env vars if the .env.local.php file exists
// Run "composer dump-env prod" to create it (requires symfony/flex >=1.2)
if (is_array($env = @include dirname(__DIR__).'/.env.local.php') && (!isset($env['APP_ENV']) || ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? $env['APP_ENV']) === $env['APP_ENV'])) {
    (new Dotenv(false))->populate($env);
} else {
    // load all the .env files
    (new Dotenv(false))->loadEnv(dirname(__DIR__).'/.env');
}

// Fix for hosts that do not have date.timezone set, it will be reset based on users settings
date_default_timezone_set('UTC');
define('MAUTIC_ROOT_DIR', __DIR__);

$_SERVER += $_ENV;
$_SERVER['APP_ENV']     = $_ENV['APP_ENV']     = ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) ?: 'dev';
$_SERVER['APP_DEBUG']   = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? 'prod' !== $_SERVER['APP_DEBUG'];
$_SERVER['APP_DEBUG']   = $_ENV['APP_DEBUG']   = (int) $_SERVER['APP_DEBUG'] || filter_var($_SERVER['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
$_SERVER['IPS_ALLOWED'] = $_ENV['IPS_ALLOWED'] = ($_SERVER['IPS_ALLOWED'] ?? $_ENV['IPS_ALLOWED'] ?? null) ?: '127.0.0.1,::1,172.17.0.1';

if ('dev' === strtolower($_SERVER['APP_ENV']) && extension_loaded('apcu') && in_array(@$_SERVER['REMOTE_ADDR'], explode(',', $_SERVER['IPS_ALLOWED']))) {
    @apcu_clear_cache();
}
