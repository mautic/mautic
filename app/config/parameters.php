<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
include __DIR__.'/paths_helper.php';

//load default parameters from bundle files
$core    = $container->getParameter('mautic.bundles');
$plugins = $container->getParameter('mautic.plugin.bundles');

$bundles = array_merge($core, $plugins);
unset($core, $plugins);

$mauticParams = [];

foreach ($bundles as $bundle) {
    if (!empty($bundle['config']['parameters'])) {
        $mauticParams = array_merge($mauticParams, $bundle['config']['parameters']);
    }
}

// Find available translations
$locales = [];

$extractLocales = function ($dir) use (&$locales) {
    $locale = $dir->getFilename();

    // Check config
    $configFile = $dir->getRealpath().'/config.php';
    if (file_exists($configFile)) {
        $config           = include $configFile;
        $locales[$locale] = (!empty($config['name'])) ? $config['name'] : $locale;
    }
};

$defaultLocalesDir = new \Symfony\Component\Finder\Finder();
$defaultLocalesDir->directories()->in($root.'/bundles/CoreBundle/Translations')->ignoreDotFiles(true)->depth('== 0');
foreach ($defaultLocalesDir as $dir) {
    $extractLocales($dir);
}

$installedLocales = new \Symfony\Component\Finder\Finder();
$installedLocales->directories()->in($root.'/../'.$paths['translations'])->ignoreDotFiles(true)->depth('== 0');

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
if (file_exists(__DIR__.'/parameters_local.php')) {
    include __DIR__.'/parameters_local.php';

    //override default with forced
    $mauticParams = array_merge($mauticParams, $parameters);
}

// Set the paths
$mauticParams['paths'] = $paths;

// Add to the container
foreach ($mauticParams as $k => &$v) {
    // Update the file paths in case $factory->getParameter() is used
    $replaceRootPlaceholder($v);

    // Update with system value if applicable
    if (!empty($v) && is_string($v) && preg_match('/getenv\((.*?)\)/', $v, $match)) {
        $v = (string) getenv($match[1]);
    }

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

        $scheme           = (!empty($parts['scheme']) ? $parts['scheme'] : 'http');
        $portContainerKey = ($scheme === 'http') ? 'request_listener.http_port' : 'request_listener.https_port';

        $container->setParameter('router.request_context.host', $parts['host']);
        $container->setParameter('router.request_context.scheme', $scheme);
        $container->setParameter('router.request_context.base_url', $path);

        if (!empty($parts['port'])) {
            $container->setParameter($portContainerKey, (!empty($parts['port']) ? $parts['port'] : null));
        }
    }
}

$loader->import('rabbitmq.php');

unset($mauticParams, $replaceRootPlaceholder, $bundles);
