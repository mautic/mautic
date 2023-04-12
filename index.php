<?php

define('MAUTIC_ROOT_DIR', __DIR__);

// Fix for hosts that do not have date.timezone set, it will be reset based on users settings
date_default_timezone_set('UTC');

require_once 'autoload.php';
$config = include 'app/config/environment.php';

use Mautic\CoreBundle\ErrorHandler\ErrorHandler;
use Mautic\Middleware\MiddlewareBuilder;
use Symfony\Component\HttpFoundation\Request;

//ErrorHandler::register('prod');
ErrorHandler::register($config['env']);

if (
    'dev' === strtolower($config['env'])
    && extension_loaded('apcu')
    && in_array(@$_SERVER['REMOTE_ADDR'], $config['dev_ip_whitelist'])
) {
    @apcu_clear_cache();
}

$kernel   = (new MiddlewareBuilder(new AppKernel($config['env'], $config['debug'])))->resolve();
$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
