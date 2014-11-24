<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$root  = $container->getParameter('kernel.root_dir');

$paths = array(
    //customizable
    'themes'  => 'themes',
    'assets'  => 'media',
    'addons'  => 'addons',
    //fixed
    'root'    => substr($root, 0, -4),
    'app'     => 'app',
    'bundles' => 'app/bundles',
    'cache'   => 'app/cache',
    'vendor'  => 'vendor'
);
