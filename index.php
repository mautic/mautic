<?php

define('MAUTIC_ROOT_DIR', __DIR__);
define('ELFINDER_IMG_PARENT_URL', 'media/bundles/fmelfinder');

// Fix for hosts that do not have date.timezone set, it will be reset based on users settings
date_default_timezone_set('UTC');

require 'app/config/bootstrap.php';

use Mautic\CoreBundle\ErrorHandler\ErrorHandler;
use Mautic\Middleware\MiddlewareBuilder;
use Symfony\Component\HttpFoundation\Request;

ErrorHandler::register($_SERVER['APP_ENV']);

$kernel   = (new MiddlewareBuilder(new AppKernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG'])))->resolve();
$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
