<?php

$bootstrap = __DIR__.'/bootstrap.php.cache';

if (file_exists($bootstrap)) {
    require_once __DIR__.'/bootstrap.php.cache';
}

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

return $loader;
