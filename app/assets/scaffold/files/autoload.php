<?php

if ('cli' !== PHP_SAPI && file_exists(dirname(__DIR__).'/../var/bootstrap.php.cache')) {
    require_once dirname(__DIR__).'/../var/bootstrap.php.cache';
}

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;

/** @var ClassLoader $loader */
$loader = require dirname(__DIR__).'/autoload.php';

AnnotationRegistry::registerLoader([$loader, 'loadClass']);

return $loader;
