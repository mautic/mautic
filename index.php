<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
define('MAUTIC_ROOT_DIR', __DIR__);

// Fix for hosts that do not have date.timezone set, it will be reset based on users settings
date_default_timezone_set('UTC');

use Mautic\Middleware\MiddlewareBuilder;
use Symfony\Component\ClassLoader\ApcClassLoader;

$loader = require_once __DIR__.'/app/autoload.php';

/*
 * Use APC for autoloading to improve performance. Change 'sf2' to a unique prefix
 * in order to prevent cache key conflicts with other applications also using APC.
 */
//$apcLoader = new ApcClassLoader('sf2', $loader);
//$loader->unregister();
//$apcLoader->register(true);

\Mautic\CoreBundle\ErrorHandler\ErrorHandler::register('prod');

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();

Stack\run((new MiddlewareBuilder('prod'))->resolve($kernel));
