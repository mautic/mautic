<?php

/*
 * @copyright   2014 Mautic, NP
 * @author      Mautic
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
define('MAUTIC_ROOT_DIR', __DIR__);

// Fix for hosts that do not have date.timezone set, it will be reset based on users settings
date_default_timezone_set('UTC');

use Mautic\Middleware\MiddlewareBuilder;

$loader = require_once __DIR__.'/vendor/autoload.php';

/*
 * If you don't want to setup permissions the proper way, just uncomment the following PHP line
 * read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
 */
umask(0000);

if (true || in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    apc_clear_cache();
    apc_clear_cache('user');
    apc_clear_cache('opcode');
}

\Mautic\CoreBundle\ErrorHandler\ErrorHandler::register('dev');

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();

Stack\run((new MiddlewareBuilder('dev'))->resolve($kernel));
