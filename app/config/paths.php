<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$root  = $container->getParameter('kernel.root_dir');

$paths = array(
    //customizable
    'theme'   => 'themes',
    'assets'  => 'assets',
    'addons'  => 'addons',
    //fixed
    'root'    => substr($root, 0, -4),
    'app'     => 'app',
    'bundles' => 'app/bundles',
    'cache'   => 'app/cache',
    'vendor'  => 'vendor'
);