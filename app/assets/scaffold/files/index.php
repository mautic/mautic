<?php

require 'config/bootstrap.php';

use Mautic\CoreBundle\ErrorHandler\ErrorHandler;
use Mautic\Middleware\MiddlewareBuilder;
use Symfony\Component\HttpFoundation\Request;

ErrorHandler::register($_SERVER['APP_ENV']);

$kernel   = (new MiddlewareBuilder(new AppKernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG'])))->resolve();
$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
