<?php

$bootstrap = __DIR__.'/bootstrap.php.cache';

if (file_exists($bootstrap)) {
    require_once __DIR__.'/bootstrap.php.cache';
}

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;

/**
 * @var ClassLoader
 */
$loader = require __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader([$loader, 'loadClass']);

return $loader;
