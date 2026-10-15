#!/usr/bin/env php
<?php

if (empty(ini_get('date.timezone'))) {
    date_default_timezone_set('UTC');
}

// if you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
// umask(0000);

if (function_exists('set_time_limit')) {
    set_time_limit(0);
}

defined('IN_MAUTIC_CONSOLE') or define('IN_MAUTIC_CONSOLE', 1);

define('MAUTIC_ROOT_DIR', realpath(__DIR__.'/..'));

require_once __DIR__.'/../autoload.php';

use Mautic\InstallBundle\Command\InstallCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\ErrorHandler\Debug;

$input = new ArgvInput();

if (InstallCommand::COMMAND === $input->getFirstArgument()) {
    defined('MAUTIC_INSTALLER') or define('MAUTIC_INSTALLER', 1);
    if (null !== $prefix = $input->getParameterOption('--db_table_prefix', null, true)) {
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', (string) $prefix);
    }
}

if (null !== $env = $input->getParameterOption(['--env', '-e'], null, true)) {
    putenv('APP_ENV='.$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $env);
}

if ($input->hasParameterOption('--no-debug', true)) {
    putenv('APP_DEBUG='.$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0');
}

require dirname(__DIR__).'/app/config/bootstrap.php';

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    if (class_exists(Debug::class)) {
        Debug::enable();
    }
}

$kernel      = new AppKernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$application = new Application($kernel);
$application->setName('Mautic');
$application->setVersion($kernel->getVersion().' - app/'.$kernel->getEnvironment().($kernel->isDebug() ? '/debug' : ''));

return $application;
