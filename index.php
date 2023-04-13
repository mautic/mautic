<?php

define('MAUTIC_ROOT_DIR', __DIR__);

// Fix for hosts that do not have date.timezone set, it will be reset based on users settings
date_default_timezone_set('UTC');

require_once 'autoload.php';

use Mautic\CoreBundle\ErrorHandler\ErrorHandler;
use Mautic\CoreBundle\Loader\EnvironmentHandler;
use Mautic\Middleware\MiddlewareBuilder;
use Symfony\Component\HttpFoundation\Request;

$config = (new EnvironmentHandler())->getEnvParameters();
ErrorHandler::register($config['ENV']);

$kernel   = (new MiddlewareBuilder(new AppKernel($config['ENV'], $config['DEBUG'])))->resolve();
$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
