<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//load default parameters from bundle files
$bundles = $container->getParameter('mautic.bundles');
$addons  = $container->getParameter('mautic.addon.bundles');

$mauticParams = array();
foreach ($bundles as $bundle) {
    if (file_exists($bundle['directory'].'/Config/parameters.php')) {
        $bundleParams = include $bundle['directory'].'/Config/parameters.php';
        foreach ($bundleParams as $k => $v) {
            $mauticParams[$k] = $v;
        }
    }
}

foreach ($addons as $bundle) {
    if (file_exists($bundle['directory'].'/Config/parameters.php')) {
        $bundleParams = include $bundle['directory'].'/Config/parameters.php';
        foreach ($bundleParams as $k => $v) {
            $mauticParams[$k] = $v;
        }
    }
}

$mauticParams['supported_languages'] = array(
    'en_US' => 'English - United States'
);

//include path settings
$root  = $container->getParameter('kernel.root_dir');

//Closure to replace %kernel_root_dir% placeholders
$replaceRootPlaceholder = function(&$value) use ($root, &$replaceRootPlaceholder) {
    if (is_array($value)) {
        foreach ($value as &$v) {
            $replaceRootPlaceholder($v);
        }
    } elseif (strpos($value, '%kernel.root_dir%') !== false) {
        $value = str_replace('%kernel.root_dir%', $root, $value);
    }
};

//Include local paths
require 'paths.php';
$replaceRootPlaceholder($paths);

//load parameters array from local configuration
if (isset($paths['local_config'])) {
    if (file_exists($paths['local_config'])) {
        include $paths['local_config'];

        //override default with local
        $mauticParams = array_merge($mauticParams, $parameters);
    }
}

//Set the paths
$mauticParams['paths'] = $paths;

//Add to the container
foreach ($mauticParams as $k => &$v) {
    //update the file paths in case $factory->getParameter() is used
    $replaceRootPlaceholder($v);

    //add to the container
    $container->setParameter("mautic.{$k}", $v);
}

//used for passing params into factory/services
$container->setParameter('mautic.parameters', $mauticParams);