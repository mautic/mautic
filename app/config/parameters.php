<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//load default parameters from bundle files
$core    = $container->getParameter('mautic.bundles');
$addons  = $container->getParameter('mautic.addon.bundles');

$bundles = array_merge($core, $addons);
unset($core, $addons);

$mauticParams = array();

foreach ($bundles as $bundle) {
    if (!empty($bundle['config']['parameters'])) {
        $mauticParams = array_merge($mauticParams, $bundle['config']['parameters']);
    }
}

// Include path settings
$root  = $container->getParameter('kernel.root_dir');

// Closure to replace %kernel_root_dir% placeholders
$replaceRootPlaceholder = function(&$value) use ($root, &$replaceRootPlaceholder) {
    if (is_array($value)) {
        foreach ($value as &$v) {
            $replaceRootPlaceholder($v);
        }
    } elseif (strpos($value, '%kernel.root_dir%') !== false) {
        $value = str_replace('%kernel.root_dir%', $root, $value);
    }
};

// Include local paths
require 'paths.php';
$replaceRootPlaceholder($paths);

// Find available translations
$locales = array();

$extractLocales = function($dir) use (&$locales) {
    $locale = $dir->getFilename();

    // Check config
    $configFile = $dir->getRealpath() . '/config.php';
    if (file_exists($configFile)) {
        $config           = include $configFile;
        $locales[$locale] = (!empty($config['name'])) ? $config['name'] : $locale;
    }
};

$defaultLocalesDir = new \Symfony\Component\Finder\Finder();
$defaultLocalesDir->directories()->in($root . '/bundles/CoreBundle/Translations')->ignoreDotFiles(true)->depth('== 0');
foreach ($defaultLocalesDir as $dir) {
    $extractLocales($dir);
}

$installedLocales = new \Symfony\Component\Finder\Finder();
$installedLocales->directories()->in($root . '/../' . $paths['translations'])->ignoreDotFiles(true)->depth('== 0');

foreach ($installedLocales as $dir) {
    $extractLocales($dir);
}
unset($defaultLocalesDir, $installedLocales, $extractLocales);


$mauticParams['supported_languages'] = $locales;

// Load parameters array from local configuration
if (isset($paths['local_config'])) {
    if (file_exists($paths['local_config'])) {
        include $paths['local_config'];

        // Override default with local
        $mauticParams = array_merge($mauticParams, $parameters);
    }
}

// Force local specific params
if (file_exists(__DIR__ . '/parameters_local.php')) {
    include __DIR__ . '/parameters_local.php';

    //override default with forced
    $mauticParams = array_merge($mauticParams, $parameters);
}

// Set the paths
$mauticParams['paths'] = $paths;

// Add to the container
foreach ($mauticParams as $k => &$v) {
    // Update the file paths in case $factory->getParameter() is used
    $replaceRootPlaceholder($v);

    // Add to the container
    $container->setParameter("mautic.{$k}", $v);
}

// Used for passing params into factory/services
$container->setParameter('mautic.parameters', $mauticParams);

// Set the router URI for CLI
if (isset($mauticParams['site_url'])) {
    $parts = parse_url($mauticParams['site_url']);

    if (!empty($parts['host'])) {
        $path = '';

        if (!empty($parts['path'])) {
            // Check and remove trailing slash to prevent double // in Symfony cli generated URLs
            $path = $parts['path'];
            if (substr($path, -1) == '/') {
                $path = substr($path, 0, -1);
            }
        }

        $container->setParameter('router.request_context.host', $parts['host']);
        $container->setParameter('router.request_context.scheme', (!empty($parts['scheme']) ? $parts['scheme'] : 'http'));
        $container->setParameter('router.request_context.base_url', $path);
    }
}

unset($mauticParams, $replaceRootPlaceholder, $bundles);